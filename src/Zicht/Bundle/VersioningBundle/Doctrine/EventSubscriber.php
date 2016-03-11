<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Doctrine;

use Doctrine\Common\EventSubscriber as DoctrineEventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Entity\IVersionable;
use Zicht\Bundle\VersioningBundle\Entity\IVersionableChild;
use Zicht\Bundle\VersioningBundle\Services\SerializerService;
use Zicht\Bundle\VersioningBundle\Services\VersioningService;

/**
 * Class EventSubscriber
 *
 * @package Zicht\Bundle\VersioningBundle\Doctrine
 */
class EventSubscriber implements DoctrineEventSubscriber
{
    /** @var SerializerService */
    private $serializer;
    /**
     * @var VersioningService
     */
    private $versioning;

    /** @var array */
    private $handledEntities = [];

    /**
     * EventSubscriber constructor.
     *
     * @param SerializerService $serializer
     * @param VersioningService $versioning
     */
    public function __construct(SerializerService $serializer, VersioningService $versioning)
    {
        $this->serializer = $serializer;
        $this->versioning = $versioning;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
//                Events::postLoad,
                Events::onFlush,
                Events::postFlush
        ];
    }


    /**
     * postLoad listener
     *
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof IVersionable) {
            return;
        }

        //TODO: this needs to be tested!
        if ($this->versioning->getCurrentWorkingVersionNumber($entity)) {
            //we have requested a different one than the active one, so we need to replace it

            $result = $args->getEntityManager()->getRepository('ZichtVersioningBundle:EntityVersion')->findVersion($entity, $this->versioning->getCurrentWorkingVersionNumber($entity));

            if ($result) {
                $entity = $result;
            }
        }
    }

    /**
     * onFlush listener
     * We create the version here
     *
     * @param OnFlushEventArgs $args
     * @return void
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof IVersionable || $entity instanceof IVersionableChild) {

                $entityVersion = $this->handleVersioning($entity, $em);

                if (!$entityVersion->isActive()) {
                    $this->undoEntityChanges($entity, $uow);
                }
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof IVersionable || $entity instanceof IVersionableChild) {

                $entityVersion = $this->handleVersioning($entity, $em);

                if (!$entityVersion->isActive()) {
                    $this->undoEntityChanges($entity, $uow);
                }
            }
        }

        $uow->computeChangeSets();
    }

    /**
     * postFlush listener
     * We update the ids here for the just inserted entities - since in the onFlush we don't have the ids for the inserted entities
     *
     * @param PostFlushEventArgs $args
     * @return void
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $em  = $args->getEntityManager();

        //temporary remove the eventSubscriber - to prevent infinite loop ^^
        $em->getEventManager()->removeEventSubscriber($this);

        foreach ($this->handledEntities as $entityMap) {
            /** @var IVersionable $entity */
            $entity = $entityMap['entity'];
            /** @var EntityVersion $entityVersion */
            $entityVersion = $entityMap['entityVersion'];

            $entityVersion->setOriginalId($entity->getId());
            $em->persist($entityVersion);
        }

        $em->flush();

        //re-add the eventSubscriber again
        $em->getEventManager()->addEventSubscriber($this);
    }

    /**
     * Handles the versioning for the given entity
     *
     * @param IVersionable $entity
     * @param EntityManager $em
     * @return EntityVersion
     */
    private function handleVersioning(IVersionable $entity, EntityManager $em)
    {
        if ($entity instanceof IVersionableChild) {
            do {
                $entity = $entity->getParent();
            } while($entity instanceof IVersionableChild);
        }

        $hash = $this->versioning->makeHash($entity);

        if (!array_key_exists($hash, $this->handledEntities)) {
            $entityVersion = $this->createEntityVersion($entity);

            if ($entityVersion->isActive()) {
                $this->versioning->deactivateAll($entity);
            }

            $em->persist($entityVersion);

            $this->handledEntities[$hash] = ['entityVersion' => $entityVersion, 'entity' => $entity];
        }

        return $this->handledEntities[$hash]['entityVersion'];
    }

    /**
     * Create a new entityVersion
     *
     * @param IVersionable $entity
     * @return EntityVersion
     */
    private function createEntityVersion(IVersionable $entity)
    {
        $newEntityVersion = new EntityVersion();

        $newEntityVersion->setSourceClass(get_class($entity));
        $newEntityVersion->setOriginalId($entity->getId());
        $newEntityVersion->setData($this->serializer->serialize($entity));
        $newEntityVersion->setVersionNumber($this->versioning->getVersionCount($entity) + 1);

        $entityVersionInformation = $this->versioning->getEntityVersionInformation($entity);

        if ($entityVersionInformation) {
            $newEntityVersion->setIsActive($entityVersionInformation->isActive());
            $newEntityVersion->setBasedOnVersion($entityVersionInformation->getVersionNumber());
        } else {
            //if there is no version information, the entity is new and should be set to active
            $newEntityVersion->setIsActive(true);
        }

        return $newEntityVersion;
    }

    /**
     * Undo the changes made to the given entity
     *
     * @param IVersionable $entity
     * @param UnitOfWork $uow
     * @return void
     */
    private function undoEntityChanges(IVersionable $entity, UnitOfWork $uow)
    {
        if ($entity instanceof IVersionableChild) {
            //TODO shouldn't we check here the entity state, so we can refresh it instead of removing???!!!
            $uow->remove($entity);

            $entity = $entity->getParent();
        }

        $uow->refresh($entity);
        $uow->clearEntityChangeSet(spl_object_hash($entity));
    }
}
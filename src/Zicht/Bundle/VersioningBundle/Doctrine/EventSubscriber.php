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
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
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
                Events::postLoad,
                Events::preUpdate,
                Events::onFlush,
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
     * preUpdate doctrine listener
     *
     * @param PreUpdateEventArgs $args
     * @return void
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof IVersionable) {
            return;
        }

        $entityVersionInformation = $this->versioning->getEntityVersionInformation($entity);

        /*
         * whipe the changes if we are not working in the active version
         * don't worry, the changes will be written to the versioning table (@see onFlush)
         */
        if (!$entityVersionInformation->isActive()) {
            $args->getEntityManager()->refresh($entity);
            $args->getEntityManager()->getUnitOfWork()->clearEntityChangeSet(spl_object_hash($entity));
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
            $this->handleVersioning($entity, $em);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->handleVersioning($entity, $em);
        }

        $uow->computeChangeSets();
    }

    /**
     * Handles the versioning for the given entity
     *
     * @param IVersionable $entity
     * @param EntityManager $em
     * @return void
     */
    private function handleVersioning(IVersionable $entity, EntityManager $em)
    {
        if ($entity instanceof IVersionable || $entity instanceof IVersionableChild) {
            if ($entity instanceof IVersionableChild) {
                $entity = $entity->getParent();
            }

            $entityVersion = $this->createEntityVersion($entity);

            if ($entityVersion->isActive()) {
                $this->versioning->deactivateAll($entity);
            }

            $em->persist($entityVersion);
        }
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
}
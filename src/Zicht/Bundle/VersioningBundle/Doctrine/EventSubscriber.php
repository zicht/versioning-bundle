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
use Zicht\Bundle\VersioningBundle\Model\EntityVersionInterface;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;
use Zicht\Bundle\VersioningBundle\Model\VersionableChildInterface;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;

/**
 * Class EventSubscriber
 *
 * @package Zicht\Bundle\VersioningBundle\Doctrine
 */
class EventSubscriber implements DoctrineEventSubscriber
{
    /**
     * @var VersioningManager
     */
    private $versioning;

    /** @var array */
    private $createdEntities = [];

    private $activatedVersions = [];

    /**
     * EventSubscriber constructor.
     *
     * @param VersioningManager $versioning
     */
    public function __construct(VersioningManager $versioning)
    {
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

        if (!$entity instanceof VersionableInterface) {
            return;
        }

        if ($version = $this->versioning->getVersionToLoad($entity)) {
            $this->versioning->loadVersion($entity, $version);
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

        foreach (['insert' => $uow->getScheduledEntityInsertions(), 'update' => $uow->getScheduledEntityUpdates()] as $type => $entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof VersionableInterface || $entity instanceof VersionableChildInterface) {

                    if ('update' === $type) {
                        list($versionOperation, $baseVersion) = $this->versioning->getVersionOperation($entity);
                        switch ($versionOperation) {
                            case VersioningManager::ACTION_ACTIVATE:
                                $version = $this->versioning->createEntityVersion($entity, $uow->getEntityChangeSet($entity));
                                $version->setBasedOnVersion($baseVersion);
                                $uow->scheduleForInsert($version);
                                $this->versioning->addAffectedVersion($entity, $version);
                                $version->setIsActive(true);

                                $currentActive = $this->versioning->getActiveVersion($entity);

                                if ($currentActive && $currentActive->getVersionNumber() !== $version->getVersionNumber()) {
                                    $currentActive->setIsActive(false);
                                    $uow->scheduleForUpdate($currentActive);
                                    $uow->scheduleForDirtyCheck($currentActive);
                                    $uow->computeChangeSets();
                                }

                                break;
                            case VersioningManager::ACTION_NEW:
                                // new version does not update the current active version
                                $version = $this->versioning->createEntityVersion($entity, $uow->getEntityChangeSet($entity));
                                $version->setBasedOnVersion($baseVersion);
                                $uow->scheduleForInsert($version);
                                $uow->clearEntityChangeSet(spl_object_hash($entity));
                                $this->versioning->addAffectedVersion($entity, $version);
                                break;
                            case VersioningManager::ACTION_UPDATE:
                                $version = $this->versioning->updateEntityVersion($entity, $uow->getEntityChangeSet($entity), $baseVersion);
                                $uow->scheduleForUpdate($version);
                                $uow->scheduleForDirtyCheck($version);
                                $this->versioning->addAffectedVersion($entity, $version);
                                $uow->computeChangeSets();
                                break;
                            default:
                                throw new \UnexpectedValueException("Can't handle this operation: '{$versionOperation}'");
                        }
                    } else {
                        $this->createdEntities[]= ['entity' => $entity, 'version' => $this->versioning->createEntityVersion($entity, $uow->getEntityChangeSet($entity))];
                    }
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

        $num = 0;
        foreach ($this->createdEntities as $entityMap) {
            /** @var VersionableInterface $entity */
            $entity = $entityMap['entity'];
            /** @var EntityVersionInterface $entityVersion */
            $entityVersion = $entityMap['version'];

            $entityVersion->setOriginalId($entity->getId());
            $em->persist($entityVersion);
            $num ++;
        }

        if ($num > 0) {
            $em->flush();
        }

        //re-add the eventSubscriber again
        $em->getEventManager()->addEventSubscriber($this);
    }
}
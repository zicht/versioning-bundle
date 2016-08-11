<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Doctrine;

use Doctrine\Common\EventSubscriber as DoctrineEventSubscriber;
use Doctrine\ORM\Event;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Exception\InvalidStateException;
use Zicht\Bundle\VersioningBundle\Exception\UnsupportedVersionOperationException;
use Zicht\Bundle\VersioningBundle\Model\EmbeddedVersionableInterface;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;
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
    private $versioning = null;

    /**
     * Keeps a map of versions that are being affected during a persist loop.
     *
     * @var array
     */
    private $versionMap = [];

    /**
     * EventSubscriber constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
            Events::preFlush,
            Events::postFlush,
            Events::onFlush
        ];
    }


    /**
     * postLoad listener
     *
     * @param Event\LifecycleEventArgs $args
     * @return void
     */
    public function postLoad(Event\LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof VersionableInterface) {
            return;
        }

        $this->fetchVersioningService();

        $this->versioning->loadVersion($entity);
    }


    /**
     * Hook into the onClear event to remove referenced entity's from the VersioningManager
     *
     * @param Event\OnClearEventArgs $e
     * @return void
     */
    public function onClear(Event\OnClearEventArgs $e)
    {
        $this->fetchVersioningService();

        $this->versioning->clear($e->getEntityClass());
    }


    /**
     * Hook into the preFlush to see if versions need to be created, updated or activated and
     * adds all related operations to the UnitOfWork.
     *
     * @param Event\PreFlushEventArgs $args
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function preFlush(Event\PreFlushEventArgs $args)
    {
        $this->fetchVersioningService();

        $em  = $args->getEntityManager();

        $uow = $em->getUnitOfWork();

        $objectMap = [];

        $uow->computeChangeSets();

        foreach (['insert' => $uow->getScheduledEntityInsertions(), 'update' => $uow->getScheduledEntityUpdates(), 'delete' => $uow->getScheduledEntityDeletions()] as $type => $entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof EmbeddedVersionableInterface && $parent = $entity->getVersionableParent()) {
                    // mimic an update for the parent. Any creation or update or delete should affect the parent,
                    // not the 'embedded' entity
                    if ($parent->getId()) {
                        $type = 'update';
                        $entity = $parent;
                    }
                }

                if ($entity instanceof VersionableInterface) {
                    $objectMap[$type][spl_object_hash($entity)]= $entity;
                }
            }
        }


        foreach ($this->versioning->getExplicitVersionOperations() as list($className, $id, $operation, $version, $meta)) {
            $entity = $em->find($className, $id);

            if (!in_array(spl_object_hash($entity), array_map('array_keys', $objectMap))) {
                // minic a change for the versions that are not part of the unit of work, but do require an explicit change:
                $objectMap['update'][spl_object_hash($entity)]= $entity;
            }
        }

        $this->versionMap = [];

        foreach ($objectMap as $type => $entities) {
            foreach ($entities as $entity) {
                if ('update' === $type) {
                    list($versionOperation, $baseVersion, $meta) = $this->versioning->getVersionOperation($entity);

                    switch ($versionOperation) {
                        case VersioningManager::VERSION_OPERATION_NEW:
                            $version = $this->versioning->createEntityVersion($entity, $uow->getEntityChangeSet($entity), $baseVersion, $meta);

                            $uow->scheduleForInsert($version);
                            $uow->clearEntityChangeSet(spl_object_hash($entity));

                            $this->versionMap[spl_object_hash($entity)]= $version;

                            // this makes sure that, if the 'NEW' operation was triggered by an explicit version
                            // operation, we mark it as handled here, so any subsequent flush won't keep creating new
                            // versions. Fixes RCO-882
                            $this->versioning->markExplicitVersionOperationHandled($entity);

                            break;

                        case VersioningManager::VERSION_OPERATION_UPDATE:
                            $version = $this->versioning->updateEntityVersion($entity, $uow->getEntityChangeSet($entity), $baseVersion, $meta);

                            $uow->scheduleForDirtyCheck($version);

                            // Remove scheduled persistence in the 'real' entity if the version is not active:
                            if (!$version->isActive()) {
                                $uow->clearEntityChangeSet(spl_object_hash($entity));
                            }

                            $this->versionMap[spl_object_hash($entity)]= $version;

                            break;

                        case VersioningManager::VERSION_OPERATION_ACTIVATE:
                            $version = $this->versioning->updateEntityVersion($entity, $uow->getEntityChangeSet($entity), $baseVersion, $meta);
                            $version->setIsActive(true);

                            $uow->scheduleForUpdate($version);
                            $uow->scheduleForDirtyCheck($version);

                            $currentActive = $this->versioning->findActiveVersion($entity);

                            if ($currentActive && $currentActive->getVersionNumber() !== $version->getVersionNumber()) {
                                $currentActive->setIsActive(false);
                                $uow->scheduleForUpdate($currentActive);
                                $uow->scheduleForDirtyCheck($currentActive);
                            }

                            // If the object has persistentCollections stashed, use those to
                            // have doctrine synchronize the collections with the database for the loaded
                            // version. 
                            // See the Serializer for the implementation
                            if (isset($entity->__persistentCollections__)) {
                                foreach ($entity->__persistentCollections__ as list($reflection, $collection)) {
                                    $collection->clear();
                                    foreach ($reflection->getValue($entity) as $value) {
                                        $collection->add($value);
                                    }
                                }
                            }

                            $this->versionMap[spl_object_hash($entity)]= $version;

                            $this->versioning->markExplicitVersionOperationHandled($entity);
                            break;

                        default:
                            throw new UnsupportedVersionOperationException("Can't handle this operation: '{$versionOperation}'");
                    }
                } else {
                    $version = $this->versioning->createEntityVersion($entity, $uow->getEntityChangeSet($entity));
                    $version->setIsActive(true);
                    $uow->scheduleForInsert($version);
                    $this->versionMap[spl_object_hash($entity)]= $version;
                }
            }
        }
    }


    public function onFlush(Event\OnFlushEventArgs $e)
    {
        $uow = $e->getEntityManager()->getUnitOfWork();

        // See if any of the remaining entities would be inserted after recompute of the change sets.
        $allScheduled = [
            'insert' => $uow->getScheduledEntityInsertions(),
            'delete' => $uow->getScheduledEntityDeletions(),
            'update' => $uow->getScheduledEntityUpdates()
        ];

        foreach ($allScheduled as $operation => $entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof EmbeddedVersionableInterface) {
                    if (!isset($this->versionMap[spl_object_hash($entity->getVersionableParent())])) {
                        // This is an error state that should never occur. The entity pointed to in this case
                        // should have been scheduled for dirty check in the switch case above and therefore
                        // should be part of the change set, and thus it's version should be known to the current
                        // scope.
                        $conjugated = rtrim($operation, 'e') . 'ed';
                        throw new InvalidStateException(
                            "The versionable parent of this object was not persisted as a version, "
                            . "but this entity would be {$conjugated} by the unit of work." // and in case you didn't get any
                           // of that, that is bad. Because that breaks the entire concept.
                        );
                    }

                    if (!$this->versionMap[spl_object_hash($entity->getVersionableParent())]->isActive()) {
                        $uow->detach($entity);
                    }
                }
            }
        }
    }


    /**
     * postFlush listener
     * We update the ids here for the just inserted entities - since in the onFlush we don't have the ids for the inserted entities
     *
     * @param Event\PostFlushEventArgs $args
     * @return void
     */
    public function postFlush(Event\PostFlushEventArgs $args)
    {
        $this->fetchVersioningService();

        $em = $args->getEntityManager();

        //temporary remove the eventSubscriber - to prevent infinite loop ^^
        $em->getEventManager()->removeEventSubscriber($this);

        $num = 0;
        foreach ($this->versioning->getAffectedVersions() as $affectedVersion) {
            /** @var VersionableInterface $entity */
            /** @var EntityVersion $version */
            list($entity, $version) = $affectedVersion;

            if (!$version->getOriginalId()) {
                $version->setOriginalId($entity->getId());
                $em->persist($version);
                $num ++;
            }
        }

        if ($num > 0) {
            $em->flush();
        }

        //re-add the eventSubscriber again
        $em->getEventManager()->addEventSubscriber($this);
    }

    /**
     * Get the versioninig service. Needed to get rid of an otherwise circular dependency.
     *
     * @return void
     */
    private function fetchVersioningService()
    {
        if (null === $this->versioning) {
            $this->versioning = $this->container->get('zicht_versioning.manager');
        }
    }
}

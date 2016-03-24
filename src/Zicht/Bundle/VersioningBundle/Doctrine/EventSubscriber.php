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
    private $versioning = null;

    /**
     * EventSubscriber constructor.
     *
     * @param VersioningManager $versioning
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
            Events::onFlush,
            Events::postFlush
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

        if ($version = $this->versioning->getVersionToLoad($entity)) {
            $this->versioning->loadVersion($entity, $version);
        }
    }

    /**
     * onFlush listener
     * We create the version here
     *
     * @param Event\OnFlushEventArgs $args
     * @return void
     */
    public function onFlush(Event\OnFlushEventArgs $args)
    {
        $this->fetchVersioningService();

        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach (['insert' => $uow->getScheduledEntityInsertions(), 'update' => $uow->getScheduledEntityUpdates()] as $type => $entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof VersionableInterface || $entity instanceof VersionableChildInterface) {
                    if ('update' === $type) {
                        list($versionOperation, $baseVersion) = $this->versioning->getVersionOperation($entity);
                        switch ($versionOperation) {
                            case VersioningManager::VERSION_OPERATION_ACTIVATE:
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
                                }

                                break;

                            case VersioningManager::VERSION_OPERATION_NEW:
                                $version = $this->versioning->createEntityVersion($entity, $uow->getEntityChangeSet($entity));
                                $version->setBasedOnVersion($baseVersion);
                                $uow->scheduleForInsert($version);
                                $uow->clearEntityChangeSet(spl_object_hash($entity));
                                $this->versioning->addAffectedVersion($entity, $version);
                                break;

                            case VersioningManager::VERSION_OPERATION_UPDATE:
                                $version = $this->versioning->updateEntityVersion($entity, $uow->getEntityChangeSet($entity), $baseVersion);
                                $uow->scheduleForUpdate($version);
                                $uow->scheduleForDirtyCheck($version);
                                $this->versioning->addAffectedVersion($entity, $version);
                                break;

                            default:
                                throw new \UnexpectedValueException("Can't handle this operation: '{$versionOperation}'");
                        }
                    } else {
                        $version = $this->versioning->createEntityVersion($entity, $uow->getEntityChangeSet($entity));
                        $version->setIsActive(true);
                        $uow->scheduleForInsert($version);
                        $this->versioning->addAffectedVersion($entity, $version);
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
     * @param Event\PostFlushEventArgs $args
     * @return void
     */
    public function postFlush(Event\PostFlushEventArgs $args)
    {
        $this->fetchVersioningService();

        $em  = $args->getEntityManager();

        //temporary remove the eventSubscriber - to prevent infinite loop ^^
        $em->getEventManager()->removeEventSubscriber($this);

        $num = 0;
        foreach ($this->versioning->getAffectedVersions() as $affectedVersion) {
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
     */
    private function fetchVersioningService()
    {
        if (null === $this->versioning) {
            $this->versioning = $this->container->get('zicht_versioning.manager');
        }
    }
}
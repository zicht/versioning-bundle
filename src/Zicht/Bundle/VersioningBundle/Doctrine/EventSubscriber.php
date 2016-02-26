<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\EventSubscriber as DoctrineEventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Entity\IVersionable;
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
     * A queue with items to persist after the flush has triggered
     * @var array
     */
    private $queue = [];

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
                Events::postPersist,
                Events::postLoad,
                Events::preUpdate,
                Events::postFlush,
        ];
    }


    /**
     * postPersist doctrine listener
     *
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof IVersionable) {
            return;
        }

        $this->createVersion($entity);
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

        if ($this->versioning->getCurrentWorkingVersionNumber($entity)) {
            //we have requested a different one than the active one, so we need to replace it

            $result = $args->getEntityManager()->getRepository('ZichtVersioningBundle:EntityVersion')->findVersion($entity, $this->versioning->getCurrentWorkingVersionNumber($entity));

            var_dump($result);
            exit;
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

        $entityVersion = $this->createVersion($entity);

        /*
         * whipe the changes if we are not working in the active version
         *
         * don't worry, the changes already are written to the versioning table (@see createVersion)
         */
        if (!$entityVersion->isActive()) {
            $args->getEntityManager()->refresh($entity);
            $args->getEntityManager()->getUnitOfWork()->clearEntityChangeSet(spl_object_hash($entity));
        }
    }

    /**
     * postFlush listener
     * To persist all items in the queue
     * This is needed, since we want to persist things from the preUpdate, but we can't persist in that handler
     *
     * @param PostFlushEventArgs $args
     * @return void
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $this->persistQueue($args->getEntityManager());
    }

    /**
     * Create a new entityVersion
     *
     * @param IVersionable $entity
     * @return EntityVersion
     */
    private function createVersion(IVersionable $entity)
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

        $this->queue[] = $newEntityVersion;

        return $newEntityVersion;
    }

    /**
     * Helper method to persist the queue
     *
     * @param EntityManager $entityManager
     * @return void
     */
    private function persistQueue(EntityManager $entityManager)
    {
        if (!empty($this->queue)) {
            foreach ($this->queue as $thing) {
                $entityManager->persist($thing);
            }

            $this->queue = [];
            $entityManager->flush();
        }
    }
}
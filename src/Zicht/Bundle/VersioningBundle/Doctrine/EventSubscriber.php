<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\EventSubscriber as DoctrineEventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
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
        return ['postPersist', 'preUpdate', 'postFlush'];
    }


    /**
     * prePersist doctrine listener
     *
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$entity instanceof IVersionable) {
            return;
        }

        $this->createVersion($entity);
    }

    /**
     * prePersist doctrine listener
     *
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$entity instanceof IVersionable) {
            return;
        }

        $this->createVersion($entity);
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if (!empty($this->queue)) {

            $em = $args->getEntityManager();

            foreach ($this->queue as $thing) {

                $em->persist($thing);
            }

            $this->queue = [];
            $em->flush();
        }
    }

    /**
     * Create a new version (and store it in the database)
     *
     * @param IVersionable $entity
     * @return void
     */
    private function createVersion(IVersionable $entity)
    {
        $entityVersion = new EntityVersion();
        //TODO: how to get the author name? :|
        $entityVersion->setSourceClass(get_class($entity));
        $entityVersion->setOriginalId($entity->getId());
        $entityVersion->setData($this->serializer->serialize($entity));
        $entityVersion->setVersionNumber($this->versioning->getVersionCount($entity) + 1);
        
        $this->queue[] = $entityVersion;
    }
}
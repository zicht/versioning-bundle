<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Entity\IVersionable;
use Zicht\Bundle\VersioningBundle\Services\SerializerService;

/**
 * Class EventListener
 *
 * @package Zicht\Bundle\VersioningBundle\Doctrine
 */
class EventListener
{
    /** @var Registry */
    private $doctrine;

    /** @var SerializerService */
    private $serializer;

    /**
     * EventListener constructor.
     *
     * @param Registry $doctrine
     * @param SerializerService $serializer
     */
    public function __construct(Registry $doctrine, SerializerService $serializer)
    {
        $this->doctrine = $doctrine;
        $this->serializer = $serializer;
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
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$entity instanceof IVersionable) {
            return;
        }

        $this->createVersion($entity);
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

        //TODO: get the previous saved version and update the number
        //$entityVersion->setVersionNumber

        $this->doctrine->getManager()->persist($entityVersion);
        $this->doctrine->getManager()->flush();
        echo 'VERSION WRITTEN' . PHP_EOL;
    }
}
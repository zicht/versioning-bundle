<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Services;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Entity\IVersionable;

/**
 * Class VersioningService
 *
 * @package Zicht\Bundle\VersioningBundle\Services
 */
class VersioningService
{
    /**
     * @var Registry
     */
    private $doctrine;
    /**
     * @var SerializerService
     */
    private $serializer;

    /**
     * VersioningService constructor.
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
     * Get the version count for the given entity
     *
     * @param IVersionable $entity
     * @return integer
     */
    public function getVersionCount(IVersionable $entity)
    {
        $result = $this->doctrine->getManager()->getRepository('ZichtVersioningBundle:EntityVersion')->findVersions($entity);
        return count($result);
    }

    /**
     * Sets the given page to the version information of the given version
     *
     * @param IVersionable $entity
     * @param integer $version
     * @return void
     */
    public function setActive(IVersionable $entity, $version)
    {
        /** @var EntityVersion $entityVersion */
        $entityVersion = $this->doctrine->getManager()->getRepository('ZichtVersioningBundle:EntityVersion')->findVersion($entity, $version);

        $storedEntity = $this->serializer->deserialize($entityVersion);
        $storedEntity = $this->doctrine->getManager()->merge($storedEntity);

        $this->doctrine->getManager()->persist($storedEntity);
        $this->doctrine->getManager()->flush();
    }
}
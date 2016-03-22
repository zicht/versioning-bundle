<?php

/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Entity\VersionableInterface;
use Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DateTimeNormalizer;
use Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer;

/**
 * Class SerializerService
 *
 * @package Zicht\Bundle\VersioningBundle\Services
 */
class SerializerService
{
    /** @var Serializer */
    private $serializer;

    /**
     * SerializerService constructor.
     */
    public function __construct(EntityManager $manager)
    {
        $this->serializer = new Serializer(
            [new DateTimeNormalizer(), new DoctrineEntityNormalizer($manager)],
            [new JsonEncoder()]
        );
    }

    /**
     * Serializes the given entity
     *
     * @param VersionableInterface $entity
     * @return string
     */
    public function serialize(VersionableInterface $entity)
    {
        return $this->serializer->serialize($entity, 'json');
    }

    /**
     * Deserializes the given entity
     *
     * @param EntityVersion $entityVersion
     * @return VersionableInterface $entity
     */
    public function deserialize(EntityVersion $entityVersion, $targetObject = null)
    {
        return $this->serializer->deserialize($entityVersion->getData(), $entityVersion->getSourceClass(), 'json', ['object' => $targetObject]);
    }
}
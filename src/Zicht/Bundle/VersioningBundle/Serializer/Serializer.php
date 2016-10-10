<?php

/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Serializer;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionInterface;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;
use Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DateTimeNormalizer;
use Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer;
use Symfony\Component\Serializer\Serializer as BaseSerializer;

/**
 * Class Serializer
 *
 * @package Zicht\Bundle\VersioningBundle\Serializer
 */
class Serializer
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * SerializerService constructor.
     *
     * @param EntityManager $manager
     */
    public function __construct(EntityManager $manager)
    {
        $this->serializer = new BaseSerializer(
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
     * @param EntityVersionInterface $entityVersion
     * @param VersionableInterface $targetObject
     * @return VersionableInterface $entity
     */
    public function deserialize(EntityVersionInterface $entityVersion, $targetObject = null)
    {
        $className = $entityVersion->getSourceClass();
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Entity version does not have a source class");
        }
        if (null !== $targetObject && !$targetObject instanceof $className) {
            throw new \InvalidArgumentException("Trying to deserialize into an object of a mismatching type");
        }
        return $this->serializer->deserialize(
            $entityVersion->getData(),
            $entityVersion->getSourceClass(),
            'json',
            ['object' => $targetObject]
        );
    }
}

<?php

/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Entity\IVersionable;
use Zicht\Bundle\VersioningBundle\Serializer\Normalizer\ClassAwareNormalizer;

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
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * SerializerService constructor.
     *
     * @param AnnotationReader $annotationReader
     */
    public function __construct(AnnotationReader $annotationReader)
    {
        $this->annotationReader = $annotationReader;

        $objectNormalizer = new ClassAwareNormalizer();
        $objectNormalizer->setAnnotationReader($this->annotationReader);
        $objectNormalizer->setCircularReferenceHandler(function ($object) {
            return $object->getId();
        });
        $this->serializer = new Serializer([$objectNormalizer], [new JsonEncoder()]);
    }

    /**
     * Serializes the given entity
     *
     * @param IVersionable $entity
     * @return string
     */
    public function serialize(IVersionable $entity)
    {
        return $this->serializer->serialize($entity, 'json');
    }

    /**
     * Deserializes the given entity
     *
     * @param EntityVersion $entityVersion
     * @return IVersionable $entity
     */
    public function deserialize(EntityVersion $entityVersion)
    {
        return $this->serializer->deserialize($entityVersion->getData(), $entityVersion->getSourceClass(), 'json');
    }
}
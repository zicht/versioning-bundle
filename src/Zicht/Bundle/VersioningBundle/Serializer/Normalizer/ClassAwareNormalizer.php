<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Serializer\Normalizer;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\OneToMany;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Zicht\Bundle\VersioningBundle\Entity\IVersionable;

/**
 * Class ClassAwareNormalizer
 * Normalizer to (de)normalize an object, but storing the class information for it's one-to-many relations as well
 *
 * @package Zicht\Bundle\VersioningBundle\Serializer\Normalizer
 */
class ClassAwareNormalizer extends ObjectNormalizer
{
    /** @var AnnotationReader */
    private $reader;

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof IVersionable && parent::supportsNormalization($data, $format);
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $data = parent::normalize($object, $format, $context);
        $data['__class__'] = get_class($object);
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
//        return array_key_exists('__class__', $data) && parent::supportsDenormalization($data, $type, $format);
        return parent::supportsDenormalization($data, $type, $format);
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (array_key_exists('__class__', $data)) {
            $class = $data['__class__'];
        }

        $_reflectionClass = new \ReflectionClass($class);

        $oneToManyMap = [];

        foreach ($_reflectionClass->getProperties() as $prop) {

            $metadata = $this->reader->getPropertyAnnotations($prop);

            foreach ($metadata as $m) {
                if ($m instanceof OneToMany) {
                    $this->ignoredAttributes[] = $prop->name;

                    $oneToManyMap[$prop->name] = [];

                    foreach ($data[$prop->name] as $ci) {
                        $oneToManyMap[$prop->name][] = $this->denormalize($ci, null, $format, $context);
                    }
                }
            }
        }

        try {
            $object = parent::denormalize($data, $class, $format, $context);
        } catch (ContextErrorException $e) {
            throw new \Exception(sprintf('Denormalisation of class %s failed. Please check your setters and getters if they match the class properties', $class), $e->getCode(), $e);
        }

        foreach ($oneToManyMap as $property => $otm) {
            $this->propertyAccessor->setValue($object, $property, $otm);
        }

        return $object;
    }

    public function setAnnotationReader($doctrine)
    {
        $this->reader = $doctrine;
    }
}
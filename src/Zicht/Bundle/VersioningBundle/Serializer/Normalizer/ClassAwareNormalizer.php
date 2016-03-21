<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Serializer\Normalizer;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\OneToMany;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Zicht\Bundle\VersioningBundle\Entity\VersionableInterface;

/**
 * Class ClassAwareNormalizer
 * Normalizer to (de)normalize an object, but storing the class information for it's one-to-many relations as well
 *
 * @package Zicht\Bundle\VersioningBundle\Serializer\Normalizer
 */
class ClassAwareNormalizer extends ObjectNormalizer
{
    public function __construct(EntityManager $em)
    {
        parent::__construct();

        $this->em = $em;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof VersionableInterface && parent::supportsNormalization($data, $format);
    }


    protected function getAllowedAttributes($classOrObject, array $context, $attributesAsString = false)
    {
        return array_keys($this->em->getClassMetadata(is_object($classOrObject) ? get_class($classOrObject) : $classOrObject)->reflFields);
    }


    /**
     * {@inheritDoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        if ($data = parent::normalize($object, $format, $context)) {
            if (is_array($data)) {
                $data['__class__'] = get_class($object);
                unset($data['id']);
            }
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return parent::supportsDenormalization($data, $type, $format);
    }

    protected function prepareForDenormalization($data)
    {
        $values = parent::prepareForDenormalization($data);

        if (array_key_exists('__class__', $data)) {
            $class = $data['__class__'];
            $assocationNames = $this->em->getClassMetadata($class)->getAssociationNames();
            var_dump($assocationNames);
            var_dump(array_keys($values));
            foreach ($values as $keyName => $assocations) {
                if (in_array($keyName,  $assocationNames)) {
                    if (is_array($assocations)) {
                        foreach ($assocations as $idx => $value) {
                            if (isset($value['__class__'])) {
                                $values[$keyName][$idx] = $this->denormalize($value, $value['__class__']);
                            }
                        }
                    }
                }
            }
        }
        return $values;
    }


    /**
     * {@inheritDoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (array_key_exists('__class__', $data)) {
            $class = $data['__class__'];
        }

        try {
            $object = parent::denormalize($data, $class, $format, $context);
        } catch (ContextErrorException $e) {
            throw new \Exception(sprintf('Denormalisation of class %s failed. Please check your setters and getters if they match the class properties', $class), $e->getCode(), $e);
        }

        return $object;
    }
}
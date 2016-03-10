<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Serializer\Normalizer;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
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

        //-----
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

        $allowedAttributes = $this->getAllowedAttributes($class, $context, true);
        $normalizedData = $this->prepareForDenormalization($data);

        $reflectionClass = new \ReflectionClass($class);

        $contentitems = [];

        if ($reflectionClass->name == 'Zicht\Bundle\VersioningBundle\Entity\Test\Page') {
            foreach($data['contentItems'] as $ci) {
                $contentitems[] = $this->denormalize($ci, null, $format, $context);
            }
        }

        $object = $this->instantiateObject($normalizedData, $class, $context, $reflectionClass, $allowedAttributes);


        foreach($contentitems as $cix) {
            $object->addContentItem($cix);
        }


        if ($reflectionClass->name == 'Zicht\Bundle\VersioningBundle\Entity\Test\Page') {
            $this->ignoredAttributes[] = 'contentItems';
        }

        foreach ($normalizedData as $attribute => $value) {
            if ($this->nameConverter) {
                $attribute = $this->nameConverter->denormalize($attribute);
            }

            $allowed = $allowedAttributes === false || in_array($attribute, $allowedAttributes);
            $ignored = in_array($attribute, $this->ignoredAttributes);

            if ($allowed && !$ignored) {
                try {
                    $this->propertyAccessor->setValue($object, $attribute, $value);
                } catch (NoSuchPropertyException $exception) {
                    // Properties not found are ignored
                }
            }
        }

        return $object;
    }
}
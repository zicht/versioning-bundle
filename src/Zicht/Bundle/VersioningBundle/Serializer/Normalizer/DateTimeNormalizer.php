<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for DateTime objects
 *
 * @package Zicht\Bundle\VersioningBundle\Serializer\Normalizer
 */
class DateTimeNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof \DateTime;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return ['__class__' => 'DateTime', 'rfc3339' => $object->format(\DateTime::RFC3339)];
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && isset($data['__class__']) && $data['__class__'] === 'DateTime' && isset($data['rfc3339']);
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        return \DateTime::createFromFormat(\DateTime::RFC3339, $data['rfc3339']);
    }
}
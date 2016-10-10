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
     * Convert the object to an array
     */
    const STRATEGY_ARRAY = 'array';

    /**
     * Format the object as string
     */
    const STRATEGY_STRING = 'string';

    /**
     * Constructor
     *
     * @param string $strategy
     */
    public function __construct($strategy = self::STRATEGY_ARRAY)
    {
        $this->strategy = $strategy;
    }

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
        if ($this->strategy === self::STRATEGY_STRING) {
            return $object->format(\DateTime::RFC3339);
        }
        return ['__class__' => 'DateTime', 'rfc3339' => $object->format(\DateTime::RFC3339)];
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        if ($this->strategy !== self::STRATEGY_ARRAY) {
            // We could "try" to parse the data if it is a string, but in that case we do not know
            // for sure that the normalization was also done with this class. So let's not support this.
            return false;
        }

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

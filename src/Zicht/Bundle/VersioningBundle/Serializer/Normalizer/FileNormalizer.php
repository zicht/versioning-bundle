<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Serializer\Normalizer;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer for File objects
 */
class FileNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof File;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        /** @var File $object */
        return ['__class__' => File::class, 'path' => $object->getPath(), 'filename' => $object->getFilename()];
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && isset($data['__class__']) && $data['__class__'] === File::class && isset($data['path']) && isset($data['filename']);
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        return new File(sprintf('%s/%s', $data['path'], $data['filename']), false);
    }
}

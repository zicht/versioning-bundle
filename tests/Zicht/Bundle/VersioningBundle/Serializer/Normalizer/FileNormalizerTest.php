<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Serializer\Normalizer;

use Symfony\Component\HttpFoundation\File\File;

/**
 * Class FileNormalizerTest
 */
class FileNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test normalization and de-normalization
     */
    public function testNormalization()
    {
        $file = new File('/path/filename', false);
        $normalizer = new FileNormalizer();
        $data = $normalizer->normalize($file);
        $this->assertTrue($normalizer->supportsDenormalization($data, File::class));

        /** @var File $denormalized */
        $denormalized = $normalizer->denormalize($data, File::class);
        $this->assertInstanceOf(File::class, $denormalized);
        $this->assertEquals($file->getPath(), $denormalized->getPath());
        $this->assertEquals($file->getFilename(), $denormalized->getFilename());
    }

    /**
     * Test support for normalization
     */
    public function testSupport()
    {
        $file = new File('/path/filename', false);
        $normalizer = new FileNormalizer();
        $this->assertTrue($normalizer->supportsNormalization($file, get_class($file)));
    }
}

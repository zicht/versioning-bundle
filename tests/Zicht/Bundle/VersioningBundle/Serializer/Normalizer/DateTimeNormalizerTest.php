<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Serializer\Normalizer;

class DateTimeNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalization()
    {
        $n = new DateTimeNormalizer();
        $d = new \DateTime();
        $norm = $n->normalize($d);
        $this->assertTrue($n->supportsDenormalization($norm, 'DateTime'));
        $this->assertEquals($d, $n->denormalize($norm, 'DateTime'));
    }


    public function testSupport()
    {
        $n = new DateTimeNormalizer();
        $this->assertTrue($n->supportsNormalization(new \DateTime(), 'DateTime'));
    }
}
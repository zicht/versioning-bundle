<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Serializer;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\TestAssets\Entity;
use Zicht\Bundle\VersioningBundle\TestAssets\OtherEntity;

class SerializerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->metadata = [];
        $this->entities = [];

        $this->meta = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Mapping\MetadataFactory')->disableOriginalConstructor()->setMethods(['hasMetadataFor', 'getMetadataFor'])->getMock();
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $this->em->expects($this->any())->method('getMetadataFactory')->will($this->returnValue($this->meta));
    }


    public function testSerializer()
    {
        $this->expectMetadataFor(Entity::class, new ClassMetadataInfo(Entity::class));
        $serialized = (new Serializer($this->em))->serialize(new Entity());
        $this->assertTrue(is_string($serialized));
        $decoded = json_decode($serialized, true);
        $this->assertEquals(Entity::class, $decoded['__class__']);
    }

    public function testDeserializer()
    {
        $this->expectMetadataFor(Entity::class, new ClassMetadataInfo(Entity::class));
        $v = new EntityVersion();
        $v->setSourceClass(Entity::class);
        $v->setData(json_encode(['__class__' => Entity::class]));
        $this->assertInstanceOf(Entity::class, (new Serializer($this->em))->deserialize($v));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDeserializerThrowsExceptionIfSourceClassNotSet()
    {
        $this->expectMetadataFor(Entity::class, new ClassMetadataInfo(Entity::class));
        $v = new EntityVersion();
        (new Serializer($this->em))->deserialize($v);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDeserializerThrowsExceptionIfSourceClassDoesNotMatchObjectClass()
    {
        $this->expectMetadataFor(Entity::class, new ClassMetadataInfo(Entity::class));
        $v = new EntityVersion();
        $v->setSourceClass(Entity::class);
        (new Serializer($this->em))->deserialize($v, new OtherEntity());
    }

    public function testDeserializingIntoExistingObject()
    {
        $meta = $this->getMock(ClassMetadataInfo::class, ['getFieldNames'], [Entity::class]);
        $meta->expects($this->any())->method('getFieldNames')->will($this->returnValue(['bool']));
        $this->expectMetadataFor(Entity::class, $meta);
        $o = new Entity();
        $o->setBool(false);
        $v = new EntityVersion();
        $v->setSourceClass(Entity::class);
        $v->setData(json_encode(['__class__' => Entity::class, 'bool' => true]));
        $this->assertInstanceOf(Entity::class, (new Serializer($this->em))->deserialize($v, $o));

        $this->assertEquals(true, $o->getBool());
    }


    protected function expectMetadataFor($class, $meta)
    {
        $this->metadata[$class] = $meta;

        $this->meta->expects($this->any())->method('hasMetadataFor')->will($this->returnCallback(function($class) {
            return array_key_exists($class, $this->metadata);
        }));
        $this->meta->expects($this->any())->method('getMetadataFor')->will($this->returnCallback(function($class) {
            return $this->metadata[$class];
        }));
    }

    protected function expectToBeFound($class, $int, $entity)
    {
        $this->entities[$class][$int] = $entity;

        $this->em->expects($this->any())->method('find')->will($this->returnCallback(function($class, $id) {
            return isset($this->entities[$class][$id]) ? $this->entities[$class][$id] : null;
        }));
    }


    protected function expectToBeReferenced($class, $int, $entity)
    {
        $this->proxyEntities[$class][$int] = $entity;

        $this->em->expects($this->any())->method('getReference')->will($this->returnCallback(function($class, $idArray) {
            list($id) = array_values($idArray);
            return isset($this->proxyEntities[$class][$id]) ? $this->proxyEntities[$class][$id] : null;
        }));
    }
}

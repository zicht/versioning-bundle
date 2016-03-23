<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Serializer\Normalizer;


use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Serializer;

class Entity
{
    protected $bool = null;

    protected $object = null;

    private $other;
    private $others;

    private $priv = 1;

    public function setObject($entity)
    {
        $this->object = $entity;
    }


    public function getObject()
    {
        return $this->object;
    }

    public function setBool($bool)
    {
        $this->bool = $bool;
    }


    public function getBool()
    {
        return $this->bool;
    }


    public function setOther($o)
    {
        $this->other = $o;
    }

    public function getOther()
    {
        return $this->other;
    }


    public function addOther($o)
    {
        return $this->others[]= $o;
    }

    public function getOthers()
    {
        return $this->others;
    }

    public function setOthers($others)
    {
        $this->others = $others;
    }

    public function getPrivateValue()
    {
        return $this->priv;
    }
}

class OtherEntity
{
    private $id;
    private $name;

    public function __construct($id = null, $name = 'otha!')
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}

class DoctrineEntityNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineEntityNormalizer
     */
    protected $normalizer;
    protected $meta;
    protected $em;

    public function setUp()
    {
        $this->metadata = [];
        $this->entities = [];

        $this->meta = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Mapping\MetadataFactory')->disableOriginalConstructor()->setMethods(['hasMetadataFor', 'getMetadataFor'])->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $this->em->expects($this->any())->method('getMetadataFactory')->will($this->returnValue($this->meta));

        $this->normalizer = new DoctrineEntityNormalizer($this->em);
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::supportsNormalization
     */
    public function testSupportsNormalizationForEntityWithMetaData()
    {
        $this->meta->expects($this->once())->method('hasMetadataFor')->with(Entity::class)->will($this->returnValue(true));
        $this->assertTrue($this->normalizer->supportsNormalization(new Entity()));
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::supportsNormalization
     */
    public function testDoesNotSupportNormalizationForEntityWithoutMetaData()
    {
        $this->meta->expects($this->once())->method('hasMetadataFor')->with(Entity::class)->will($this->returnValue(false));
        $this->assertFalse($this->normalizer->supportsNormalization(new Entity()));
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::supportsDenormalization
     */
    public function testSupportsDenormalizationForEntityWithMetaData()
    {
        $this->meta->expects($this->once())->method('hasMetadataFor')->with(Entity::class)->will($this->returnValue(true));
        $this->assertTrue($this->normalizer->supportsDenormalization(['__class__' => Entity::class], Entity::class));
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::supportsDenormalization
     */
    public function testDoesNotSupportDenormalizationForEntityWithoutMetaData()
    {
        $this->meta->expects($this->any())->method('hasMetadataFor')->with(Entity::class)->will($this->returnValue(false));
        $this->assertFalse($this->normalizer->supportsDenormalization(['__class__' => Entity::class], Entity::class));
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::denormalize
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::getClassMetaData
     */
    public function testPropertyDenormalizationForUnmanagedProperties()
    {
        $this->expectMetadataFor(Entity::class, new ClassMetadataInfo(Entity::class));

        $o = $this->normalizer->denormalize(['__class__' => Entity::class, 'bool' => true], Entity::class);
        $this->assertNull($o->getBool());
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::denormalize
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::getClassMetaData
     */
    public function testPropertyDenormalizationForManagedProperties()
    {
        $meta = $this->getMock(ClassMetadataInfo::class, ['getFieldNames'], [Entity::class]);
        $meta->expects($this->any())->method('getFieldNames')->will($this->returnValue(['bool']));
        $this->expectMetadataFor(Entity::class, $meta);

        $o = $this->normalizer->denormalize(['__class__' => Entity::class, 'bool' => true], Entity::class);
        $this->assertTrue($o->getBool());
    }
    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::denormalize
     */
    public function testPropertyDenormalizationForManagedPrivateProperties()
    {
        $meta = $this->getMock(ClassMetadataInfo::class, ['getFieldNames'], [Entity::class]);
        $meta->expects($this->any())->method('getFieldNames')->will($this->returnValue(['priv']));
        $meta->fieldMappings['priv']['declared'] = Entity::class;
        $meta->fieldMappings['priv']['fieldName'] = 'priv';
        $this->expectMetadataFor(Entity::class, $meta);

        $value = rand(1, 1000);
        $o = $this->normalizer->denormalize(['__class__' => Entity::class, 'priv' => $value], Entity::class);
        $this->assertEquals($value, $o->getPrivateValue());
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::normalize
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::getClassMetaData
     */
    public function testPropertyNormalizationForManagedProperties()
    {
        $meta = $this->getMock(ClassMetadataInfo::class, ['getFieldNames'], [Entity::class]);
        $meta->expects($this->any())->method('getFieldNames')->will($this->returnValue(['bool']));
        $this->expectMetadataFor(Entity::class, $meta);

        $o = new Entity();
        foreach ([true, false] as $boolVal) {
            $o->setBool($boolVal);
            $norm = $this->normalizer->normalize($o);
            $this->assertEquals($boolVal, $norm['bool']);
        }
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::normalize
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::getClassMetaData
     */
    public function testPropertyNormalizationForUnmanagedProperties()
    {
        $this->expectMetadataFor(Entity::class, new ClassMetadataInfo(Entity::class));

        $o = new Entity();
        $o->setBool(true);
        $norm = $this->normalizer->normalize($o);
        $this->assertArrayNotHasKey('bool', $norm);
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::normalize
     */
    public function testPropertyNormalizationForComplexPropertiesWillNormalizeValue()
    {
        $meta = $this->getMock(ClassMetadataInfo::class, ['getFieldNames'], [Entity::class]);
        $meta->expects($this->any())->method('getFieldNames')->will($this->returnValue(['object']));

        $this->expectMetadataFor(Entity::class, $meta);

        $serializer = $this->getMockBuilder(Serializer::class)->disableOriginalConstructor()->setMethods(['normalize'])->getMock();
        $this->normalizer->setSerializer($serializer);

        $obj = new \stdClass;
        $serializer->expects($this->once())->method('normalize')->with($obj)->will($this->returnValue(1234));

        $o = new Entity();
        $o->setObject($obj);
        $norm = $this->normalizer->normalize($o);

        $this->assertEquals(1234, $norm['object']);
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::denormalize
     */
    public function testPropertyDenormalizationForComplexPropertiesWillDenormalizeValue()
    {
        $meta = $this->getMock(ClassMetadataInfo::class, ['getFieldNames'], [Entity::class]);
        $meta->expects($this->any())->method('getFieldNames')->will($this->returnValue(['object']));

        $this->expectMetadataFor(Entity::class, $meta);

        $serializer = $this->getMockBuilder(Serializer::class)->disableOriginalConstructor()->setMethods(['denormalize'])->getMock();
        $this->normalizer->setSerializer($serializer);

        $obj = new \stdClass;
        $serializer->expects($this->once())->method('denormalize')->with(['__class__' => 'foo'])->will($this->returnValue($obj));

        $e = $this->normalizer->denormalize([
                '__class__' => Entity::class,
                'object' => ['__class__' => 'foo']
            ],
            Entity::class
        );

        $this->assertEquals($obj, $e->getObject());
        $this->assertSame($obj, $e->getObject());
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::denormalize
     */
    public function testPropertyDenormalizationForNonExistingPropertiesWillDefaultToNull()
    {
        $meta = $this->getMock(ClassMetadataInfo::class, ['getFieldNames'], [Entity::class]);
        $meta->expects($this->any())->method('getFieldNames')->will($this->returnValue(['object']));

        $this->expectMetadataFor(Entity::class, $meta);

        $e = new Entity();
        $e->setObject(new \stdClass());

        $this->normalizer->denormalize(['__class__' => Entity::class], Entity::class, null, ['object' => $e]);
        $this->assertEquals(null, $e->getObject());
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::normalize
     */
    public function testPropertyNormalizationForManyToOneAssociation()
    {
        $meta = $this->getMock(ClassMetadataInfo::class, ['getAssociationNames'], [Entity::class]);
        $meta->expects($this->any())->method('getAssociationNames')->will($this->returnValue(['other']));
        $meta->associationMappings['other'] = [
            'type' => ClassMetadataInfo::MANY_TO_ONE
        ];

        $this->expectMetadataFor(Entity::class, $meta);
        $this->expectMetadataFor(OtherEntity::class, new ClassMetadataInfo(OtherEntity::class));

        $o = new Entity();
        $o->setOther(new OtherEntity(1234));
        $norm = $this->normalizer->normalize($o);

        $this->assertEquals(['__class__' => OtherEntity::class, 'id' => 1234], $norm['other']);
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::denormalize
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::__construct
     */
    public function testPropertyDenormalizationForManyToOneAssociation()
    {
        $meta = $this->getMock(ClassMetadataInfo::class, ['getAssociationNames'], [Entity::class]);
        $meta->expects($this->any())->method('getAssociationNames')->will($this->returnValue(['other']));
        $meta->associationMappings['other'] = [
            'type' => ClassMetadataInfo::MANY_TO_ONE
        ];
        $this->expectToBeFound(OtherEntity::class, 1234, new OtherEntity(1234, 'from em'));

        $this->expectMetadataFor(Entity::class, $meta);
        $this->expectMetadataFor(OtherEntity::class, new ClassMetadataInfo(OtherEntity::class));

        $data = [
            '__class__' => Entity::class,
            'other' => ['__class__' => OtherEntity::class, 'id' => 1234]
        ];
        $entity = $this->normalizer->denormalize($data, Entity::class);

        $this->assertInstanceOf(OtherEntity::class, $entity->getOther());
        $this->assertEquals(1234, $entity->getOther()->getId());
        $this->assertEquals('from em', $entity->getOther()->getName());
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::normalize
     */
    public function testPropertyNormalizationForManyToOneAssociationWithNullValue()
    {
        $meta = $this->getMock(ClassMetadataInfo::class, ['getAssociationNames'], [Entity::class]);
        $meta->expects($this->any())->method('getAssociationNames')->will($this->returnValue(['other']));
        $meta->associationMappings['other'] = [
            'type' => ClassMetadataInfo::MANY_TO_ONE
        ];

        $this->expectMetadataFor(Entity::class, $meta);

        $o = new Entity();
        $norm = $this->normalizer->normalize($o);

        $this->assertEquals(null, $norm['other']);
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::denormalize
     */
    public function testPropertyDenormalizationForManyToOneAssociationWithNullValue()
    {
        $meta = $this->getMock(ClassMetadataInfo::class, ['getAssociationNames'], [Entity::class]);
        $meta->expects($this->any())->method('getAssociationNames')->will($this->returnValue(['other']));
        $meta->associationMappings['other'] = [
            'type' => ClassMetadataInfo::MANY_TO_ONE
        ];

        $this->expectMetadataFor(Entity::class, $meta);

        $o = new Entity();
        $o->setOther(new OtherEntity(0));
        $norm = $this->normalizer->denormalize(['__class__' => Entity::class], Entity::class);
        $this->assertEquals(null, $norm->getOther());
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::normalize
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::serializeReferencedAssociation
     */
    public function testPropertyNormalizationForManyToManyAssociation()
    {
        $meta = $this->getMock(ClassMetadataInfo::class, ['getAssociationNames'], [Entity::class]);
        $meta->expects($this->any())->method('getAssociationNames')->will($this->returnValue(['others']));
        $meta->associationMappings['others'] = [
            'type' => ClassMetadataInfo::MANY_TO_MANY
        ];

        $this->expectMetadataFor(Entity::class, $meta);
        $this->expectMetadataFor(OtherEntity::class, new ClassMetadataInfo(OtherEntity::class));

        $o = new Entity();
        $o->setOthers([new OtherEntity(1234), new OtherEntity(5678)]);
        $norm = $this->normalizer->normalize($o);

        $this->assertEquals(
            [['__class__' => OtherEntity::class, 'id' => 1234], ['__class__' => OtherEntity::class, 'id' => 5678]],
            $norm['others']
        );
    }
 /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::denormalize
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::resolveReferencedAssociation
     */
    public function testPropertyDenormalizationForManyToManyAssociation()
    {
        $meta = $this->getMock(ClassMetadataInfo::class, ['getAssociationNames'], [Entity::class]);
        $meta->expects($this->any())->method('getAssociationNames')->will($this->returnValue(['others']));
        $meta->associationMappings['others'] = [
            'type' => ClassMetadataInfo::MANY_TO_MANY
        ];

        $this->expectMetadataFor(Entity::class, $meta);
        $this->expectMetadataFor(OtherEntity::class, new ClassMetadataInfo(OtherEntity::class));

        $this->expectToBeFound(OtherEntity::class, 1234, new OtherEntity(1234, 'from em'));
        $this->expectToBeFound(OtherEntity::class, 5678, new OtherEntity(5678, 'from em'));

        $o = $this->normalizer->denormalize([
            '__class__' => Entity::class,
            'others' => [['__class__' => OtherEntity::class, 'id' => 1234], ['__class__' => OtherEntity::class, 'id' => 5678]]
        ], Entity::class);

        $ids = [];
        foreach ($o->getOthers() as $other) {
            $this->assertInstanceOf(OtherEntity::class, $other);
            $this->assertEquals('from em', $other->getName());
            $ids[]= $other->getId();
        }
        $this->assertEquals([1234, 5678], $ids);
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::normalize
     */
    public function testPropertyNormalizationForOneToManyAssociation()
    {
        $meta1 = $this->getMock(ClassMetadataInfo::class, ['getAssociationNames'], [Entity::class]);
        $meta1->expects($this->any())->method('getAssociationNames')->will($this->returnValue(['others']));
        $meta1->associationMappings['others'] = [
            'type' => ClassMetadataInfo::ONE_TO_MANY
        ];

        $this->expectMetadataFor(Entity::class, $meta1);

        $meta2 = $this->getMock(ClassMetadataInfo::class, ['getFieldNames'], [OtherEntity::class]);
        $meta2->expects($this->any())->method('getFieldNames')->will($this->returnValue(['name']));
        $this->expectMetadataFor(OtherEntity::class, $meta2);

        $o = new Entity();
        $o->setOthers([new OtherEntity(1234, 'I am nr 1'), new OtherEntity(5678, 'Who does number two work for!')]);
        $norm = $this->normalizer->normalize($o);

        $this->assertEquals(
            [['__class__' => OtherEntity::class, 'name' => 'I am nr 1'], ['__class__' => OtherEntity::class, 'name' => 'Who does number two work for!']],
            $norm['others']
        );
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::denormalize
     */
    public function testPropertyDenormalizationForOneToManyAssociation()
    {
        $meta1 = $this->getMock(ClassMetadataInfo::class, ['getAssociationNames'], [Entity::class]);
        $meta1->expects($this->any())->method('getAssociationNames')->will($this->returnValue(['others']));
        $meta1->associationMappings['others'] = [
            'type' => ClassMetadataInfo::ONE_TO_MANY
        ];

        $this->expectMetadataFor(Entity::class, $meta1);

        $meta2 = $this->getMock(ClassMetadataInfo::class, ['getFieldNames'], [OtherEntity::class]);
        $meta2->expects($this->any())->method('getFieldNames')->will($this->returnValue(['name']));
        $this->expectMetadataFor(OtherEntity::class, $meta2);

        $o = $this->normalizer->denormalize(
            ['__class__' => Entity::class, 'others' => [['__class__' => OtherEntity::class, 'name' => 'I am nr 1'], ['__class__' => OtherEntity::class, 'name' => 'Who does number two work for!']]],
            Entity::class
        );

        $this->assertEquals(2, count($o->getOthers()));
        $this->assertEquals('I am nr 1', $o->getOthers()[0]->getName());
        $this->assertEquals('Who does number two work for!', $o->getOthers()[1]->getName());
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::normalize
     * @expectedException UnexpectedValueException
     */
    public function testPropertyNormalizationForUnknownAssociationWillThrowException()
    {
        $meta1 = $this->getMock(ClassMetadataInfo::class, ['getAssociationNames'], [Entity::class]);
        $meta1->expects($this->any())->method('getAssociationNames')->will($this->returnValue(['others']));
        $meta1->associationMappings['others'] = [
            'type' => 'bogus'
        ];

        $this->expectMetadataFor(Entity::class, $meta1);
        $o = new Entity();
        $this->normalizer->normalize($o, null, []);
    }

    /**
     * @covers Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer::denormalize
     * @expectedException UnexpectedValueException
     */
    public function testPropertyDenormalizationForUnknownAssociationWillThrowException()
    {
        $meta1 = $this->getMock(ClassMetadataInfo::class, ['getAssociationNames'], [Entity::class]);
        $meta1->expects($this->any())->method('getAssociationNames')->will($this->returnValue(['others']));
        $meta1->associationMappings['others'] = [
            'type' => 'bogus'
        ];

        $this->expectMetadataFor(Entity::class, $meta1);
        $this->normalizer->denormalize(['__class__' => Entity::class, 'others' => [1, 2, 3]], Entity::class);
    }


    private function expectMetadataFor($class, $meta)
    {
        $this->metadata[$class] = $meta;

        $this->meta->expects($this->any())->method('hasMetadataFor')->will($this->returnCallback(function($class) {
            return array_key_exists($class, $this->metadata);
        }));
        $this->meta->expects($this->any())->method('getMetadataFor')->will($this->returnCallback(function($class) {
            return $this->metadata[$class];
        }));
    }

    private function expectToBeFound($class, $int, $entity)
    {
        $this->entities[$class][$int] = $entity;

        $this->em->expects($this->any())->method('find')->will($this->returnCallback(function($class, $id) {
            return isset($this->entities[$class][$id]) ? $this->entities[$class][$id] : null;
        }));
    }
}
<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Manager;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionStorageInterface;
use Zicht\Bundle\VersioningBundle\Serializer\Serializer;
use Zicht\Bundle\VersioningBundle\TestAssets\Entity;

/**
 * @covers Zicht\Bundle\VersioningBundle\Manager\VersioningManager
 */
class VersioningManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var VersioningManager */
    protected $manager;

    /** @var Serializer */
    protected $serializer;

    /**
     * @var EntityVersionStorageInterface
     */
    protected $storage;

    public function setUp()
    {
        $this->serializer = $this->getMockBuilder(Serializer::class)->disableOriginalConstructor()->getMock();
        $this->storage = $this->getMockBuilder(EntityVersionStorageInterface::class)->getMock();

        $this->manager = new VersioningManager($this->serializer, $this->storage);
    }


    public function testGetVersionCount()
    {
        $entity = new Entity();
        $this->storage->expects($this->once())->method('findVersions')->with($entity)->will($this->returnValue([new EntityVersion(), new EntityVersion()]));
        $this->assertEquals(2, $this->manager->getVersionCount($entity));
    }


    public function testFindActiveVersionDelegatesToStorage()
    {
        $entity = new Entity();
        $this->storage->expects($this->once())->method('findActiveVersion')->with($entity)->will($this->returnValue($ret = new EntityVersion()));
        $this->assertEquals($ret, $this->manager->findActiveVersion($entity));
    }

    public function testFindVersionDelegatesToStorage()
    {
        $entity = new Entity();
        $n = rand(1, 100);
        $this->storage->expects($this->once())->method('findVersion')->with($entity, $n)->will($this->returnValue($ret = new EntityVersion()));
        $this->assertEquals($ret, $this->manager->findVersion($entity, $n));
    }

    public function testFindVersionsDelegatesToStorage()
    {
        $entity = new Entity();
        $n = rand(1, 100);
        $this->storage->expects($this->once())->method('findVersions')->with($entity)->will($this->returnValue($ret = [new EntityVersion(), new EntityVersion()]));
        $this->assertEquals($ret, $this->manager->findVersions($entity));
    }


    public function testCreateEntityVersion()
    {
        $entity = new Entity();
        $n = rand(1, 100);

        $v = rand(100, 999);
        $this->serializer->expects($this->once())->method('serialize')->with($entity)->will($this->returnValue('serialized data'));

        $this->storage->expects($this->once())->method('getNextVersionNumber')->with($entity)->will($this->returnValue($v));

        $version = $this->manager->createEntityVersion($entity, ['foo' => 'bar'], $n);

        $this->assertEquals(get_class($entity), $version->getSourceClass());
        $this->assertEquals($entity->getId(), $version->getOriginalId());
        $this->assertEquals('serialized data', $version->getData());
        $this->assertEquals(['foo' => 'bar'], $version->getChangeset());
        $this->assertEquals($n, $version->getBasedOnVersion());
        $this->assertEquals($v, $version->getVersionNumber());
    }

    public function testCreateEntityVersionBaseVersionIsOptional()
    {
        $entity = new Entity();
        $version = $this->manager->createEntityVersion($entity, ['foo' => 'bar']);
        $this->assertNull($version->getBasedOnVersion());
    }


    public function testUpdateEntityVersion()
    {
        $version = new EntityVersion();
        $version->setVersionNumber(1234);
        $version->setSourceClass('foo');
        $version->setOriginalId(5678);

        $entity = new Entity();

        $this->serializer->expects($this->once())->method('serialize')->with($entity)->will($this->returnValue('new serialized data'));
        $this->storage->expects($this->once())->method('findVersion')->with($entity, $version->getVersionNumber())->will($this->returnValue($version));
        $this->storage->expects($this->never())->method('getNextVersionNumber')->with($entity);

        $version = $this->manager->updateEntityVersion($entity, ['foo' => 'bar'], $version->getVersionNumber());

        $this->assertEquals(1234, $version->getVersionNumber());
        $this->assertEquals('foo', $version->getSourceClass());
        $this->assertEquals(5678, $version->getOriginalId());
        $this->assertEquals('new serialized data', $version->getData());
    }

    /**
     * @expectedException \Zicht\Bundle\VersioningBundle\Exception\VersionNotFoundException
     */
    public function testUpdateEntityVersionThrowsExceptionIfVersionNotFound()
    {
        $this->manager->updateEntityVersion(new Entity(), ['foo' => 'bar'], 0);
    }


    public function testCreateVersionWillUpdateAffectedVersions()
    {
        $this->assertEquals(0, count($this->manager->getAffectedVersions()));
        $this->manager->createEntityVersion(new Entity(), []);
        $this->assertEquals(1, count($this->manager->getAffectedVersions()));
    }

    public function testUpdateVersionWillUpdateAffectedVersions()
    {
        $this->storage->expects($this->once())->method('findVersion')->will($this->returnValue(new EntityVersion()));
        $this->assertEquals(0, count($this->manager->getAffectedVersions()));
        $this->manager->updateEntityVersion(new Entity(), [], 1);
        $this->assertEquals(1, count($this->manager->getAffectedVersions()));
    }


    public function testSetVersionToLoadWillLoadVersion()
    {
        $entity = new Entity();
        $version = new EntityVersion();

        $this->manager->setVersionToLoad(Entity::class, $entity->getId(), 5678);
        $this->storage->expects($this->once())->method('findVersion')->with($entity, 5678)->will($this->returnValue($version));
        $this->serializer->expects($this->once())->method('deserialize')->with($version, $entity);

        $this->manager->loadVersion($entity);
    }


    public function testGetVersionOperationWillBeNewWithoutBaseVersionInitially()
    {
        $entity = new Entity();
        $this->storage->expects($this->once())->method('findActiveVersion')->with($entity)->will($this->returnValue(null));
        $this->assertEquals([VersioningManager::VERSION_OPERATION_NEW, null], $this->manager->getVersionOperation($entity));
    }

    public function testGetVersionOperationWillBeNewWithBaseVersionIfActive()
    {
        $entity = new Entity();
        $version = new EntityVersion();
        $version->setVersionNumber(rand(1000, 9999));
        $this->storage->expects($this->once())->method('findActiveVersion')->with($entity)->will($this->returnValue($version));
        $this->assertEquals([VersioningManager::VERSION_OPERATION_NEW, $version->getVersionNumber()], $this->manager->getVersionOperation($entity));
    }

    public function testGetVersionOperationWillBeBasedOnVersionToLoadIfAvailable()
    {
        $entity = new Entity();
        $version = new EntityVersion();
        $version->setVersionNumber(rand(1000, 9999));
        $this->manager->setVersionToLoad(Entity::class, $entity->getId(), $version->getVersionNumber());
        $this->storage->expects($this->never())->method('findActiveVersion')->with($entity)->will($this->returnValue($version));
        $this->assertEquals([VersioningManager::VERSION_OPERATION_NEW, $version->getVersionNumber()], $this->manager->getVersionOperation($entity));
    }


    public function testSetVersionOperation()
    {
        $entity = new Entity();
        $this->manager->setVersionOperation($entity, VersioningManager::VERSION_OPERATION_ACTIVATE, 5678);
        $this->assertEquals([VersioningManager::VERSION_OPERATION_ACTIVATE, 5678], $this->manager->getVersionOperation($entity));
    }
}
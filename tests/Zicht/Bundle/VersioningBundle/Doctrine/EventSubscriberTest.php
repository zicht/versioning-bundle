<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Doctrine;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Exception\UnsupportedVersionOperationException;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;
use Zicht\Bundle\VersioningBundle\TestAssets\Entity;
use Doctrine\ORM\Event;
use Zicht\Bundle\VersioningBundle\TestAssets\OtherEntity;

class EventSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var EventSubscriber */
    private $subscriber;

    protected function setUp()
    {
        $container = new Container();
        $this->manager = $this->getMockBuilder(VersioningManager::class)->disableOriginalConstructor()->setMethods(['loadVersion', 'getAffectedVersions', 'findActiveVersion', 'getVersionOperation', 'createEntityVersion', 'updateEntityVersion'])->getMock();

        $container->set('zicht_versioning.manager', $this->manager);

        $this->subscriber = new EventSubscriber($container);

        $this->uow = $this->getMockBuilder(UnitOfWork::class)->disableOriginalConstructor()->setMethods(['getScheduledEntityInsertions', 'getScheduledEntityUpdates', 'clearEntityChangeSet', 'scheduleForInsert', 'scheduleForDirtyCheck', 'scheduleForUpdate', 'getEntityChangeSet', 'computeChangeSets'])->getMock();
        $this->em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->setMethods(['getUnitOfWork', 'persist', 'flush', 'getEventManager'])->getMock();
        $this->em->expects($this->any())->method('getUnitOfWork')->will($this->returnValue($this->uow));
    }

    public function testSubscription()
    {
        $this->assertContains('onFlush', $this->subscriber->getSubscribedEvents());
        $this->assertContains('postFlush', $this->subscriber->getSubscribedEvents());
        $this->assertContains('postLoad', $this->subscriber->getSubscribedEvents());
    }


    public function testPostLoadCallsVersioningLoad()
    {
        $entity = new Entity();
        $this->manager->expects($this->once())->method('loadVersion')->with($entity);
        $event = $this->getMockBuilder(Event\LifecycleEventArgs::class)->disableOriginalConstructor()->setMethods(['getObject'])->getMock();
        $event->expects($this->once())->method('getObject')->will($this->returnValue($entity));
        $this->subscriber->postLoad($event);
    }

    public function testPostLoadIgnoresNonVersionableObjects()
    {
        $entity = new OtherEntity();
        $this->manager->expects($this->never())->method('loadVersion')->with($entity);
        $event = $this->getMockBuilder(Event\LifecycleEventArgs::class)->disableOriginalConstructor()->setMethods(['getObject'])->getMock();
        $event->expects($this->once())->method('getObject')->will($this->returnValue($entity));
        $this->subscriber->postLoad($event);
    }

    public function testOnFlushWillCreateVersionForInsert()
    {
        $entity = new Entity();
        $version = new EntityVersion();

        $event = $this->createOnFlushEventArgs();

        $this->uow->expects($this->once())->method('getScheduledEntityUpdates')->will($this->returnValue([]));
        $this->uow->expects($this->once())->method('getScheduledEntityInsertions')->will($this->returnValue([$entity]));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->with($entity)->will($this->returnValue($changeset));
        $this->uow->expects($this->once())->method('scheduleForInsert')->with($version);
        $this->manager->expects($this->never())->method('getVersionOperation');
        $this->manager->expects($this->once())->method('createEntityVersion')->with($entity, $changeset)->will($this->returnValue($version));
        $this->subscriber->onFlush($event);
    }

    public function testOnFlushWillCreateVersionForUpdateAndClearChanges()
    {
        $entity = new Entity();
        $version = new EntityVersion();

        $event = $this->createOnFlushEventArgs();

        $this->uow->expects($this->once())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->once())->method('getScheduledEntityInsertions')->will($this->returnValue([]));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->with($entity)->will($this->returnValue($changeset));
        $this->uow->expects($this->once())->method('scheduleForINsert')->with($version);
        $this->uow->expects($this->once())->method('clearEntityChangeSet')->with(spl_object_hash($entity));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue([VersioningManager::VERSION_OPERATION_NEW, null]));
        $this->manager->expects($this->once())->method('createEntityVersion')->with($entity, $changeset)->will($this->returnValue($version));
        $this->subscriber->onFlush($event);
    }


    public function testOnFlushWillCreateVersionWithBasedOnVersion()
    {
        $entity = new Entity();
        $version = new EntityVersion();

        $event = $this->createOnFlushEventArgs();

        $this->uow->expects($this->once())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->once())->method('getScheduledEntityInsertions')->will($this->returnValue([]));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->with($entity)->will($this->returnValue($changeset));
        $this->uow->expects($this->once())->method('scheduleForInsert')->with($version);
        $this->uow->expects($this->once())->method('clearEntityChangeSet')->with(spl_object_hash($entity));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue([VersioningManager::VERSION_OPERATION_NEW, 1234]));
        $this->manager->expects($this->once())->method('createEntityVersion')->with($entity, $changeset, 1234)->will($this->returnValue($version));
        $this->subscriber->onFlush($event);
    }


    public function testOnFlushWillUpdateCurrentVersionIfSpecified()
    {
        $entity = new Entity();
        $version = new EntityVersion();

        $event = $this->createOnFlushEventArgs();

        $this->uow->expects($this->once())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->once())->method('getScheduledEntityInsertions')->will($this->returnValue([]));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->with($entity)->will($this->returnValue($changeset));
        $this->uow->expects($this->once())->method('scheduleForUpdate')->with($version);
        $this->uow->expects($this->once())->method('scheduleForDirtyCheck')->with($version);
        $this->uow->expects($this->once())->method('clearEntityChangeSet')->with(spl_object_hash($entity));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue([VersioningManager::VERSION_OPERATION_UPDATE, 1234]));
        $this->manager->expects($this->once())->method('updateEntityVersion')->with($entity, $changeset, 1234)->will($this->returnValue($version));
        $this->subscriber->onFlush($event);
    }


    public function testOnFlushWillActivateCurrentVersionAsNewVersion()
    {
        $entity = new Entity();
        $version = new EntityVersion();

        $event = $this->createOnFlushEventArgs();

        $this->uow->expects($this->once())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->once())->method('getScheduledEntityInsertions')->will($this->returnValue([]));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->with($entity)->will($this->returnValue($changeset));
        $this->uow->expects($this->once())->method('scheduleForInsert')->with($version);
        $this->uow->expects($this->never())->method('clearEntityChangeSet')->with(spl_object_hash($entity));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue([VersioningManager::VERSION_OPERATION_ACTIVATE, 1234]));
        $this->manager->expects($this->once())->method('createEntityVersion')->will($this->returnValue($version));
        $this->subscriber->onFlush($event);
    }

    public function testOnFlushWillDectivateCurrentlyActiveVersion()
    {
        $entity = new Entity();
        $version = new EntityVersion();
        $version->setVersionNumber(10);
        $currentActive = new EntityVersion();
        $currentActive->setIsActive(true);
        $currentActive->setVersionNumber(1);

        $event = $this->createOnFlushEventArgs();

        $this->uow->expects($this->once())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->once())->method('getScheduledEntityInsertions')->will($this->returnValue([]));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->with($entity)->will($this->returnValue($changeset));
        $this->uow->expects($this->once())->method('scheduleForInsert')->with($version);
        $this->uow->expects($this->once())->method('scheduleForUpdate')->with($currentActive);
        $this->uow->expects($this->once())->method('scheduleForDirtyCheck')->with($currentActive);
        $this->uow->expects($this->never())->method('clearEntityChangeSet')->with(spl_object_hash($entity));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue([VersioningManager::VERSION_OPERATION_ACTIVATE, 1234]));
        $this->manager->expects($this->once())->method('createEntityVersion')->will($this->returnValue($version));
        $this->manager->expects($this->once())->method('findActiveVersion')->will($this->returnValue($currentActive));
        $this->subscriber->onFlush($event);

        $this->assertFalse($currentActive->isActive());
    }

    /**
     * @expectedException \Zicht\Bundle\VersioningBundle\Exception\UnsupportedVersionOperationException
     */
    public function testOnFlushWillThrowErrorOnUnsupportedVersionOperation()
    {
        $entity = new Entity();

        $event = $this->createOnFlushEventArgs();

        $this->uow->expects($this->once())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->once())->method('getScheduledEntityInsertions')->will($this->returnValue([]));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue(['foo', 1234]));
        $this->subscriber->onFlush($event);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createOnFlushEventArgs()
    {
        $event = $this->getMockBuilder(Event\OnFlushEventArgs::class)->disableOriginalConstructor()->setMethods(['getEntityManager'])->getMock();
        $event->expects($this->once())->method('getEntityManager')->will($this->returnValue($this->em));
        return $event;
    }


    public function testPostFlushWillSetEntityIdForAffectedVersions()
    {
        $event = $this->getMockBuilder(Event\PostFlushEventArgs::class)->disableOriginalConstructor()->setMethods(['getEntityManager'])->getMock();
        $event->expects($this->any())->method('getEntityManager')->will($this->returnValue($this->em));

        $evm = $this->getMock(EventManager::class, ['removeEventSubscriber', 'addEventSubscriber'], [], '', false);
        $this->em->expects($this->any())->method('getEventManager')->will($this->returnValue($evm));

        $versions = [
            [new Entity(), new EntityVersion()],
            [new Entity(), new EntityVersion()]
        ];

        $this->manager->expects($this->once())->method('getAffectedVersions')->will($this->returnValue($versions));

        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->exactly(count($versions)))->method('persist');
        $evm->expects($this->once())->method('removeEventSubscriber')->with($this->subscriber);
        $evm->expects($this->once())->method('addEventSubscriber')->with($this->subscriber);
        $this->subscriber->postFlush($event);

        $this->assertEquals($versions[0][0]->getId(), $versions[0][1]->getOriginalId());
    }
}
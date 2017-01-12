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
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;
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
        $this->manager = $this->getMockBuilder(VersioningManager::class)->disableOriginalConstructor()->setMethods([
            'loadVersion', 'getAffectedVersions', 'findActiveVersion', 'getVersionOperation', 'createEntityVersion', 'updateEntityVersion',
            'clear'
        ])->getMock();

        $container->set('zicht_versioning.manager', $this->manager);

        $this->subscriber = new EventSubscriber($container);

        $this->uow = $this->getMockBuilder(UnitOfWork::class)->disableOriginalConstructor()->setMethods(['getScheduledEntityInsertions', 'getScheduledEntityUpdates', 'clearEntityChangeSet', 'scheduleForInsert', 'scheduleForDirtyCheck', 'scheduleForUpdate', 'getEntityChangeSet', 'computeChangeSets'])->getMock();
        $this->em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->setMethods(['getUnitOfWork', 'persist', 'flush', 'getEventManager'])->getMock();
        $this->em->expects($this->any())->method('getUnitOfWork')->will($this->returnValue($this->uow));
    }

    public function testSubscription()
    {
        $this->assertContains('preFlush', $this->subscriber->getSubscribedEvents());
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

    public function testPreFlushWillCreateVersionForInsert()
    {
        $entity = new Entity();
        $version = new EntityVersion();

        $event = $this->createPreFlushEventArgs();

        $this->uow->expects($this->any())->method('getScheduledEntityUpdates')->will($this->returnValue([]));
        $this->uow->expects($this->any())->method('getScheduledEntityInsertions')->will($this->returnCallback(function() use($entity) {
            static $i = 0;
            if (0 === $i ++) {
                return [$entity];
            }
            return [];
        }));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->with($entity)->will($this->returnValue($changeset));
        $this->uow->expects($this->once())->method('scheduleForInsert')->with($version);
        $this->manager->expects($this->never())->method('getVersionOperation');
        $this->manager->expects($this->once())->method('createEntityVersion')->with($entity, $changeset)->will($this->returnValue($version));
        $this->subscriber->preFlush($event);
    }

    public function testPreFlushWillCreateVersionForUpdateAndClearChanges()
    {
        $entity = new Entity();
        $version = new EntityVersion();

        $event = $this->createPreFlushEventArgs();

        $this->uow->expects($this->any())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->any())->method('getScheduledEntityInsertions')->will($this->returnValue([]));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->with($entity)->will($this->returnValue($changeset));
        $this->uow->expects($this->once())->method('scheduleForInsert')->with($version);
        $this->uow->expects($this->once())->method('clearEntityChangeSet')->with(spl_object_hash($entity));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue([VersioningManager::VERSION_OPERATION_NEW, null, []]));
        $this->manager->expects($this->once())->method('createEntityVersion')->with($entity, $changeset)->will($this->returnValue($version));
        $this->subscriber->preFlush($event);
    }


    public function testPreFlushWillCreateVersionWithBasedOnVersion()
    {
        $entity = new Entity();
        $version = new EntityVersion();

        $event = $this->createPreFlushEventArgs();

        $this->uow->expects($this->any())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->any())->method('getScheduledEntityInsertions')->will($this->returnValue([]));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->with($entity)->will($this->returnValue($changeset));
        $this->uow->expects($this->once())->method('scheduleForInsert')->with($version);
        $this->uow->expects($this->once())->method('clearEntityChangeSet')->with(spl_object_hash($entity));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue([VersioningManager::VERSION_OPERATION_NEW, 1234, []]));
        $this->manager->expects($this->once())->method('createEntityVersion')->with($entity, $changeset, 1234)->will($this->returnValue($version));
        $this->subscriber->preFlush($event);
    }


    public function testPreFlushWillUpdateCurrentVersionIfSpecified()
    {
        $entity = new Entity();
        $version = new EntityVersion();

        $event = $this->createPreFlushEventArgs();

        $this->uow->expects($this->any())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->any())->method('getScheduledEntityInsertions')->will($this->returnValue([]));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->with($entity)->will($this->returnValue($changeset));
        $this->uow->expects($this->once())->method('scheduleForDirtyCheck')->with($version);
        $this->uow->expects($this->once())->method('clearEntityChangeSet')->with(spl_object_hash($entity));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue([VersioningManager::VERSION_OPERATION_UPDATE, 1234, []]));
        $this->manager->expects($this->once())->method('updateEntityVersion')->with($entity, $changeset, 1234)->will($this->returnValue($version));
        $this->subscriber->preFlush($event);
    }


    public function testPreFlushWillActivateCurrentVersion()
    {
        $entity = new Entity();
        $version = new EntityVersion();

        $event = $this->createPreFlushEventArgs();

        $this->uow->expects($this->any())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->any())->method('getScheduledEntityInsertions')->will($this->returnValue([]));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->with($entity)->will($this->returnValue($changeset));
        $this->uow->expects($this->once())->method('scheduleForUpdate')->with($version);
        $this->uow->expects($this->once())->method('scheduleForDirtyCheck')->with($version);
        $this->uow->expects($this->never())->method('clearEntityChangeSet')->with(spl_object_hash($entity));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue([VersioningManager::VERSION_OPERATION_ACTIVATE, 1234, []]));
        $this->manager->expects($this->once())->method('updateEntityVersion')->will($this->returnValue($version));
        $this->subscriber->preFlush($event);
    }

    public function testPreFlushWillSetDateActiveFromOnActivatedVersionWhereVersionAlreadyHasADate()
    {
        $entity = new Entity();
        $version = new EntityVersion();
        $version->setDateActiveFrom(new \DateTime('1980-11-17'));

        $event = $this->createPreFlushEventArgs();

        $this->uow->expects($this->any())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->any())->method('getScheduledEntityInsertions')->will($this->returnValue([]));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->with($entity)->will($this->returnValue($changeset));
        $this->uow->expects($this->once())->method('scheduleForUpdate')->with($version);
        $this->uow->expects($this->once())->method('scheduleForDirtyCheck')->with($version);
        $this->uow->expects($this->never())->method('clearEntityChangeSet')->with(spl_object_hash($entity));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue([VersioningManager::VERSION_OPERATION_ACTIVATE, 1234, []]));
        $this->manager->expects($this->once())->method('updateEntityVersion')->will($this->returnValue($version));
        $this->subscriber->preFlush($event);

        $this->assertNotEquals('1980-11-17', $version->getDateActiveFrom()->format('Y-m-d'));
        $this->assertNotNull($version->getDateActiveFrom());
    }

    public function testPreFlushWillSetDateActiveFromOnActivatedVersion()
    {
        $entity = new Entity();
        $version = new EntityVersion();

        $event = $this->createPreFlushEventArgs();

        $this->uow->expects($this->any())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->any())->method('getScheduledEntityInsertions')->will($this->returnValue([]));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->with($entity)->will($this->returnValue($changeset));
        $this->uow->expects($this->once())->method('scheduleForUpdate')->with($version);
        $this->uow->expects($this->once())->method('scheduleForDirtyCheck')->with($version);
        $this->uow->expects($this->never())->method('clearEntityChangeSet')->with(spl_object_hash($entity));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue([VersioningManager::VERSION_OPERATION_ACTIVATE, 1234, []]));
        $this->manager->expects($this->once())->method('updateEntityVersion')->will($this->returnValue($version));
        $this->subscriber->preFlush($event);

        $this->assertNotEquals('', $version->getDateActiveFrom()->format('Y-m-d'));
        $this->assertNotNull($version->getDateActiveFrom());
    }

    public function testPreFlushWillClearDateActiveFromWhenCreatingNewVersion()
    {
        // arrange
        $entity = new Entity();
        $version = new EntityVersion();
        $meta = ['dateActiveFrom' => new \DateTime(), 'other_data' => rand()];

        $event = $this->createPreFlushEventArgs();

        $this->uow->expects($this->any())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->any())->method('getScheduledEntityInsertions')->will($this->returnValue([]));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->will($this->returnValue($changeset));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue([VersioningManager::VERSION_OPERATION_NEW, 1234, $meta]));

        // assert
        $this->manager->expects($this->once())->method('createEntityVersion')->will(
            $this->returnCallback(
                function (VersionableInterface $entity, $changeset, $baseVersion = null, $metadata = null) {
                    $this->assertNotEmpty($metadata['other_data']);
                    $this->assertEmpty($metadata['dateActiveFrom']);
                }));

        // act
        $this->subscriber->preFlush($event);
    }

    public function testPreFlushWillDectivateCurrentlyActiveVersion()
    {
        $entity = new Entity();
        $version = new EntityVersion();
        $version->setVersionNumber(10);
        $currentActive = new EntityVersion();
        $currentActive->setIsActive(true);
        $currentActive->setVersionNumber(1);

        $event = $this->createPreFlushEventArgs();

        $this->uow->expects($this->any())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->any())->method('getScheduledEntityInsertions')->will($this->returnValue([]));

        $changeset = ['some'=>'changeset'];
        $this->uow->expects($this->once())->method('getEntityChangeSet')->with($entity)->will($this->returnValue($changeset));
        $this->uow->expects($this->never())->method('clearEntityChangeSet')->with(spl_object_hash($entity));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue([VersioningManager::VERSION_OPERATION_ACTIVATE, 1234, []]));
        $this->manager->expects($this->once())->method('updateEntityVersion')->will($this->returnValue($version));
        $this->manager->expects($this->once())->method('findActiveVersion')->will($this->returnValue($currentActive));
        $this->subscriber->preFlush($event);

        $this->assertFalse($currentActive->isActive());
    }

    /**
     * @expectedException \Zicht\Bundle\VersioningBundle\Exception\UnsupportedVersionOperationException
     */
    public function testPreFlushWillThrowErrorOnUnsupportedVersionOperation()
    {
        $entity = new Entity();

        $event = $this->createPreFlushEventArgs();

        $this->uow->expects($this->once())->method('getScheduledEntityUpdates')->will($this->returnValue([$entity]));
        $this->uow->expects($this->once())->method('getScheduledEntityInsertions')->will($this->returnValue([]));
        $this->manager->expects($this->once())->method('getVersionOperation')->will($this->returnValue(['foo', 1234, []]));
        $this->subscriber->preFlush($event);
    }

    /**
     * @return Event\PreFlushEventArgs
     */
    protected function createPreFlushEventArgs()
    {
        $event = $this->getMockBuilder(Event\PreFlushEventArgs::class)->disableOriginalConstructor()->setMethods(['getEntityManager'])->getMock();
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

    /**
     * @dataProvider clearArgs
     */

    public function testOnClearWillClearVersioning($entityClass)
    {
        $event = $this->getMockBuilder(Event\OnClearEventArgs::class)->disableOriginalConstructor()->getMock();
        $event->expects($this->once())->method('getEntityClass')->will($this->returnValue($entityClass));
        $this->manager->expects($this->once())->method('clear')->with($entityClass);

        $this->subscriber->onClear($event);
    }
    public function clearArgs()
    {
        return [
            [null],
            ['someclassname']
        ];
    }
}
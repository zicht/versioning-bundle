<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Integration;

use Doctrine\ORM\EntityManager;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Entity\Test\ContentItem;
use Zicht\Bundle\VersioningBundle\Entity\Test\Page;
use Zicht\Bundle\VersioningBundle\Entity\Test;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;

/**
 * @group integration
 */
class IntegrationTest extends \PHPUnit_Framework_TestCase
{
    /** @var \AppKernel */
    private static $kernel;

    /** @var EntityManager */
    private $em;

    /** @var VersioningManager */
    private $vm;

    public static function setUpBeforeClass()
    {
        require_once getenv('APPROOT') . '/bootstrap.php.cache';
        require_once getenv('APPROOT') . '/AppKernel.php';

        $kernel = new \AppKernel();
        $kernel->boot();

        self::$kernel = $kernel;

        $em = $kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        $classes = [
            Test\Page::class,
            Test\OtherOneToManyRelation::class,
            Test\NestedContentItem::class,
            Test\ChildOfNestedContentItem::class,
        ];

        $entityVersionClassMetadata = $em->getClassMetadata(EntityVersion::class);
        $connection = $em->getConnection();

        $connection->beginTransaction();

        try {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
            foreach ($classes as $className) {
                $connection->query('DELETE FROM '.$em->getClassMetadata($className)->getTableName());
            }
            $connection->query(sprintf(
                'DELETE FROM %s WHERE source_class IN(%s)',
                $entityVersionClassMetadata->getTableName(),
                join(', ', array_map([$connection, 'quote'], $classes)))
            );
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
        }
    }

    public function setUp()
    {
        $this->em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $this->em->clear();
        $this->vm = self::$kernel->getContainer()->get('zicht_versioning.manager');
    }

    public function testScalarField()
    {
        $o = new Page();
        $o->setTestingId(1);

        $o->setTitle("V1");

        $this->em->persist($o);
        $this->em->flush();
        $this->assertEquals(1, $this->vm->getVersionCount($o));
        $this->assertEquals(1, $this->vm->findActiveVersion($o)->getVersionNumber());

        $o->setTitle("V2");
        $this->em->persist($o);
        $this->em->flush();

        $this->assertEquals(2, $this->vm->getVersionCount($o));
        $this->assertEquals(1, $this->vm->findActiveVersion($o)->getVersionNumber());

        $this->em->clear();

        $o2 = $this->em->find(get_class($o), $o->getId());
        $this->assertEquals("V1", $o2->getTitle());

        $this->vm->loadVersion($o2, 2);
        $this->assertEquals("V2", $o2->getTitle());

        $this->vm->setVersionOperation($o2, VersioningManager::VERSION_OPERATION_UPDATE, 2);
        $o2->setTitle("V2 updated");
        $this->em->persist($o2);
        $this->em->flush();

        $this->vm->loadVersion($o2, 1);
        $this->assertEquals("V1", $o2->getTitle());

        $this->vm->loadVersion($o2, 2);
        $this->assertEquals("V2 updated", $o2->getTitle());

        $this->assertEquals(2, $this->vm->getVersionCount($o));
        $this->assertEquals(1, $this->vm->findActiveVersion($o)->getVersionNumber());

        $this->vm->setVersionOperation($o2, VersioningManager::VERSION_OPERATION_ACTIVATE, 2);
        $o2->setTitle("V2 updated2");
        $this->em->persist($o2);
        $this->em->flush();

        $this->assertEquals(2, $this->vm->getVersionCount($o));
        $this->assertEquals(2, $this->vm->findActiveVersion($o)->getVersionNumber());
    }

    public function testOneToManyWhenPageIsPersisted()
    {
        $o = new Page();
        $o->setTestingId(1);
        $o->setTitle("V1");

        $this->em->persist($o);
        $this->em->flush();

        $this->assertEquals(1, $this->vm->getVersionCount($o));
        $this->assertEquals(1, $this->vm->findActiveVersion($o)->getVersionNumber());

        $o->addContentItem(new ContentItem("item 1"));
        $this->em->persist($o);
        $this->em->flush();

        $this->assertEquals(2, $this->vm->getVersionCount($o));

        $this->vm->loadVersion($o, 1);
        $this->assertEquals([], $o->getContentItems()->toArray());

        $this->vm->loadVersion($o, 2);
        $this->assertEquals(1, count($o->getContentItems()->toArray()));

        $this->em->clear();
        $this->em->persist($o);
    }


    public function testOneToManyWhenContentItemIsPersisted()
    {
        $o = new Page();
        $o->setTestingId(1);
        $o->setTitle("V1");
        $this->em->persist($o);
        $this->em->flush();

        $this->vm->setVersionOperation($o, VersioningManager::VERSION_OPERATION_UPDATE, 1);

        $o->addContentItem(new ContentItem("item 1"));
        $this->em->persist($o);
        $this->em->flush();

        $this->assertEquals(1, $this->vm->getVersionCount($o));

        $this->vm->resetVersionOperation($o);
        /** @var ContentItem $item */
        $item = $o->getContentItems()->first();
        $item->setTitle('item 1 was updated');
        $this->em->persist($item);
        $this->em->flush();

        $this->assertEquals(2, $this->vm->getVersionCount($o));

        $this->em->clear();
        $o = $this->em->find(get_class($o), $o->getId());
        $this->assertEquals('item 1', $o->getContentItems()->first()->getTitle());

        $this->em->clear();
        $this->vm->setVersionToLoad(get_class($o), $o->getId(), 2);

        $o = $this->em->find(get_class($o), $o->getId());
        $this->assertEquals('item 1 was updated', $o->getContentItems()->first()->getTitle());
    }
}
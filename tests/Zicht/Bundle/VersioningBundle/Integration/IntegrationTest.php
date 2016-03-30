<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Integration;

use Doctrine\ORM\EntityManager;
use Zicht\Bundle\VersioningBundle\Entity\Test\ContentItem;
use Zicht\Bundle\VersioningBundle\Entity\Test\Page;
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

        $pageClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\Test\Page');
        $contentItemClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\Test\ContentItem');
        $otherOneToManyRelationClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\Test\OtherOneToManyRelation');
        $nestedContentItemClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\Test\NestedContentItem');
        $nestedChildContentItemClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\Test\ChildOfNestedContentItem');
        $entityVersionClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\EntityVersion');
        $connection = $em->getConnection();

        $connection->beginTransaction();

        try {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
            $connection->query('DELETE FROM '.$pageClassMetadata->getTableName());
            $connection->query('DELETE FROM '.$entityVersionClassMetadata->getTableName());
            $connection->query('DELETE FROM '.$contentItemClassMetadata->getTableName());
            $connection->query('DELETE FROM '.$otherOneToManyRelationClassMetadata->getTableName());
            $connection->query('DELETE FROM '.$nestedContentItemClassMetadata->getTableName());
            $connection->query('DELETE FROM '.$nestedChildContentItemClassMetadata->getTableName());
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
        }
    }

    public function setUp()
    {
        $this->em = self::$kernel->getContainer()->get('doctrine')->getManager();
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

        $this->assertEquals(3, $this->vm->getVersionCount($o));
        $this->assertEquals(3, $this->vm->findActiveVersion($o)->getVersionNumber());
    }

    public function testScalarFieldWithUpdateOperation()
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

        $this->assertEquals(3, $this->vm->getVersionCount($o));
        $this->assertEquals(3, $this->vm->findActiveVersion($o)->getVersionNumber());
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
    }
}
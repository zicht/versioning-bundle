<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @author Boudewijn Schoon <boudewijn@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersionRepository;
use Zicht\Bundle\VersioningBundle\TestAssets\Entity;

class EntityVersionRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineQueryBuilder;

    /** @var EntityVersionRepository | \PHPUnit_Framework_MockObject_MockObject */
    protected $entityVersionRepository;

    /**
     * Setup the test
     */
    protected function setUp()
    {
        $methods = [
            'select', 'where', 'andWhere', 'setParameters', 'orderBy', 'setMaxResults', 'getQuery', 'getResult'
        ];

        $this->doctrineQueryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->setMethods($methods)->getMock();
        foreach ($methods as $method) {
            $this->doctrineQueryBuilder->method($method)->will($this->returnValue($this->doctrineQueryBuilder));
        }

        /** @var ClassMetadata $classMetaData */
        $classMetaData = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
        $classMetaData->name = 'mocked ClassMetaData name';

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->setMethods(['createQueryBuilder'])->getMock();
        $entityManager->method('createQueryBuilder')->will($this->returnValue($this->doctrineQueryBuilder));

        $this->entityVersionRepository = new EntityVersionRepository($entityManager, $classMetaData);
    }
    
    /**
     * The findVersions is used in the EventSubscriber to determine the latest version, therefor the order need to be descending
     */
    public function test__findVersions__orderMustBeDescending()
    {
        $entity = new Entity();
        $limit = 171180;

        $this->doctrineQueryBuilder->expects($this->once())->method('orderBy')->with('ev.id', 'DESC');
        $this->doctrineQueryBuilder->expects($this->once())->method('setMaxResults')->with($limit);

        $this->entityVersionRepository->findVersions($entity, $limit);
    }
}

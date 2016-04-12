<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Entity;

use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityRepository;

use Zicht\Bundle\VersioningBundle\Model;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionInterface;

/**
 * EntityVersionRepository
 */
class EntityVersionRepository extends EntityRepository implements Model\EntityVersionStorageInterface
{
    /**
     * @{inheritDoc}
     */
    public function findVersions(Model\VersionableInterface $entity)
    {
        return $this->createQueryBuilder('ev')
            ->select('ev')
            ->where('ev.sourceClass = :sourceClass')
            ->andWhere('ev.originalId = :originalId')
            ->setParameters($this->queryParams($entity))
            ->orderBy('ev.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @{inheritDoc}
     */
    public function findVersion(Model\VersionableInterface $entity, $version)
    {
        return $this->createQueryBuilder('ev')
            ->select('ev')
            ->where('ev.sourceClass = :sourceClass')
            ->andWhere('ev.originalId = :originalId')
            ->setParameters($this->queryParams($entity))
            ->andWhere('ev.versionNumber = :version')
            ->setParameter('version', $version)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @{inheritDoc}
     */
    public function findActiveVersion(Model\VersionableInterface $entity)
    {
        return $this->createQueryBuilder('ev')
            ->select('ev')
            ->where('ev.sourceClass = :sourceClass')
            ->andWhere('ev.originalId = :originalId')
            ->setParameters($this->queryParams($entity))
            ->andWhere('ev.isActive = 1')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @{inheritDoc}
     */
    public function getNextVersionNumber(Model\VersionableInterface $entity)
    {
        /** @var QueryBuilder $q */
        $connection = $this->getEntityManager()->getConnection();
        $q = $connection->createQueryBuilder()
            ->select('MAX(version_number)')
            ->from('_entity_version', 'v')
            ->andWhere('original_id=:originalId AND source_class=:sourceClass')
            ->setParameters($this->queryParams($entity));
        /** @var PDOStatement $stmt */
        $stmt = $q->execute();
        list(list($m)) = $stmt->fetchAll(\PDO::FETCH_NUM);
        return $m + 1;
    }


    /**
     * Returns query parameters used commonly in all queries
     *
     * @param Model\VersionableInterface $entity
     * @return array
     */
    private function queryParams(Model\VersionableInterface $entity)
    {
        return ['sourceClass' => get_class($entity), 'originalId' => $entity->getId()];
    }

    /**
     * Store a version. when 'batch' passed as true, a closure to finish the transaction is returned.
     *
     * @param EntityVersionInterface $v
     * @param bool $batch
     * @return callable|null
     */
    public function save(EntityVersionInterface $v, $batch = false)
    {
        $this->getEntityManager()->persist($v);
        if (!$batch) {
            $this->getEntityManager()->flush($v);
            return null;
        } else {
            return function() {
                $this->getEntityManager()->flush();
            };
        }
    }
}

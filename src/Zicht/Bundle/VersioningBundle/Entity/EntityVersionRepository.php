<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Entity;

use Doctrine\Common\Util\ClassUtils;
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
            ->where('original_id=:originalId AND source_class=:sourceClass')
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
        return ['sourceClass' => ClassUtils::getRealClass(get_class($entity)), 'originalId' => $entity->getId()];
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
            return function () {
                $this->getEntityManager()->flush();
            };
        }
    }

    /**
     * Finds latest changes and match the objects.
     *
     * @param bool $active
     * @param int $limit
     * @return EntityVersionInterface[]
     */
    public function findLatestVersionChanges($active = null, $limit = 10)
    {
        $em = $this->getEntityManager();
        $q = $em->createQueryBuilder()
            ->select('v')
            ->from('ZichtVersioningBundle:EntityVersion', 'v')
            ->orderBy('v.dateCreated', 'DESC')
            ->setMaxResults($limit);

        if (null !== $active) {
            $q
                ->andWhere('v.isActive=:active')
                ->setParameter(':active', (bool)$active);
        }

        return $q->getQuery()->execute();
    }

    /**
     * Returns the list of Versionable objects that the versions are part of.
     *
     * @param Model\EntityVersionInterface[] $versions
     * @return Model\VersionableInterface[]
     */
    public function getObjects($versions)
    {
        $objects = [];
        $objectIds = [];

        foreach ($versions as $version) {
            $objectIds[$version->getSourceClass()][]= $version->getOriginalId();
        }

        foreach ($objectIds as $entityClass => $ids) {
            foreach ($this->getEntityManager()->getRepository($entityClass)->findBy(['id' => $ids]) as $entity) {
                $objects[$entityClass][$entity->getId()]= $entity;
            }
        }

        return $objects;
    }

    /**
     * Returns a list of versions that have not yet been activated, but have an active version that is older.
     *
     * @param int $limit
     * @return EntityVersionInterface[]
     */
    public function findUnactivatedVersions($limit = 10)
    {
        $ids = array_map(
            function ($row) {
                return $row['id'];
            },
            $this->getEntityManager()->getConnection()->fetchAll(
                sprintf(
                    '
                        SELECT
                            DISTINCT new_version.id
                        FROM
                            _entity_version new_version
                                INNER JOIN _entity_version active_version ON(
                                    new_version.source_class=active_version.source_class
                                    AND new_version.original_id=active_version.original_id
                                    AND new_version.version_number > active_version.version_number
                                )
                        ORDER BY
                            new_version.date_created DESC
                        LIMIT
                            %d
                    ',
                    $limit
                )
            )
        );
        $em = $this->getEntityManager();
        $q = $em->createQueryBuilder();

        $q
            ->select('v')
            ->from('ZichtVersioningBundle:EntityVersion', 'v')
            ->orderBy('v.dateCreated', 'DESC')
            ->andWhere('v.id IN(:ids)')
            ->setParameter(':ids', $ids);

        return $q->getQuery()->execute();
    }


    /**
     * Remove version
     *
     * @param EntityVersionInterface $version
     * @return void
     */
    public function remove($version)
    {
        $this->_em->remove($version);
        $this->_em->flush($version);
    }
}

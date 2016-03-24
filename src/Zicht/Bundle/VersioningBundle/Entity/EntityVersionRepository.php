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
     * Find the entityversions for the given entity
     *
     * @param Model\VersionableInterface $entity
     * @return mixed
     */
    public function findVersions(Model\VersionableInterface $entity)
    {
        return $this->createQueryBuilder('ev')
            ->select('ev')
            ->where('ev.sourceClass = :sourceClass')
            ->andWhere('ev.originalId = :originalId')
            ->setParameters(['sourceClass' => get_class($entity), 'originalId' => $entity->getId()])
            ->orderBy('ev.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a specific version of the given entity
     *
     * @param Model\VersionableInterface $entity
     * @param integer $version
     * @return mixed
     */
    public function findVersion(Model\VersionableInterface $entity, $version)
    {
        return $this->createQueryBuilder('ev')
            ->select('ev')
            ->where('ev.sourceClass = :sourceClass')
            ->andWhere('ev.originalId = :originalId')
            ->setParameters(['sourceClass' => get_class($entity), 'originalId' => $entity->getId()])
            ->andWhere('ev.versionNumber = :version')
            ->setParameter('version', $version)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find a specific version of the given entity - using the basedOnVersion
     *
     * @param Model\VersionableInterface $entity
     * @param integer $version
     * @return mixed
     */
    public function findByBasedOnVersion(Model\VersionableInterface $entity, $basedOnVersion)
    {
        return $this->createQueryBuilder('ev')
            ->select('ev')
            ->where('ev.sourceClass = :sourceClass')
            ->andWhere('ev.originalId = :originalId')
            ->setParameters(['sourceClass' => get_class($entity), 'originalId' => $entity->getId()])
            ->andWhere('ev.basedOnVersion = :basedOnVersion')
            ->setParameter('basedOnVersion', $basedOnVersion)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Set all the versions for the given entity to is_active = false
     *
     * @param Model\VersionableInterface $entity
     * @return void
     */
    public function deactivateAll(Model\VersionableInterface $entity)
    {
        $this->createQueryBuilder('ev')
            ->update('ZichtVersioningBundle:EntityVersion', 'ev')
            ->set('ev.isActive', 0)
            ->where('ev.sourceClass = :sourceClass')
            ->andWhere('ev.originalId = :originalId')
            ->setParameters(['sourceClass' => get_class($entity), 'originalId' => $entity->getId()])
            ->getQuery()
            ->execute();
    }

    /**
     * Gets the active version for the given entity
     *
     * @param Model\VersionableInterface $entity
     * @return EntityVersionInterface | null
     */
    public function findActiveEntityVersion(Model\VersionableInterface $entity)
    {
        return $this->createQueryBuilder('ev')
            ->select('ev')
            ->where('ev.sourceClass = :sourceClass')
            ->andWhere('ev.originalId = :originalId')
            ->setParameters(['sourceClass' => get_class($entity), 'originalId' => $entity->getId()])
            ->andWhere('ev.isActive = 1')
            ->getQuery()
            ->getOneOrNullResult();
    }



    public function getNextVersionNumber($entity)
    {
        /** @var QueryBuilder $q */
        $connection = $this->getEntityManager()->getConnection();
        $q = $connection->createQueryBuilder()
            ->select('MAX(version_number)')
            ->from('_entity_version', 'v')
            ->andWhere('original_id=? AND source_class=?')
            ->setParameters([
                $entity->getId(),
                get_class($entity)
            ]);
        /** @var PDOStatement $stmt */
        $stmt = $q->execute();
        list(list($m)) = $stmt->fetchAll(\PDO::FETCH_NUM);
        return $m + 1;
    }
}

<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * EntityVersionRepository
 *
 */
class EntityVersionRepository extends EntityRepository
{
    /**
     * Find the entityversions for the given entity
     *
     * @param IVersionable $entity
     * @return mixed
     */
    public function findVersions(IVersionable $entity)
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
     * @param IVersionable $entity
     * @param integer $version
     * @return mixed
     */
    public function findVersion(IVersionable $entity, $version)
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
     * Set all the versions for the given entity to is_active = false
     *
     * @param IVersionable $entity
     * @return void
     */
    public function deactivateAll(IVersionable $entity)
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
     * @param IVersionable $entity
     * @return EntityVersion | null
     */
    public function findActiveEntityVersion(IVersionable $entity)
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
}

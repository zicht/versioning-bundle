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
}
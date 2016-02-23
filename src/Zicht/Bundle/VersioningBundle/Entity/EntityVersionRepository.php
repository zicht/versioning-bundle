<?php

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
     * @param $sourceClass
     * @param $id
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
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

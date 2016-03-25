<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Model;

/**
 * Base interface for storing entity versions
 */
interface EntityVersionStorageInterface
{
    /**
     * Find all versions for the specified object.
     *
     * @param VersionableInterface $object
     * @return EntityVersionInterface[]
     */
    public function findVersions(VersionableInterface $object);

    /**
     * Find the specified version of the specified object
     *
     * @param VersionableInterface $entity
     * @param int $versionId
     * @return EntityVersionInterface
     */
    public function findVersion(VersionableInterface $entity, $versionId);

    /**
     * Find the active version for the specified entity.
     *
     * @param VersionableInterface $entity
     * @return mixed
     */
    public function findActiveEntityVersion(VersionableInterface $entity);

    /**
     * Get the next version number for the specified entity.
     *
     * @param VersionableInterface $entity
     * @return mixed
     */
    public function getNextVersionNumber(VersionableInterface $entity);
}
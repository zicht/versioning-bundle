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
     * @param null $limit
     * @return EntityVersionInterface[]
     */
    public function findVersions(VersionableInterface $object, $limit = null);

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
    public function findActiveVersion(VersionableInterface $entity);

    /**
     * Get the next version number for the specified entity.
     *
     * @param VersionableInterface $entity
     * @return mixed
     */
    public function getNextVersionNumber(VersionableInterface $entity);

    /**
     * Saves a version in the versioning repository.
     *
     * This method is not for api use; it is used for administrative purposes.
     *
     * @param EntityVersionInterface $v
     * @param bool $batch
     * @return mixed
     * @internal
     */
    public function save(EntityVersionInterface $v, $batch = false);

    /**
     * Returns the latest changes, as seen from the versioning perspective.
     *
     * @param bool $active
     * @param int $limit
     * @return \mixed[]
     */
    public function findLatestVersionChanges($active = null, $limit = 10);

    /**
     * Returns the objects that the versions specified apply to.
     *
     * TODO Consider finding a better place for this...
     *
     * @param EntityVersionInterface[] $versions
     * @return mixed
     */
    public function getObjects($versions);

    /**
     * Find all versions that have higher version numbers than the currently active version
     *
     * @return mixed
     */
    public function findUnactivatedVersions();

    /**
     * Remove a version
     *
     * @param EntityVersionInterface $version
     * @return mixed
     */
    public function remove($version);
}

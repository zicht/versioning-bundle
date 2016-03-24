<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionInterface;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionStorageInterface;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;
use Zicht\Bundle\VersioningBundle\Serializer\Serializer;

/**
 * Class VersioningService
 *
 * @package Zicht\Bundle\VersioningBundle\Manager
 */
class VersioningManager
{
    const VERSION_OPERATION_NEW = 'new';
    const VERSION_OPERATION_ACTIVATE = 'activate';
    const VERSION_OPERATION_UPDATE = 'update';
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var Serializer
     */
    private $serializer;

    private $versionsToLoad = [];
    private $versionOperations = [];
    private $affectedVersions = [];

    /**
     * VersioningService constructor.
     *
     * @param Registry $doctrine
     */
    public function __construct(Serializer $serializer, EntityVersionStorageInterface $storage)
    {
        $this->serializer = $serializer;
        $this->storage = $storage;
    }

    /**
     * Get the version count for the given entity
     *
     * @param VersionableInterface $entity
     * @return integer
     */
    public function getVersionCount(VersionableInterface $entity)
    {
        return count($this->storage->findVersions($entity));
    }


    public function getNextVersionNumber(VersionableInterface $entity)
    {
        return $this->storage->getNextVersionNumber($entity);
    }

    /**
     * Get the active version for the given entity
     *
     * @param VersionableInterface $entity
     * @return EntityVersionInterface | null
     */
    public function getActiveVersion(VersionableInterface $entity)
    {
        return $this->storage->findActiveEntityVersion($entity);
    }

    /**
     * @param VersionableInterface $entity
     * @param integer $versionId
     * @return EntityVersionInterface
     */
    private function findVersion(VersionableInterface $entity, $versionId)
    {
        return $this->storage->findVersion($entity, $versionId);
    }

    /**
     * Returns all entity versions of the specified versionable object.
     *
     * @param VersionableInterface $object
     * @return EntityVersionInterface[]
     */
    public function getVersions(VersionableInterface $object)
    {
        return $this->storage->findVersions($object);
    }

    public function createEntityVersion(VersionableInterface $entity, $changeset)
    {
        $version = new EntityVersion();

        $version->setChangeset(json_encode($changeset));
        $version->setSourceClass(get_class($entity));
        $version->setOriginalId($entity->getId());
        $version->setData($this->serializer->serialize($entity));
        $version->setVersionNumber($this->getNextVersionNumber($entity));

        return $version;
    }


    public function updateEntityVersion(VersionableInterface $entity, $changeset, $versionNumber)
    {
        $version = $this->findVersion($entity, $versionNumber);
        $version->setData($this->serializer->serialize($entity));
        $version->setChangeSet(json_encode($changeset));
        return $version;
    }

    public function setVersionToLoad($entityName, $id, $version)
    {
        $this->versionsToLoad[$entityName][$id]= $version;
    }

    public function getVersionToLoad(VersionableInterface $entity)
    {
        $className = get_class($entity);
        $id = $entity->getId();
        return isset($this->versionsToLoad[$className][$id]) ? $this->versionsToLoad[$className][$id] : null;
    }

    public function loadVersion(VersionableInterface $entity, $versionId)
    {
        $this->serializer->deserialize($this->findVersion($entity, $versionId), $entity);
    }

    /**
     * @param VersionableInterface $entity
     * @return string
     */
    public function getVersionOperation(VersionableInterface $entity)
    {
        $className = get_class($entity);
        $id = $entity->getId();
        if (isset($this->versionOperations[$className][$id])) {
            return $this->versionOperations[$className][$id];
        }
        if (null !== $this->getVersionToLoad($entity)) {
            return [self::VERSION_OPERATION_UPDATE, $this->getVersionToLoad($entity)];
        }
        if ($this->getActiveVersion($entity)) {
            return [self::VERSION_OPERATION_NEW, $this->getActiveVersion($entity)->getVersionNumber()];
        }
        return [self::VERSION_OPERATION_NEW, null];
    }

    public function setVersionOperation(VersionableInterface $entity, $versionOperation, $baseVersion)
    {
        $className = get_class($entity);
        $id = $entity->getId();
        $this->versionOperations[$className][$id] = [$versionOperation, $baseVersion];
    }

    public function getLoadedVersion($entity)
    {
        return $this->getVersionToLoad($entity) ?: $this->getActiveVersion($entity);
    }

    public function addAffectedVersion($entity, $version)
    {
        $this->affectedVersions[]= [$entity, $version];
    }

    public function getAffectedVersions()
    {
        return $this->affectedVersions;
    }
}
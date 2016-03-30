<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Manager;

use Gedmo\Exception\RuntimeException;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Exception\VersionNotFoundException;
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
    /**
     * Used to identify the operation of creating a new version
     */
    const VERSION_OPERATION_NEW = 'new';

    /**
     * Used to identify the operation of activating a version
     */
    const VERSION_OPERATION_ACTIVATE = 'activate';

    /**
     * Used to identify the operation of updating the version's contents
     */
    const VERSION_OPERATION_UPDATE = 'update';

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var EntityVersionStorageInterface
     */
    private $storage;

    private $versionsToLoad = [];
    private $versionOperations = [];
    private $affectedVersions = [];
    private $loadedVersions = [];

    /**
     * VersioningService constructor.
     *
     * @param Serializer $serializer
     * @param EntityVersionStorageInterface $storage
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


    /**
     * Get the active version for the given entity
     *
     * @param VersionableInterface $entity
     * @return EntityVersionInterface | null
     */
    public function findActiveVersion(VersionableInterface $entity)
    {
        return $this->storage->findActiveVersion($entity);
    }

    /**
     * Find the specified entity version
     *
     * @param VersionableInterface $entity
     * @param integer $versionId
     * @return EntityVersionInterface
     */
    public function findVersion(VersionableInterface $entity, $versionId)
    {
        return $this->storage->findVersion($entity, $versionId);
    }

    /**
     * Returns all entity versions of the specified versionable object.
     *
     * @param VersionableInterface $object
     * @return EntityVersionInterface[]
     */
    public function findVersions(VersionableInterface $object)
    {
        return $this->storage->findVersions($object);
    }

    /**
     * Create a new entity version based on the specified entity and changeset.
     *
     * @param VersionableInterface $entity
     * @param array $changeset
     * @return EntityVersion
     */
    public function createEntityVersion(VersionableInterface $entity, $changeset, $baseVersion = null)
    {
        $version = new EntityVersion();

        $version->setChangeset($changeset);
        $version->setSourceClass(get_class($entity));
        $version->setOriginalId($entity->getId());
        $version->setData($this->serializer->serialize($entity));
        $version->setVersionNumber($this->storage->getNextVersionNumber($entity));
        if ($baseVersion !== null) {
            $version->setBasedOnVersion($baseVersion);
        }

        $this->affectedVersions[]= [$entity, $version];
        return $version;
    }


    /**
     * Update an existing entity version.
     *
     * @param VersionableInterface $entity
     * @param array $changeset
     * @param int $versionNumber
     * @return EntityVersionInterface
     */
    public function updateEntityVersion(VersionableInterface $entity, $changeset, $versionNumber)
    {
        $version = $this->findVersion($entity, $versionNumber);
        if (!$version) {
            throw new VersionNotFoundException("Version not found: {$versionNumber}");
        }
        $version->setData($this->serializer->serialize($entity));
        $version->setChangeSet(json_encode($changeset));

        $this->affectedVersions[]= [$entity, $version];
        return $version;
    }

    /**
     * Marks an entity to be loaded as the specified version
     *
     * @param string $entityName
     * @param int $id
     * @param int $version
     * @return void
     */
    public function setVersionToLoad($entityName, $id, $version)
    {
        $this->versionsToLoad[$entityName][$id]= $version;
    }

    /**
     * Returns a version number to load for the specified entity, based on what was previously registered as
     * the version to load by setVersionToLoad().
     *
     * @param VersionableInterface $entity
     * @return int|null
     */
    public function getVersionToLoad(VersionableInterface $entity)
    {
        $className = get_class($entity);
        $id = $entity->getId();
        return isset($this->versionsToLoad[$className][$id]) ? $this->versionsToLoad[$className][$id] : null;
    }


    /**
     * Injects the values of the version number that was specified earlier using setVersionToLoad into the specified
     * entity.
     *
     * @param VersionableInterface $entity
     * @param int $versionNumber
     * @return void
     */
    public function loadVersion(VersionableInterface $entity, $versionNumber = null)
    {
        if (null !== $versionNumber || ($versionNumber = $this->getVersionToLoad($entity))) {
            $version = $this->findVersion($entity, $versionNumber);
            $this->loadedVersions[]= $version;

            $this->serializer->deserialize($version, $entity);
        }
    }

    /**
     * Returns the version operation and base version number for the specified entity. If there is none currently
     * avaiable, defaults to a new version based on the currently active version
     *
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
        if ($activeVersion = $this->findActiveVersion($entity)) {
            return [self::VERSION_OPERATION_NEW, $activeVersion->getVersionNumber()];
        }
        return [self::VERSION_OPERATION_NEW, null];
    }

    /**
     * Schedule any operation on the entity to be the specified version operation based on the specified version number
     *
     * @param VersionableInterface $entity
     * @param string $versionOperation
     * @param int $baseVersionNumber
     * @return void
     */
    public function setVersionOperation(VersionableInterface $entity, $versionOperation, $baseVersionNumber)
    {
        $className = get_class($entity);
        $id = $entity->getId();
        $this->versionOperations[$className][$id] = [$versionOperation, $baseVersionNumber];
    }

    /**
     * Returns a list of tuples containing the entity and it's associated version that was affected during
     * the current request.
     *
     * @return array
     */
    public function getAffectedVersions()
    {
        return $this->affectedVersions;
    }


    public function isManaged($className)
    {
        return (new \ReflectionClass($className))->implementsInterface(VersionableInterface::class);
    }

    public function fix($object)
    {
        if (!$this->findActiveVersion($object)) {
            $v = $this->createEntityVersion($object, [], null);
            $v->setIsActive(true);
            $this->storage->save($v);
        }
    }

    public function getLoadedVersions()
    {
        return $this->loadedVersions;
    }
}
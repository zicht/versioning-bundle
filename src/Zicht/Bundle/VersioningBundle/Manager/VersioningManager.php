<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Manager;

use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Util\ClassUtils;
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
     * Used to configure what version operation to do when a version is loaded explicitly
     */
    const VERSION_STATE_EXPLICITLY_LOADED = 'explicit';

    /**
     * Used to configure what version operation to do when a version is loaded based on it's active state
     */
    const VERSION_STATE_ACTIVE = 'active';

    /**
     * @var array
     */
    protected $defaultVersionOperation = [
        self::VERSION_STATE_EXPLICITLY_LOADED => self::VERSION_OPERATION_UPDATE,
        self::VERSION_STATE_ACTIVE => self::VERSION_OPERATION_UPDATE
    ];

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var EntityVersionStorageInterface
     */
    private $storage;

    /** @var TokenStorageInterface */
    private $securityTokenStorage = null;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker = null;

    private $versionsToLoad = [];
    private $versionOperations = [];
    private $affectedVersions = [];

    /**
     * @var EntityVersionInterface[]
     */
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
     * Set the token storage of which the username is used to store in newly created versions
     *
     * @param TokenStorageInterface $tokenStorage
     * @return void
     */
    public function setTokenStorage(TokenStorageInterface $tokenStorage)
    {
        $this->securityTokenStorage = $tokenStorage;
    }

    /**
     * Set the authorizationchecker to use for authorization checks
     *
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @return void
     */
    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
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
    public function findVersions(VersionableInterface $object, $limit = null)
    {
        return $this->storage->findVersions($object, $limit);
    }


    /**
     * Create a new entity version based on the specified entity and changeset.
     *
     * @param VersionableInterface $entity
     * @param array $changeset
     * @param int $baseVersion
     * @param array $metadata
     * @return EntityVersion
     */
    public function createEntityVersion(VersionableInterface $entity, $changeset, $baseVersion = null, $metadata = null)
    {
        $version = new EntityVersion();

        $version->setUsername($this->securityTokenStorage->getToken()->getUsername());
        $version->setChangeset($changeset);
        $version->setSourceClass(get_class($entity));
        $version->setOriginalId($entity->getId());
        $version->setData($this->serializer->serialize($entity));
        $version->setVersionNumber($this->storage->getNextVersionNumber($entity));
        if (null !== $baseVersion) {
            $version->setBasedOnVersion($baseVersion);
        }
        if (null !== $metadata) {
            if (isset($metadata['dateActiveFrom'])) {
                $version->setDateActiveFrom($metadata['dateActiveFrom']);
            }
            if (isset($metadata['notes'])) {
                $version->setNotes($metadata['notes']);
            }
        }

        $this->affectedVersions[] = [$entity, $version];
        return $version;
    }


    /**
     * Update an existing entity version.
     *
     * @param VersionableInterface $entity
     * @param array $changeset
     * @param int $versionNumber
     * @param array $metadata
     * @return EntityVersionInterface
     */
    public function updateEntityVersion(VersionableInterface $entity, $changeset, $versionNumber, $metadata = null)
    {
        $version = $this->findVersion($entity, $versionNumber);
        if (!$version) {
            throw new VersionNotFoundException("Version not found: {$versionNumber}");
        }
        $version->setData($this->serializer->serialize($entity));
        $version->setChangeSet(json_encode($changeset));
        if (null !== $metadata) {
            if (isset($metadata['dateActiveFrom'])) {
                $version->setDateActiveFrom($metadata['dateActiveFrom']);
            }
            if (isset($metadata['notes'])) {
                $version->setNotes($metadata['notes']);
            }
        }

        $this->affectedVersions[] = [$entity, $version];
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
        if (null === $version) {
            unset($this->versionsToLoad[$entityName][$id]);
        } else {
            $this->versionsToLoad[$entityName][$id] = $version;
        }
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
        if (null === $versionNumber) {
            $versionNumber = $this->getVersionToLoad($entity);
        }

        if (null !== $versionNumber) {
            $version = $this->findVersion($entity, $versionNumber);

            if (!$version) {
                throw new VersionNotFoundException();
            }
            if (!$this->authorizationChecker->isGranted(['EDIT'], $entity) && !$this->authorizationChecker->isGranted(['VIEW'], $version)) {
                throw new AccessDeniedException();
            }

            $this->loadedVersions[] = $version;
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
            return [$this->defaultVersionOperation[self::VERSION_STATE_EXPLICITLY_LOADED], $this->getVersionToLoad($entity), []];
        }
        if ($activeVersion = $this->findActiveVersion($entity)) {
            return [$this->defaultVersionOperation[self::VERSION_STATE_ACTIVE], $activeVersion->getVersionNumber(), []];
        }
        return [self::VERSION_OPERATION_NEW, null, []];
    }


    /**
     * Gets all version operations that were explicitly set.
     *
     * @return array
     */
    public function getExplicitVersionOperations()
    {
        $ret = [];
        foreach ($this->versionOperations as $className => $instances) {
            foreach ($instances as $id => $operationDetails) {
                $ret[]= array_merge(
                    [$className, $id],
                    $operationDetails
                );
            }
        }

        return $ret;
    }


    /**
     * Mark a version operation as handled.
     *
     * @param VersionableInterface $entity
     * @return void
     */
    public function markExplicitVersionOperationHandled(VersionableInterface $entity)
    {
        unset($this->versionOperations[ClassUtils::getRealClass($entity)][$entity->getId()]);
    }


    /**
     * Schedule any operation on the entity to be the specified version operation based on the specified version number
     *
     * @param VersionableInterface $entity
     * @param string $versionOperation
     * @param int $baseVersionNumber
     * @param array $metaData
     * @return void
     */
    public function setVersionOperation(VersionableInterface $entity, $versionOperation, $baseVersionNumber, $metaData = [])
    {
        if (!$this->authorizationChecker->isGranted(['EDIT'], $entity)) {
            throw new AccessDeniedException;
        }
        $className = get_class($entity);
        $id = $entity->getId();
        if (null === $versionOperation) {
            unset($this->versionOperations[$className][$id]);
        } else {
            $this->versionOperations[$className][$id] = [$versionOperation, $baseVersionNumber, $metaData];
        }
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


    /**
     * Returns all currently affected versions for the specified entity.
     *
     * @param VersionableInterface $entity
     * @return EntityVersion[]
     */
    public function getAffectedEntityVersions($entity)
    {
        $ret = [];

        if ($this->isManaged(get_class($entity))) {
            foreach ($this->affectedVersions as list($affectedEntity, $version)) {
                if (get_class($entity) === get_class($affectedEntity)) {
                    $ret[]= $version;
                }
            }
        }

        return $ret;
    }


    /**
     * Check if the provided class name is managed
     *
     * @param string $className
     * @return bool
     */
    public function isManaged($className)
    {
        return (new \ReflectionClass($className))->implementsInterface(VersionableInterface::class);
    }

    /**
     * Fix a versionable object: if it has no active version, create one and set that active.
     *
     * @param VersionableInterface $object
     * @return null|EntityVersion
     */
    public function fix(VersionableInterface $object)
    {
        if (!$this->findActiveVersion($object)) {
            $v = $this->createEntityVersion($object, [], null);
            $v->setIsActive(true);
            return $v;
        }
        return null;
    }

    /**
     * @return array
     */
    public function getLoadedVersions()
    {
        return $this->loadedVersions;
    }

    /**
     * Set a default version operation for a specific version state
     *
     * @param string $state
     * @param string $operation
     * @return void
     */
    public function setDefaultVersionOperation($state, $operation)
    {
        if (!in_array($state, [self::VERSION_STATE_ACTIVE, self::VERSION_STATE_EXPLICITLY_LOADED])) {
            throw new \InvalidArgumentException("{$state} is not a valid version state");
        }
        if (!in_array($operation, [self::VERSION_OPERATION_NEW, self::VERSION_OPERATION_UPDATE])) {
            throw new \InvalidArgumentException("{$operation} is not a valid version operation");
        }

        $this->defaultVersionOperation[$state]= $operation;
    }

    /**
     * Resets the version operation for the specified object.
     *
     * @param VersionableInterface $object
     * @return void
     */
    public function resetVersionOperation(VersionableInterface $object)
    {
        $this->setVersionOperation($object, null, null, null);
    }

    /**
     * Flush all entity versions specified as the argument.
     *
     * This method is not for api use; it is used for administrative purposes.
     *
     * @param EntityVersionInterface[] $changes
     * @return void
     * @internal
     */
    public function flushChanges($changes)
    {
        if (!count($changes)) {
            return;
        }

        $cb = null;
        foreach ($changes as $c) {
            $cb = $this->storage->save($c, true);
        }

        if (is_callable($cb)) {
            call_user_func($cb);
        }
    }

    /**
     * @param VersionableInterface $object
     * @param EntityVersionInterface|null $version
     * @return array
     */
    public function getAvailableOperations(VersionableInterface $object, EntityVersionInterface $version = null)
    {
        $ret = [];

        if ($this->authorizationChecker->isGranted(['EDIT'], $object)) {
            $ret = [VersioningManager::VERSION_OPERATION_NEW];

            if ($version) {
                if ($this->authorizationChecker->isGranted(['EDIT'], $version)) {
                    $ret [] = VersioningManager::VERSION_OPERATION_UPDATE;
                }
                if (!$version->isActive() && $this->authorizationChecker->isGranted(['PUBLISH'], $object)) {
                    $ret [] = VersioningManager::VERSION_OPERATION_ACTIVATE;
                }
            }
        }
        return $ret;
    }

    /**
     * Set a system token. Use with care; typically only in console commands.
     *
     * @param string $username
     * @param array $roles
     * @return void
     */
    public function setSystemToken($username = 'SYSTEM', $roles = ['ROLE_SYSTEM'])
    {
        if (null !== $this->securityTokenStorage->getToken()) {
            throw new \UnexpectedValueException("Refusing to override an existing token");
        }
        $this->securityTokenStorage->setToken(new PreAuthenticatedToken($username, '', 'SYSTEM', $roles));
    }


    /**
     * Get the latest version changes, returns a tuple containing a list of EntityVersions and a map containing the
     * objects mapped by class name and object id.
     *
     * @param bool $active
     * @param int $limit
     * @return \mixed[]
     */
    public function getLatestChanges($active, $limit = 10)
    {
        /** @var EntityVersionInterface[] $versions */
        $versions = $this->storage->findLatestVersionChanges($active, $limit);
        $objects = $this->storage->getObjects($versions);
        return ['versions' => $versions, 'objects' => $objects];
    }


    /**
     * List all versions that have an active version that is older; i.e. all non-activated versions that are "pending"
     *
     * @return array
     */
    public function getUnactivatedVersions()
    {
        $versions = $this->storage->findUnactivatedVersions();
        $objects = $this->storage->getObjects($versions);
        return ['versions' => $versions, 'objects' => $objects];
    }

    /**
     * Clears affected versions. Typically only used by the EventSubscribe to listen to Doctrine's onClear event.
     *
     * @param null $entityClass
     * @return void
     */
    public function clear($entityClass = null)
    {
        if (null === $entityClass) {
            $this->affectedVersions = [];
        } else {
            $this->affectedVersions = array_filter(
                $this->affectedVersions,
                function ($affectedVersion) use ($entityClass) {
                    list($entity, $id) = $affectedVersion;
                    return ! ($entity instanceof $entityClass);
                }
            );
        }
    }

    /**
     * Delete a single version
     *
     * @param VersionableInterface $object
     * @param int $versionNumber
     * @return bool
     */
    public function deleteVersion($object, $versionNumber)
    {
        // only delete loaded versions.
        foreach ($this->loadedVersions as $i => $version) {
            if ($version->getSourceClass() === get_class($object) && $version->getOriginalId() === $object->getId() && $version->getVersionNumber() === (int)$versionNumber) {
                if (!$this->authorizationChecker->isGranted(['DELETE'], $version)) {
                    throw new AccessDeniedException;
                }

                $this->storage->remove($version);
                $this->clear(get_class($object));
                unset($this->loadedVersions[$i]);

                return true;
            }
        }

        return false;
    }
}

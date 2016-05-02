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

    /** @var TokenStorageInterface */
    private $securityTokenStorage = null;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker = null;

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
    public function findVersions(VersionableInterface $object)
    {
        return $this->storage->findVersions($object);
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

        $this->affectedVersions[]= [$entity, $version];
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

            if (!$this->authorizationChecker->isGranted(['EDIT'], $entity) && !$this->authorizationChecker->isGranted(['VIEW'], $version)) {
                throw new AccessDeniedException();
            }

            $this->loadedVersions[]= $version;
            $this->serializer->deserialize($version, $entity);
        } elseif (null === $versionNumber) {
            $version = $this->findActiveVersion($entity);
            if ($version !== null) {
                $this->serializer->deserialize($version, $entity);
            }
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
            return [self::VERSION_OPERATION_NEW, $this->getVersionToLoad($entity), []];
        }
        if ($activeVersion = $this->findActiveVersion($entity)) {
            return [self::VERSION_OPERATION_NEW, $activeVersion->getVersionNumber(), []];
        }
        return [self::VERSION_OPERATION_NEW, null, []];
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
                    $ret []= VersioningManager::VERSION_OPERATION_UPDATE;
                }
                if (!$version->isActive() && $this->authorizationChecker->isGranted(['PUBLISH'], $object)) {
                    $ret []= VersioningManager::VERSION_OPERATION_ACTIVATE;
                }
            }
        }
        return $ret;
    }

    /**
     * Set a system token. Use with care; typically only in console commands.
     *
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
}
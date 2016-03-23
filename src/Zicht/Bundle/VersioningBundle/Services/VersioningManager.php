<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionInterface;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;
use Zicht\Bundle\VersioningBundle\Serializer\Serializer;

/**
 * Class VersioningService
 *
 * @package Zicht\Bundle\VersioningBundle\Services
 */
class VersioningManager
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var array
     */
    private $currentWorkingVersionNumberMap = [];

    /**
     * @var array
     */
    private $activatedEntityVersions = [];

    /**
     * @var array
     */
    private $makeEntityActiveMap = [];

    private $versionsToLoad = [];

    /**
     * VersioningService constructor.
     *
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }


    public function getSerializer()
    {
        if (null === $this->serializer) {
            $this->serializer = new Serializer($this->doctrine->getManager());
        }
        return $this->serializer;
    }

    /**
     * Get the version count for the given entity
     *
     * @param VersionableInterface $entity
     * @return integer
     */
    public function getVersionCount(VersionableInterface $entity)
    {
        $result = $this->doctrine->getManager()->getRepository('ZichtVersioningBundle:EntityVersion')->findVersions($entity);
        return count($result);
    }

    /**
     * Get the active version number for the given entity
     *
     * @param VersionableInterface $entity
     * @return integer
     */
    public function getActiveVersionNumber(VersionableInterface $entity)
    {
        $entityVersion = $this->getActiveVersion($entity);
        return $entityVersion->getVersionNumber();
    }

    /**
     * Sets the given page to the version information of the given version
     *
     * @param VersionableInterface $entity
     * @param integer $version
     * @return void
     */
    public function setActive(VersionableInterface $entity, $version)
    {
        $this->deactivateAll($entity);

        $entityVersion = $this->getSpecificEntityVersion($entity, $version);

        $this->storeActivatedEntityVersion($entity, $entityVersion);
        $this->restoreVersion($entityVersion);
    }

    /**
     * Helper method to set all versions of the given entry to not active
     *
     * @param VersionableInterface $entity
     * @return void
     */
    public function deactivateAll(VersionableInterface $entity)
    {
        $this->doctrine->getManager()->getRepository('ZichtVersioningBundle:EntityVersion')->deactivateAll($entity);
    }

    /**
     * Store the entityVersion information for later usage
     *
     * @param VersionableInterface $entity
     * @param EntityVersionInterface $entityVersion
     */
    private function storeActivatedEntityVersion(VersionableInterface $entity, EntityVersionInterface $entityVersion)
    {
        $entityVersion->setIsActive(true);
        $this->activatedEntityVersions[spl_object_hash($entity)] = $entityVersion;
    }

    /**
     * Gets the entityVersion information for the given entity
     *
     * @param VersionableInterface $entity
     * @return EntityVersionInterface
     */
    public function getEntityVersionInformation(VersionableInterface $entity)
    {
        /*
         * werken we nu met een versie?
         *   ja: teruggeven (EDIT VERSIE)
         *  nee: dan kijken of hij gestored (wanneer actief gezet) staat
         *     ja: teruggeven (ACTIEF GEZET)
         *    nee: dan de actieve ophalen
         *       is die er?
         *           ja: teruggeven (UPDATE)
         *          nee: nieuwe (PERSIST)
         */
        $entityKey = spl_object_hash($entity);

        //are we currently editing a specific version?
        if (array_key_exists($entityKey, $this->currentWorkingVersionNumberMap)) {
            return $this->getSpecificEntityVersion($entity, $this->currentWorkingVersionNumberMap[$entityKey]);
        }

        //do we have an entity set to active
        if (array_key_exists($entityKey, $this->activatedEntityVersions)) {
            return $this->activatedEntityVersions[$entityKey];
        }

        //if none of the above, let's get the active version
        $activeEntityVersion = $this->getActiveVersion($entity);
        if ($activeEntityVersion !== null) {

            //set the entity to active if it was set specific by the startActiveTransaction()
            if (in_array(spl_object_hash($entity), $this->makeEntityActiveMap)) {
                $activeEntityVersion->setIsActive(true);
            } else {
                //we don't want an updated version to be set to active by default
                //we need to set it explicitly to false, since we use the active EntityVersion here, which is active ^^
                $activeEntityVersion->setIsActive(false);
            }
            return $activeEntityVersion;
        }

        //none of the above, so we are creating a new entity
        return null;
    }

    /**
     * Get the active version for the given entity
     *
     * @param VersionableInterface $entity
     * @return EntityVersionInterface | null
     */
    public function getActiveVersion(VersionableInterface $entity)
    {
        return $this->doctrine->getManager()->getRepository('ZichtVersioningBundle:EntityVersion')->findActiveEntityVersion($entity);
    }

    /**
     * Writes the stored serialized entity information to the entity table
     *
     * @param EntityVersionInterface $entityVersion
     * @return void
     */
    private function restoreVersion(EntityVersionInterface $entityVersion)
    {
        /** @var VersionableInterface $storedEntity */
        $entity = $this->doctrine->getManager()->getRepository($entityVersion->getSourceClass())->find($entityVersion->getOriginalId());
        $this->getSerializer()->deserialize($entityVersion, $entity);

        $this->doctrine->getManager()->persist($entity);
        $this->doctrine->getManager()->flush();
    }

    /**
     * @param VersionableInterface $entity
     * @param $versionNumber
     */
    public function setCurrentWorkingVersionNumber(VersionableInterface $entity, $versionNumber)
    {
        $this->currentWorkingVersionNumberMap[spl_object_hash($entity)] = $versionNumber;
    }

    public function getCurrentWorkingVersionNumber(VersionableInterface $entity)
    {
        $key = spl_object_hash($entity);

        if (key_exists($key, $this->currentWorkingVersionNumberMap)) {
            return $this->currentWorkingVersionNumberMap[$key];
        }

        return null;
    }

    /**
     * Inform the versioning service we will go and make the next persist for this entity active
     *
     * @param VersionableInterface $entity
     * @return void
     */
    public function startActiveTransaction(VersionableInterface $entity)
    {
        $this->makeEntityActiveMap[] = spl_object_hash($entity);
    }

    /**
     * @param VersionableInterface $entity
     * @param integer $version
     * @return EntityVersionInterface | null
     */
    private function getSpecificEntityVersion(VersionableInterface $entity, $version)
    {
        return $this->doctrine->getManager()->getRepository('ZichtVersioningBundle:EntityVersion')->findVersion($entity, $version);
    }

    public function getVersions($object)
    {
        return $this->doctrine->getManager()->getRepository('ZichtVersioningBundle:EntityVersion')->findVersions($object);
    }

    public function createEntityVersion(VersionableInterface $entity)
    {
        $version = new EntityVersion();

        $version->setSourceClass(get_class($entity));
        $version->setOriginalId($entity->getId());
        $version->setData($this->getSerializer()->serialize($entity));
        $version->setVersionNumber($this->getVersionCount($entity) + 1);

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

    public function populateVersion($entity, $version)
    {
        $this->getSerializer()->deserialize($this->getSpecificEntityVersion($entity, $version), $entity);
    }
}
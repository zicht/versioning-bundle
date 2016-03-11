<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Services;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Entity\IVersionable;

/**
 * Class VersioningService
 *
 * @package Zicht\Bundle\VersioningBundle\Services
 */
class VersioningService
{
    /**
     * @var Registry
     */
    private $doctrine;
    /**
     * @var SerializerService
     */
    private $serializer;

    /** @var array */
    private $currentWorkingVersionNumberMap = [];

    /** @var array */
    private $activatedEntityVersions = [];

    private $makeEntityActiveMap = [];

    /**
     * VersioningService constructor.
     *
     * @param Registry $doctrine
     * @param SerializerService $serializer
     */
    public function __construct(Registry $doctrine, SerializerService $serializer)
    {
        $this->doctrine = $doctrine;
        $this->serializer = $serializer;
    }

    /**
     * Get the version count for the given entity
     *
     * @param IVersionable $entity
     * @return integer
     */
    public function getVersionCount(IVersionable $entity)
    {
        $result = $this->doctrine->getManager()->getRepository('ZichtVersioningBundle:EntityVersion')->findVersions($entity);
        return count($result);
    }

    /**
     * Get the active version number for the given entity
     *
     * @param IVersionable $entity
     * @return integer
     */
    public function getActiveVersionNumber(IVersionable $entity)
    {
        $entityVersion = $this->getActiveVersion($entity);
        return $entityVersion->getVersionNumber();
    }

    /**
     * Sets the given page to the version information of the given version
     *
     * @param IVersionable $entity
     * @param integer $version
     * @return void
     */
    public function setActive(IVersionable $entity, $version)
    {
        $this->deactivateAll($entity);

        $entityVersion = $this->getSpecificEntityVersion($entity, $version);

        $this->storeActivatedEntityVersion($entity, $entityVersion);
        $this->writeEntityToEntityTable($entityVersion);
    }

    /**
     * Helper method to set all versions of the given entry to not active
     *
     * @param IVersionable $entity
     * @return void
     */
    public function deactivateAll(IVersionable $entity)
    {
        $this->doctrine->getManager()->getRepository('ZichtVersioningBundle:EntityVersion')->deactivateAll($entity);
    }

    /**
     * Generate an identifier unique for the given entity, so we can get some information about this exact entity later on
     *
     * @param IVersionable $entity
     * @return string
     */
    public function makeHash(IVersionable $entity)
    {
//        return get_class($entity) . '-' . $entity->getId();
        return spl_object_hash($entity);
    }

    /**
     * Store the entityVersion information for later usage
     *
     * @param IVersionable $entity
     * @param EntityVersion $entityVersion
     */
    private function storeActivatedEntityVersion(IVersionable $entity, EntityVersion $entityVersion)
    {
        $entityVersion->setIsActive(true);
        $this->activatedEntityVersions[$this->makeHash($entity)] = $entityVersion;
    }

    /**
     * Gets the entityVersion information for the given entity
     *
     * @param IVersionable $entity
     * @return EntityVersion
     */
    public function getEntityVersionInformation(IVersionable $entity)
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
        $entityKey = $this->makeHash($entity);

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
            if (in_array($this->makeHash($entity), $this->makeEntityActiveMap)) {
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
     * @param IVersionable $entity
     * @return EntityVersion | null
     */
    public function getActiveVersion(IVersionable $entity)
    {
        return $this->doctrine->getManager()->getRepository('ZichtVersioningBundle:EntityVersion')->findActiveEntityVersion($entity);
    }

    /**
     * Writes the stored serialized entity information to the entity table
     *
     * @param EntityVersion $entityVersion
     * @return void
     */
    private function writeEntityToEntityTable(EntityVersion $entityVersion)
    {
        /** @var IVersionable $storedEntity */
        $storedEntity = $this->serializer->deserialize($entityVersion);
//        $storedEntity = $this->doctrine->getManager()->merge($storedEntity);

        $entity = $this->doctrine->getManager()->getRepository($entityVersion->getSourceClass())->find($entityVersion->getOriginalId());

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
//
        $_reflectionClass = new \ReflectionClass(get_class($storedEntity));
        foreach ($_reflectionClass->getProperties() as $prop) {
            if (!in_array($prop->name, ['id'])) {
                $propertyAccessor->setValue($entity, $prop->name, $propertyAccessor->getValue($storedEntity, $prop->name));
            }
        }

        $this->doctrine->getManager()->persist($entity);
        $this->doctrine->getManager()->flush();
    }

    /**
     * @param IVersionable $entity
     * @param $versionNumber
     */
    public function setCurrentWorkingVersionNumber(IVersionable $entity, $versionNumber)
    {
        $this->currentWorkingVersionNumberMap[$this->makeHash($entity)] = $versionNumber;
    }

    public function getCurrentWorkingVersionNumber(IVersionable $entity)
    {
        $key = $this->makeHash($entity);

        if (key_exists($key, $this->currentWorkingVersionNumberMap)) {
            return $this->currentWorkingVersionNumberMap[$key];
        }

        return null;
    }

    /**
     * Inform the versioning service we will go and make the next persist for this entity active
     *
     * @param IVersionable $entity
     * @return void
     */
    public function startActiveTransaction(IVersionable $entity)
    {
        $this->makeEntityActiveMap[] = $this->makeHash($entity);
    }

    /**
     * @param IVersionable $entity
     * @param integer $version
     * @return EntityVersion | null
     */
    private function getSpecificEntityVersion(IVersionable $entity, $version)
    {
        return $this->doctrine->getManager()->getRepository('ZichtVersioningBundle:EntityVersion')->findVersion($entity, $version);
    }
}
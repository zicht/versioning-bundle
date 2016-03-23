<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Model;


/**
 * Class EntityVersion
 *
 * @package Zicht\Bundle\VersioningBundle\Entity
 *
 * @ORM\Table(name="_entity_version")
 * @ORM\Entity(repositoryClass="EntityVersionRepository")
 * @ORM\ChangeTrackingPolicy(value="DEFERRED_EXPLICIT")
 */
interface EntityVersionInterface
{
    /**
     * @return \Datetime
     */
    public function getDateCreated();

    /**
     * @return string
     */
    public function getData();

    /**
     * @param string $data
     * @return void
     */
    public function setData($data);

    /**
     * @return int
     */
    public function getVersionNumber();

    /**
     * @param int $versionNumber
     * @return void
     */
    public function setVersionNumber($versionNumber);

    /**
     * @return int
     */
    public function getBasedOnVersion();

    /**
     * @param int $basedOnVersion
     * @return void
     */
    public function setBasedOnVersion($basedOnVersion);

    /**
     * @return boolean
     */
    public function isActive();

    /**
     * @param boolean $active
     * @return void
     */
    public function setIsActive($active);

    /**
     * @return int
     */
    public function getOriginalId();

    /**
     * @param int $originalId
     * @return void
     */
    public function setOriginalId($originalId);

    /**
     * @return string
     */
    public function getSourceClass();

    /**
     * @param string $sourceClass
     * @return void
     */
    public function setSourceClass($sourceClass);
}
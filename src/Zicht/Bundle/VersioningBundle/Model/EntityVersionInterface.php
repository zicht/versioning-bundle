<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Model;


/**
 * Class EntityVersion
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

    /**
     * @param string $changeset
     * @return mixed
     */
    public function setChangeset($changeset);

    /**
     * @return mixed
     */
    public function getChangeset();

    /**
     * @param string $username
     * @return void
     */
    public function setUsername($username);

    /**
     * @return void
     */
    public function getUsername();

    /**
     * @return \DateTime
     */
    public function getDateActiveFrom();

    /**
     * @param \DateTime $dateActiveFrom
     * @return mixed
     */
    public function setDateActiveFrom(\DateTime $dateActiveFrom);

    /**
     * @return mixed
     */
    public function getNotes();

    /**
     * @param string $notes
     * @return void
     */
    public function setNotes($notes);
}
<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionInterface;

/**
 * Class EntityVersion
 *
 * @package Zicht\Bundle\VersioningBundle\Entity
 *
 * @ORM\Table(name="_entity_version")
 * @ORM\Entity(repositoryClass="EntityVersionRepository")
 * @ORM\ChangeTrackingPolicy(value="DEFERRED_EXPLICIT")
 */
class EntityVersion implements EntityVersionInterface
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var \Datetime
     *
     * @ORM\Column(type="datetime", nullable=false)
     *
     */
    private $dateCreated;

    /**
     * @var string $data
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $data;

    /**
     * @var integer $versionNumber
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $versionNumber;

    /**
     * @var integer $basedOnVersion
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $basedOnVersion;

    /**
     * @var boolean $isActive
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isActive = false;

    /**
     * @var integer $originalId
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $originalId;

    /**
     * @var string $sourceClass
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $sourceClass;

    /**
     * @var
     * @ORM\Column(type="text")
     */
    private $notes;

    /**
     * EntityVersion constructor.
     */
    public function __construct()
    {
        $this->dateCreated = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Datetime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getVersionNumber()
    {
        return $this->versionNumber;
    }

    /**
     * @param int $versionNumber
     * @return void
     */
    public function setVersionNumber($versionNumber)
    {
        $this->versionNumber = $versionNumber;
    }

    /**
     * @return int
     */
    public function getBasedOnVersion()
    {
        return $this->basedOnVersion;
    }

    /**
     * @param int $basedOnVersion
     * @return void
     */
    public function setBasedOnVersion($basedOnVersion)
    {
        $this->basedOnVersion = $basedOnVersion;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @param boolean $active
     * @return void
     */
    public function setIsActive($active)
    {
        $this->isActive = $active;
    }

    /**
     * @return int
     */
    public function getOriginalId()
    {
        return $this->originalId;
    }

    /**
     * @param int $originalId
     * @return void
     */
    public function setOriginalId($originalId)
    {
        $this->originalId = $originalId;
    }

    /**
     * @return string
     */
    public function getSourceClass()
    {
        return $this->sourceClass;
    }

    /**
     * @param string $sourceClass
     * @return void
     */
    public function setSourceClass($sourceClass)
    {
        $this->sourceClass = $sourceClass;
    }
}
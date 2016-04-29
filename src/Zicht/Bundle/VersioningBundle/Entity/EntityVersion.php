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
     */
    private $dateCreated;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateActiveFrom;

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
     * @ORM\Column(type="text", nullable=true)
     */
    private $notes;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $changeset;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $username = null;

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

    /**
     * @return mixed
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param mixed $notes
     * @return void
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    /**
     * @return mixed
     */
    public function getChangeset()
    {
        return $this->changeset;
    }

    /**
     * @param mixed $changeset
     * @return void
     */
    public function setChangeset($changeset)
    {
        $this->changeset = $changeset;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     * @return void
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getDateActiveFrom()
    {
        return $this->dateActiveFrom;
    }

    /**
     * @param mixed $dateActiveFrom
     * @return void
     */
    public function setDateActiveFrom(\DateTime $dateActiveFrom = null)
    {
        $this->dateActiveFrom = $dateActiveFrom;
    }

    /**
     * Creates a volatile instance of the object. This is used for security checks.
     *
     * @return object
     */
    public function createVolatileInstance()
    {
        $refl = new \ReflectionClass($this->sourceClass);
        $object = $refl->newInstanceWithoutConstructor();

        $propIsSet = false;
        do {
            try {
                $prop = $refl->getProperty('id');
                $prop->setAccessible(true);
                $prop->setValue($object, $this->getOriginalId());
                $propIsSet = true;
            } catch (\ReflectionException $e) {
                $refl = $refl->getParentClass();
            }
        } while (!$propIsSet && $refl);

        if (!$propIsSet) {
            throw new \UnexpectedValueException("Could not figure out how to set the id property");
        }

        return $object;
    }
}
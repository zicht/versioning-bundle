<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Entity\Test;

use Doctrine\ORM\Mapping as ORM;
use Zicht\Bundle\VersioningBundle\Entity\IVersionable;

/**
 * Class Page
 *
 * @package Zicht\Bundle\VersioningBundle\Entity
 *
 * @ORM\Table(name="versioning_test_page")
 * @ORM\Entity(repositoryClass="Zicht\Bundle\VersioningBundle\Entity\Test\PageRepository")
 * @ORM\ChangeTrackingPolicy(value="DEFERRED_EXPLICIT")
 */
class Page implements IVersionable
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string $title
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $title;

    /**
     * @var string $introduction
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $introduction;

    /**
     * @var string $foo
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $foo;

    /**
     * @var boolean $booleanField
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $booleanField;

    /**
     * @var integer $integerField
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $integerField;

    /**
     * @param int $id
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getIntroduction()
    {
        return $this->introduction;
    }

    /**
     * @param string $introduction
     * @return void
     */
    public function setIntroduction($introduction)
    {
        $this->introduction = $introduction;
    }

    /**
     * @return string
     */
    public function getFoo()
    {
        return $this->foo;
    }

    /**
     * @param string $foo
     * @return void
     */
    public function setFoo($foo)
    {
        $this->foo = $foo;
    }

    /**
     * @return boolean
     */
    public function isBooleanField()
    {
        return $this->booleanField;
    }

    /**
     * @param boolean $booleanField
     * @return void
     */
    public function setBooleanField($booleanField)
    {
        $this->booleanField = $booleanField;
    }

    /**
     * @return int
     */
    public function getIntegerField()
    {
        return $this->integerField;
    }

    /**
     * @param int $integerField
     * @return void
     */
    public function setIntegerField($integerField)
    {
        $this->integerField = $integerField;
    }
}
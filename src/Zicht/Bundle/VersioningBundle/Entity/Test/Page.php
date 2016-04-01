<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Entity\Test;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;

/**
 * Class Page
 *
 * @package Zicht\Bundle\VersioningBundle\Entity\Test
 *
 * @ORM\Table(name="versioning_test_page")
 * @ORM\Entity(repositoryClass="Zicht\Bundle\VersioningBundle\Entity\Test\PageRepository")
 * @ORM\ChangeTrackingPolicy(value="DEFERRED_EXPLICIT")
 */
class Page implements VersionableInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
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
     * @ORM\OneToMany(targetEntity="Zicht\Bundle\VersioningBundle\Entity\Test\ContentItem", mappedBy="page", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $contentItems;

    /**
     * Page constructor.
     */
    public function __construct()
    {
        $this->contentItems = new ArrayCollection();
        $this->otherOneToManyRelations = new ArrayCollection();
        $this->nestedContentItems = new ArrayCollection();
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

    /**
     * Add ContentItem
     *
     * @param ContentItem $contentItem
     * @return Page
     */
    public function addContentItem(ContentItem $contentItem)
    {
        $contentItem->setPage($this);
        $this->contentItems[] = $contentItem;

        return $this;
    }

    /**
     * Remove ContentItem
     *
     * @param ContentItem $contentItem
     * @return void
     */
    public function removeContentItem(ContentItem $contentItem)
    {
        $this->contentItems->removeElement($contentItem);
    }

    /**
     * Get ContentItem
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContentItems()
    {
        return $this->contentItems;
    }


    /**
     * @return int
     */
    public function getTestingId()
    {
        return $this->testingId;
    }

    /**
     * @param int $testingId
     * @return void
     */
    public function setTestingId($testingId)
    {
        $this->testingId = $testingId;
    }
}
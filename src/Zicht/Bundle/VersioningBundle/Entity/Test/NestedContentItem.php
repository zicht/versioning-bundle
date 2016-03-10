<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Entity\Test;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Zicht\Bundle\VersioningBundle\Entity\IVersionable;
use Zicht\Bundle\VersioningBundle\Entity\IVersionableChild;

/**
 * Class NestedContentItem
 *
 * @package Zicht\Bundle\VersioningBundle\Entity\Test
 *
 * @ORM\Table(name="versioning_test_nested_ci")
 * @ORM\Entity()
 * @ORM\ChangeTrackingPolicy(value="DEFERRED_EXPLICIT")
 */
class NestedContentItem implements IVersionableChild
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
     * @var string $otherField
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $otherField;

     /**
     * @ORM\OneToMany(targetEntity="Zicht\Bundle\VersioningBundle\Entity\Test\ChildOfNestedContentItem", mappedBy="parentContentItem", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $childContentItems;

     /**
     * @ORM\ManyToOne(targetEntity="Zicht\Bundle\VersioningBundle\Entity\Test\Page", inversedBy="NestedContentItems")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $page;

    /**
     * NestedContentItem constructor.
     */
    public function __construct()
    {
        $this->childContentItems =  new ArrayCollection();
    }
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getOtherField()
    {
        return $this->otherField;
    }

    /**
     * @param mixed $otherField
     * @return void
     */
    public function setOtherField($otherField)
    {
        $this->otherField = $otherField;
    }

    /**
     * @return mixed
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param mixed $page
     * @return void
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * @return IVersionable
     */
    public function getParent()
    {
        return $this->page;
    }

      /**
     * Add ChildOfNestedContentItem
     *
     * @param ChildOfNestedContentItem $contentItem
     * @return NestedContentItem
     */
    public function addChildContentItem(ChildOfNestedContentItem $contentItem)
    {
        $contentItem->setParentContentItem($this);
        $this->childContentItems[] = $contentItem;

        return $this;
    }

    /**
     * Remove ChildOfNestedContentItem
     *
     * @param ChildOfNestedContentItem $contentItem
     */
    public function removeChildContentItem(ChildOfNestedContentItem $contentItem)
    {
        $this->childContentItems->removeElement($contentItem);
    }

    /**
     * Get ChildOfNestedContentItem
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildContentItems()
    {
        return $this->childContentItems;
    }
}
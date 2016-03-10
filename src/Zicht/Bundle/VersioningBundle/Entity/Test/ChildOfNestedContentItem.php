<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Entity\Test;

use Doctrine\ORM\Mapping as ORM;
use Zicht\Bundle\VersioningBundle\Entity\IVersionable;
use Zicht\Bundle\VersioningBundle\Entity\IVersionableChild;

/**
 * Class ChildOfNestedContentItem
 *
 * @package Zicht\Bundle\VersioningBundle\Entity\Test
 *
 * @ORM\Table(name="versioning_test_childof_nested_ci")
 * @ORM\Entity()
 * @ORM\ChangeTrackingPolicy(value="DEFERRED_EXPLICIT")
 */
class ChildOfNestedContentItem implements IVersionableChild
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
     * @ORM\ManyToOne(targetEntity="Zicht\Bundle\VersioningBundle\Entity\Test\NestedContentItem", inversedBy="childContentItems")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $parentContentItem;

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
    public function getParentContentItem()
    {
        return $this->parentContentItem;
    }

    /**
     * @param mixed $parentContentItem
     * @return void
     */
    public function setParentContentItem($parentContentItem)
    {
        $this->parentContentItem = $parentContentItem;
    }

    /**
     * @return IVersionable
     */
    public function getParent()
    {
        return $this->parentContentItem;
    }
}
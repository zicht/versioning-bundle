<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Entity\Test;

use Doctrine\ORM\Mapping as ORM;
use Zicht\Bundle\VersioningBundle\Model\EmbeddedVersionableInterface;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;

/**
 * Class ContentItem
 *
 * @package Zicht\Bundle\VersioningBundle\Entity\Test
 *
 * @ORM\Table(name="versioning_test_contentitem")
 * @ORM\Entity()
 * @ORM\ChangeTrackingPolicy(value="DEFERRED_EXPLICIT")
 */
class ContentItem implements EmbeddedVersionableInterface
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
     * @var string $otherField
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $otherField;

     /**
     * @ORM\ManyToOne(targetEntity="Zicht\Bundle\VersioningBundle\Entity\Test\Page", inversedBy="contentItems")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $page;

    /**
     * Construct the item and initial optionally with a title
     *
     * @param null $title
     */
    public function __construct($title = null)
    {
        if (null !== $title) {
            $this->setTitle($title);
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return VersionableInterface
     */
    public function getVersionableParent()
    {
        return $this->page;
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

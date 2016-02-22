<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Page
 *
 * @package Zicht\Bundle\VersioningBundle\Entity
 *
 * @ORM\Table(name="versioning_test_page")
 * @ORM\Entity(repositoryClass="PageRepository", )
 * @ORM\ChangeTrackingPolicy(value="DEFERRED_EXPLICIT")
 */
class Page
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
     * @var string $title
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $title;

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
}
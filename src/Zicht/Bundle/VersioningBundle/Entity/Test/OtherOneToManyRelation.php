<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Entity\Test;

use Doctrine\ORM\Mapping as ORM;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;
use Zicht\Bundle\VersioningBundle\Model\VersionableChildInterface;

/**
 * Class OtherOneToManyRelation
 *
 * @package Zicht\Bundle\VersioningBundle\Entity\Test
 *
 * @ORM\Table(name="versioning_test_other_otmr")
 * @ORM\Entity()
 * @ORM\ChangeTrackingPolicy(value="DEFERRED_EXPLICIT")
 */
class OtherOneToManyRelation implements VersionableChildInterface
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
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $testingId;

    /**
     * @var string $titelo
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $titelo;

    /**
     * @var string $bladie
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $bladie;

     /**
     * @ORM\ManyToOne(targetEntity="Zicht\Bundle\VersioningBundle\Entity\Test\Page", inversedBy="otherOneToManyRelations")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $page;

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
    public function getTitelo()
    {
        return $this->titelo;
    }

    /**
     * @param mixed $titelo
     * @return void
     */
    public function setTitelo($titelo)
    {
        $this->titelo = $titelo;
    }

    /**
     * @return mixed
     */
    public function getBladie()
    {
        return $this->bladie;
    }

    /**
     * @param mixed $bladie
     * @return void
     */
    public function setBladie($bladie)
    {
        $this->bladie = $bladie;
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
    public function getParent()
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
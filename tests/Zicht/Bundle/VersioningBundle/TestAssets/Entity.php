<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\TestAssets;

use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;

class Entity implements VersionableInterface
{
    protected $bool = null;

    public function __construct()
    {
        $this->id = rand(1, 100);
    }

    public function getId()
    {
        return $this->id;
    }

    protected $object = null;

    private $other;
    private $others;

    private $priv = 1;

    public function setObject($entity)
    {
        $this->object = $entity;
    }


    public function getObject()
    {
        return $this->object;
    }

    public function setBool($bool)
    {
        $this->bool = $bool;
    }


    public function getBool()
    {
        return $this->bool;
    }


    public function setOther($o)
    {
        $this->other = $o;
    }

    public function getOther()
    {
        return $this->other;
    }


    public function addOther($o)
    {
        return $this->others[]= $o;
    }

    public function getOthers()
    {
        return $this->others;
    }

    public function setOthers($others)
    {
        $this->others = $others;
    }

    public function getPrivateValue()
    {
        return $this->priv;
    }

    public function __clone()
    {
    }
}



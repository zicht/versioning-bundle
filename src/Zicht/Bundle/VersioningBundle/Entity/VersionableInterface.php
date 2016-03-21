<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Entity;

interface VersionableInterface
{
    //not much here - just something to be able to 'mark' entities with ^^

    //we need an id, to determine the original source
    public function getId();
}
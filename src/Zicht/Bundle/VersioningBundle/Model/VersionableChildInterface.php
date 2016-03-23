<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Model;

interface VersionableChildInterface extends VersionableInterface
{
    //not much here - just something to be able to 'mark' entities with ^^

    /**
     * @return VersionableInterface
     */
    public function getParent();
}
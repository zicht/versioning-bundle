<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Entity;

interface IVersionableChild extends IVersionable
{
    //not much here - just something to be able to 'mark' entities with ^^

    /**
     * @return IVersionable
     */
    public function getParent();
}
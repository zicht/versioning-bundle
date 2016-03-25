<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Model;

/**
 * Interface VersionableInterface
 */
interface VersionableInterface
{
    /**
     * Anything versionable must have an id.
     *
     * @return mixed
     */
    public function getId();
}
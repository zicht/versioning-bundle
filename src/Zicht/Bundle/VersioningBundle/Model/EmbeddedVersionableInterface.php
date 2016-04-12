<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Model;

/**
 * Provides an interface for objects that are part of the parent; i.e. a OnetoMany relation that needs to
 * be subject to versioning
 */
interface EmbeddedVersionableInterface
{
    /**
     * @return VersionableInterface
     */
    public function getVersionableParent();
}
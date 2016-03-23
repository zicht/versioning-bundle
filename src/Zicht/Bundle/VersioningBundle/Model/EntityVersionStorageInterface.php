<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Model;

interface EntityVersionStorageInterface
{
    public function findVersions(VersionableInterface $object);
}
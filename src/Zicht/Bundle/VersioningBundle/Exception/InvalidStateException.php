<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Exception;

/**
 * Thrown whenever an object is in a state that is ambiguous or invalid.
 *
 * @package Zicht\Bundle\VersioningBundle\Exception
 */
class InvalidStateException extends \LogicException
{
}

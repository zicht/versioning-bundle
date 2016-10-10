<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Exception;

/**
 * Thrown whenever the version manager is asked to do something it does not understand.
 */
class UnsupportedVersionOperationException extends \UnexpectedValueException
{
}

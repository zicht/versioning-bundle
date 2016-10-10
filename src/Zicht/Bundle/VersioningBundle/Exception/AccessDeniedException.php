<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Exception;

use Symfony\Component\Security\Core\Exception\AccessDeniedException as BaseException;

/**
 * Thrown whenever the version manager is asked to do something it can not allow
 */
class AccessDeniedException extends BaseException
{
}

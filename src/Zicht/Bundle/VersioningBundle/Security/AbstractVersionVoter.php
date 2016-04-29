<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Security;


use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionInterface;

/**
 * Base class for voters supporting EntityVersionInterface instances
 */
abstract class AbstractVersionVoter implements VoterInterface
{
    /**
     * @{inheritDoc}
     */
    public function supportsClass($class)
    {
        return (new \ReflectionClass($class))->implementsInterface(EntityVersionInterface::class);
    }
}
<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionInterface;

/**
 * Implements a voter which handles the checks on EntityVersionInterface objects
 */
class Voter implements VoterInterface
{
    /**
     * @{inheritDoc}
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, ['EDIT', 'VIEW']);
    }

    /**
     * @{inheritDoc}
     */
    public function supportsClass($class)
    {
        return (new \ReflectionClass($class))->implementsInterface(EntityVersionInterface::class);
    }

    /**
     * @{inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$this->supportsClass(get_class($object))) {
            return self::ACCESS_ABSTAIN;
        }


        foreach ($attributes as $attribute) {
            if ($this->supportsAttribute($attribute)) {
                switch ($attribute) {
                    case 'EDIT':
                    case 'VIEW':
                        if ($token->getUsername() === $object->getUsername()) {
                            return self::ACCESS_GRANTED;
                        }
                        break;
                }
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
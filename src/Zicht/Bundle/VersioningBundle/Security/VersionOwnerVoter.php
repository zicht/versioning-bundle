<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Implements a voter which handles the checks on EntityVersionInterface objects
 */
class VersionOwnerVoter extends AbstractVersionVoter
{
    /**
     * Construct the voter with the specified attributes to grant access for when
     * the current user is owner of the version.
     *
     * @param string[] $grantedOwnerAttributes
     */
    public function __construct($grantedOwnerAttributes)
    {
        $this->attributes = $grantedOwnerAttributes;
    }


    /**
     * @{inheritDoc}
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, $this->attributes);
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
            if ($this->supportsAttribute($attribute) && $token->getUsername() === $object->getUsername()) {
                return self::ACCESS_GRANTED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
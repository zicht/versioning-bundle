<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionInterface;

/**
 * Denies delete on active versions
 */
class VersionDeleteDenyVoter extends AbstractVersionVoter
{
    public function supportsAttribute($attribute)
    {
        return $attribute === 'DELETE';
    }

    /**
     * @param TokenInterface $token
     * @param EntityVersionInterface $object
     * @param array $attributes
     * @return int
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$this->supportsClass(get_class($object))) {
            return self::ACCESS_ABSTAIN;
        }
        foreach ($attributes as $attr) {
            if ($this->supportsAttribute($attr)) {
                if ($object->isActive()) {
                    return self::ACCESS_DENIED;
                }
            }
        }


        return self::ACCESS_ABSTAIN;
    }
}
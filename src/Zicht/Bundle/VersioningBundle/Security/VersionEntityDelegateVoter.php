<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Security;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionInterface;

/**
 * Implements a voter which delegates the check on a version to check an attribute of the entity of that version.
 *
 * For example: Editing a version may be delegated to the ADMIN attribute on the entity itself.
 */
class VersionEntityDelegateVoter extends AbstractVersionVoter
{
    /**
     * Construct
     *
     * @param ContainerInterface $container
     * @param string[][] $attributeMap
     */
    public function __construct(ContainerInterface $container, $attributeMap)
    {
        $this->container = $container;
        $this->attributeMap = $attributeMap;
    }

    /**
     * @{inheritDoc}
     */
    public function supportsAttribute($attribute)
    {
        return array_key_exists($attribute, $this->attributeMap);
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
                if ($this->container->get('security.authorization_checker')->isGranted($this->attributeMap[$attribute], $object->createVolatileInstance())) {
                    return self::ACCESS_GRANTED;
                }
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}

<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;


/**
 * Little wrapper to wrap available version operations in a choice
 */
class VersionOperationType extends AbstractType
{
    /**
     * @{inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('operations')
            ->setDefaults([
                'label' => 'form_label.operation',
                'choices' =>
                    function(Options $options) {
                        $ret = [];
                        foreach ($options['operations'] as $op) {
                            $ret[$op]= 'admin.versioning.operation.' . $op;
                        }
                        return $ret;
                    },
                'translation_domain' => 'admin'
            ])
        ;
    }

    /**
     * @{inheritDoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * @{inheritDoc}
     */
    public function getName()
    {
        return 'zicht_version_operation_choice';
    }
}
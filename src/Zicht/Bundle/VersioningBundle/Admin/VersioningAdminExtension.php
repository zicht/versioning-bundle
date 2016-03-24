<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Admin;

use Sonata\AdminBundle\Admin\AdminExtension;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class VersioningAdminExtension extends AdminExtension
{
    public function configureFormFields(FormMapper $form)
    {
        $form
            ->tab('versions')
                ->with('versions')
                    ->add('versions', 'zicht_version')
                ->end()
            ->end()
        ;
    }
}

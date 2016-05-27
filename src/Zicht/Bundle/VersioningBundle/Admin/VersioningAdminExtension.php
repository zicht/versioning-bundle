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

/**
 * This extension loads an extra tab with versions and configures an extra route to show versions
 */
class VersioningAdminExtension extends AdminExtension
{
    /**
     * @{inheritDoc}
     */
    public function configureFormFields(FormMapper $form)
    {
        $form
            ->tab('admin.tab.general')
                ->with('admin.versioning.section', ['collapsed' => true])
                    ->add('versions', 'zicht_version')
                ->end()
            ->end()
        ;
    }

    /**
     * @{inheritDoc}
     */
    public function configureRoutes(AdminInterface $admin, RouteCollection $collection)
    {
        $collection
            ->add('versions', $admin->getRouterIdParameter().'/versions.{_format}', ['_controller' => 'ZichtVersioningBundle:SonataCRUD:versions'])
            ->add('deleteversion', $admin->getRouterIdParameter().'/delete-version/{version_number}', ['_controller' => 'ZichtVersioningBundle:SonataCRUD:deleteVersion']);
    }
}

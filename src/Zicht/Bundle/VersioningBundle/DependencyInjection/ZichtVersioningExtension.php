<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension as DIExtension;

/**
 * Class ZichtVersioningExtension
 */
class ZichtVersioningExtension extends DIExtension
{
    /**
     * @{inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        /*
         * This is loaded, even when the versioning isn't enabled.
         * This is needed to support parsing the one-to-many in the HelperController
         */
        $loader->load('sonata_helper.xml');

        if (!$config['enabled']) {
            return;
        }

        $loader->load('services.xml');
        $loader->load('commands.xml');
        $loader->load('controllers.xml');
        $loader->load('admin.xml');
        $loader->load('form.xml');
        $loader->load('twig.xml');
        $loader->load('security.xml');

        $container->getDefinition('zicht_versioning.security.version_owner_voter')
            ->replaceArgument(0, ['EDIT', 'VIEW', 'DELETE'])
        ;
        $container->getDefinition('zicht_versioning.security.version_entity_delegate_voter')
            ->replaceArgument(1, ['EDIT' => ['ADMIN'], 'VIEW' => ['ADMIN'], 'DELETE' => ['ADMIN']])
        ;

        $formResources = $container->getParameter('twig.form.resources');
        $formResources[]= 'ZichtVersioningBundle::form_theme.html.twig';
        $container->setParameter('twig.form.resources', $formResources);
    }
}

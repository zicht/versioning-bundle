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

class ZichtVersioningExtension extends DIExtension
{
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('commands.xml');
        $loader->load('admin.xml');
        $loader->load('form.xml');
        $loader->load('twig.xml');

        $formResources = $container->getParameter('twig.form.resources');
        $formResources[]= 'ZichtVersioningBundle::form_theme.html.twig';
        $container->setParameter('twig.form.resources', $formResources);
    }
}

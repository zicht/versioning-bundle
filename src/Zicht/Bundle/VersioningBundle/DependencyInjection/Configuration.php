<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */


namespace Zicht\Bundle\VersioningBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Page bundle configuration
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @{inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('zicht_versioning');

        $rootNode
            ->canBeDisabled()
        ;

        return $treeBuilder;
    }
}

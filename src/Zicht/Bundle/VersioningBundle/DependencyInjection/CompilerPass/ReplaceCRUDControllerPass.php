<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;

class ReplaceCRUDControllerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        /** @var Definition[] $childDefs */
        $childDefs = [];

        foreach ($container->findTaggedServiceIds('sonata.admin') as $serviceId => $tags) {
            $def = $container->getDefinition($serviceId);
            $args = $def->getArguments();

            list(, $entityClass) = $container->getParameterBag()->resolveValue($args);

            $refl = new \ReflectionClass($entityClass);

            if ($refl->implementsInterface(VersionableInterface::class)) {
                foreach ($def->getMethodCalls() as list($method, $args)) {

                    // make sure all child admins that are version-managed get a different controller implementation
                    if ($method === 'addChild') {
                        /** @var Reference $ref */
                        $ref = $args[0];
                        $childDefs[(string)$ref]= $container->getDefinition((string)$ref);
                    }
                    if ($method === 'setRouteGenerator') {
                        $def->removeMethodCall('setRouteGenerator');
                        $def->addMethodCall('setRouteGenerator', [new Reference('zicht_versioning.admin.route_generator')]);
                    }
                }
            }
        }

        foreach ($childDefs as $serviceId => $childDef) {
//            $container->getParameterBag()->resolveValue($childDef->getArguments());
//            $childDef->replaceArgument(2, 'ZichtVersioningBundle:CRUD');
            $childDef->addMethodCall('addExtension', [new Reference('zicht_versioning.admin.versioning_child_extension')]);

            $childDef->removeMethodCall('setRouteGenerator');
            $childDef->addMethodCall('setRouteGenerator', [new Reference('zicht_versioning.admin.route_generator')]);
        }
    }
}
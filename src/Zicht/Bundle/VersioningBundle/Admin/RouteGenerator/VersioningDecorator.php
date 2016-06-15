<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Admin\RouteGenerator;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Route\RouteGeneratorInterface;
use Zicht\Bundle\VersioningBundle\Http\UrlHelper;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;
use Zicht\Bundle\VersioningBundle\Model\EmbeddedVersionableInterface;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;


/**
 * Decorates the RouteGeneratorInterface with extra parameters added to the URL for specific (explicit) versions
 * to be loaded for objects that were loaded as a specific version in the versioningmanager.
 *
 * Only generateUrl() is overridden, the rest is delegated to the wrapped RouteGeneratorInterface
 */
class VersioningDecorator implements RouteGeneratorInterface
{
    /**
     * Constructor
     *
     * @param RouteGeneratorInterface $generator
     * @param VersioningManager $versioning
     * @param UrlHelper $urlHelper
     */
    public function __construct(RouteGeneratorInterface $generator, VersioningManager $versioning, UrlHelper $urlHelper)
    {
        $this->generator = $generator;
        $this->versioning = $versioning;
        $this->urlHelper = $urlHelper;
    }


    /**
     * @{inheritDoc}
     */
    public function generateUrl(AdminInterface $admin, $name, array $parameters = array(), $absolute = false)
    {
        $url = $this->generator->generateUrl($admin, $name, $parameters, $absolute);

        $refl = (new \ReflectionClass($admin->getClass()));
        if (
            ($refl->implementsInterface(EmbeddedVersionableInterface::class) && in_array($name, ['edit', 'list', 'create']))
            || ($refl->implementsInterface(VersionableInterface::class) && in_array($name, ['show', 'edit']))
        ) {
            if ($versions = $this->versioning->getAffectedVersions()) {
                $url = $this->urlHelper->decorateVersionsUrl(
                    $url,
                    array_column($versions, 1)
                );
            } elseif ($versions = $this->versioning->getLoadedVersions()) {
                $url = $this->urlHelper->decorateVersionsUrl(
                    $url,
                    $versions
                );
            }
        }
        return $url;
    }

    /**
     * @{inheritDoc}
     */
    public function generateMenuUrl(AdminInterface $admin, $name, array $parameters = array(), $absolute = false)
    {
        return $this->generator->generateMenuUrl($admin, $name, $parameters, $absolute);
    }

    /**
     * @{inheritDoc}
     */
    public function generate($name, array $parameters = array(), $absolute = false)
    {
        return $this->generator->generate($name, $parameters, $absolute);
    }

    /**
     * @{inheritDoc}
     */
    public function hasAdminRoute(AdminInterface $admin, $name)
    {
        return $this->generator->hasAdminRoute($admin, $name);
    }
}
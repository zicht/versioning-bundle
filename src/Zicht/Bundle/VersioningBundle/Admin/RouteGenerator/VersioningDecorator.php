<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Admin\RouteGenerator;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Route\RouteGeneratorInterface;
use Zicht\Bundle\RcoSiteBundle\Admin\ContentItem\ContentItemDetailAdmin;
use Zicht\Bundle\VersioningBundle\Http\UrlHelper;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;
use Zicht\Bundle\VersioningBundle\Model\EmbeddedVersionableInterface;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;

class VersioningDecorator implements RouteGeneratorInterface
{
    public function __construct(RouteGeneratorInterface $generator, VersioningManager $versioning, UrlHelper $urlHelper)
    {
        $this->generator = $generator;
        $this->versioning = $versioning;
        $this->urlHelper = $urlHelper;
    }


    public function generateUrl(AdminInterface $admin, $name, array $parameters = array(), $absolute = false)
    {
        $url = $this->generator->generateUrl($admin, $name, $parameters, $absolute);

        if (
            ((new \ReflectionClass($admin->getClass()))->implementsInterface(EmbeddedVersionableInterface::class) && in_array($name, ['edit', 'list', 'create']))
            || ((new \ReflectionClass($admin->getClass()))->implementsInterface(VersionableInterface::class) && in_array($name, ['show', 'edit']))
        ) {
            if ($this->versioning->getAffectedVersions()) {
                $url = $this->urlHelper->decorateVersionsUrl(
                    $url,
                    array_column($this->versioning->getAffectedVersions(), 1)
                );
            } else {
                $url = $this->urlHelper->decorateVersionsUrl(
                    $url,
                    $this->versioning->getLoadedVersions()
                );
            }

            return $url;
        }
        return $url;
    }

    public function generateMenuUrl(AdminInterface $admin, $name, array $parameters = array(), $absolute = false)
    {
        return $this->generator->generateMenuUrl($admin, $name, $parameters, $absolute);
    }

    public function generate($name, array $parameters = array(), $absolute = false)
    {
        return $this->generator->generate($name, $parameters, $absolute);
    }

    public function hasAdminRoute(AdminInterface $admin, $name)
    {
        return $this->generator->hasAdminRoute($admin, $name);
    }
}
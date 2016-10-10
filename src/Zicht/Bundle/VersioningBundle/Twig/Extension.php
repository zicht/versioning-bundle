<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Twig;

use Zicht\Bundle\VersioningBundle\Http\UrlHelper;

/**
 * Twig extension for versioning related functions:
 *
 * - version_url() decorates any url with the version specified; delegates to UrlHelper::decorateVersionUrl
 */
class Extension extends \Twig_Extension
{
    /**
     * Construct the extension
     *
     * @param UrlHelper $helper
     */
    public function __construct(UrlHelper $helper)
    {
        $this->urlHelper = $helper;
    }

    /**
     * @{inheritDoc}
     */
    public function getFunctions()
    {
        return [
            'version_url' => new \Twig_SimpleFunction('version_url', [$this->urlHelper, 'decorateVersionUrl'])
        ];
    }

    /**
     * @{inheritDoc}
     */
    public function getName()
    {
        return 'zicht_versioning';
    }
}

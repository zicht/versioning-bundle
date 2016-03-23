<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Twig;

use Zicht\Bundle\VersioningBundle\Http\UrlHelper;

class Extension extends \Twig_Extension
{
    function __construct(UrlHelper $helper)
    {
        $this->urlHelper = $helper;
    }


    public function getFunctions()
    {
        return [
            'version_url' => new \Twig_SimpleFunction('version_url', function($url, $entityVersion) {
                return $this->urlHelper->decorateVersionUrl($url, $entityVersion);
            })
        ];
    }

    public function getName()
    {
        return 'zicht_versioning';
    }
}
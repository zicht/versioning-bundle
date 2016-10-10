<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Http;

use Symfony\Component\HttpFoundation\Request;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionInterface;
use Zicht\Util\Url;

/**
 * Helper class for handling version-decorated URL's
 *
 * @package Zicht\Bundle\VersioningBundle\Http
 */
class UrlHelper
{
    /**
     * Constructor
     *
     * @param string $paramName
     */
    public function __construct($paramName = '__v__')
    {
        $this->paramName = $paramName;
    }


    /**
     * Adds parameters to an url which can be used to determine specific entity versions to be loaded in a request.
     *
     * @param string $url
     * @param EntityVersionInterface $version
     * @return string
     */
    public function decorateVersionUrl($url, EntityVersionInterface $version)
    {
        return $this->decorateVersionsUrl($url, [$version]);
    }


    /**
     * Adds versions of multiple entities to a URL
     *
     * @param string $url
     * @param EntityVersionInterface[] $versions
     * @return string
     */
    public function decorateVersionsUrl($url, array $versions)
    {
        $url = new Url($url);
        $versionInfo = $url->getParam($this->paramName, []);
        foreach ($versions as $version) {
            if ($version->isActive()) {
                unset($versionInfo[$version->getSourceClass()][$version->getOriginalId()]);
            } else {
                $versionInfo[$version->getSourceClass()][$version->getOriginalId()] = $version->getVersionNumber();
            }
        }
        $url->setParam($this->paramName, $versionInfo);
        return (string)$url;
    }


    /**
     * Get the version info from a request which may contain a version-decorated URL
     *
     * @param Request $request
     * @return array
     */
    public function extractVersionInfo(Request $request)
    {
        $ret = [];
        foreach ($request->query->get($this->paramName, []) as $entityName => $idToVersionMap) {
            if (is_array($idToVersionMap)) {
                foreach ($idToVersionMap as $id => $version) {
                    $ret[$entityName][(int)$id] = (int)$version;
                }
            }
        }
        return $ret;
    }
}

<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Http;

use Symfony\Component\HttpFoundation\Request;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionInterface;
use Zicht\Util\Url;

class UrlHelper
{
    public function __construct($paramName = '__v__')
    {
        $this->paramName = $paramName;
    }

    public function decorateVersionUrl($url, EntityVersionInterface $version)
    {
        return $this->decorateVersionsUrl($url, [$version]);
    }

    /**
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
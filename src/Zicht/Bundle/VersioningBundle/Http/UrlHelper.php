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
        $url = new Url($url);
        $versionInfo = $url->getParam($this->paramName, []);
        $versionInfo[$version->getSourceClass()][$version->getOriginalId()] = $version->getVersionNumber();
        $url->setParam($this->paramName, $versionInfo);
        return (string)$url;
    }


    public function extractVersionInfo(Request $request)
    {
        $ret = [];
        foreach ($request->query->get($this->paramName, []) as $entityName => $idToVersionMap) {
            foreach ($idToVersionMap as $id => $version) {
                $ret[$entityName][(int)$id] = (int)$version;
            }
        }
        return $ret;
    }
}
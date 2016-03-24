<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Http;

use Symfony\Component\HttpFoundation\Request;
use Zicht\Bundle\RcoSiteBundle\Entity\Page\HomePage;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;

class UrlHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testUrlHelper()
    {
        $helper = new UrlHelper();

        $v = new EntityVersion();
        $v->setIsActive(false);
        $v->setVersionNumber(5678);
        $v->setOriginalId(1234);
        $v->setSourceClass(HomePage::class);

        $decorated = $helper->decorateVersionUrl('http://example.org/url?param1=param2', $v);

        $query = parse_url($decorated, PHP_URL_QUERY);
        $params = [];
        parse_str($query, $params);
        $request = new Request($params);

        $versionInfo = $helper->extractVersionInfo($request);

        $this->assertArrayHasKey(HomePage::class, $versionInfo);
        $this->assertArrayHasKey(1234, $versionInfo[HomePage::class]);
        $this->assertEquals(5678, $versionInfo[HomePage::class][1234]);
    }


    public function testUrlHelperIgnoresVersionIdIfIsActive()
    {
        $helper = new UrlHelper();

        $v = new EntityVersion();
        $v->setIsActive(true);
        $v->setVersionNumber(5678);
        $v->setOriginalId(1234);
        $v->setSourceClass(HomePage::class);

        $decorated = $helper->decorateVersionUrl('http://example.org/url?param1=param2', $v);
        $this->assertEquals('http://example.org/url?param1=param2', $decorated);
    }
}
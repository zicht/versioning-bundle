<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Http;

use Zicht\Bundle\RcoSiteBundle\Entity\Page\HomePage;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;

class UrlTest extends \PHPUnit_Framework_TestCase
{
    public function testAddUrlParams()
    {
        $helper = new UrlHelper();

        $v = new EntityVersion();
        $v->setVersionNumber(5678);
        $v->setOriginalId(1234);
        $v->setSourceClass(HomePage::class);

        var_dump($helper->decorateVersionUrl('/', $v));
    }
}
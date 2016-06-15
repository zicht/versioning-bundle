<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Admin\RouteGenerator;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Route\RouteGeneratorInterface;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Http\UrlHelper;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;
use Zicht\Bundle\VersioningBundle\TestAssets\Entity;


/**
 * @covers Zicht\Bundle\VersioningBundle\Admin\RouteGenerator\VersioningDecorator
 */
class VersioningDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var VersioningManager
     */
    private $versioning;
    /**
     * @var RouteGeneratorInterface
     */
    private $generator;
    /**
     * @var UrlHelper
     */
    private $urlHelper;

    /**
     * @var VersioningDecorator
     */
    private $decorator;

    public function setUp()
    {
        $this->generator = $this->getMockBuilder(RouteGeneratorInterface::class)->getMock();
        $this->versioning = $this->getMockBuilder(VersioningManager::class)->setMethods(['getAffectedVersions', 'getLoadedVersions'])->disableOriginalConstructor()->getMock();
        $this->urlHelper = $this->getMockBuilder(UrlHelper::class)->setMethods(['decorateVersionsUrl'])->getMock();

        $this->decorator = new VersioningDecorator($this->generator, $this->versioning, $this->urlHelper);
        $this->admin = $this->getMockBuilder(Admin::class)->disableOriginalConstructor()->getMock();
        $this->admin->expects($this->any())->method('getClass')->will($this->returnValue(Entity::class));
    }

    public function testGenerateUrlWithoutVersion()
    {
        $generatedUrl = 'the-url';
        $this->generator->expects($this->once())->method('generateUrl')->with($this->admin, 'edit', ['a' => 'b'], true)->will($this->returnValue($generatedUrl));
        $this->assertEquals($generatedUrl, $this->decorator->generateUrl($this->admin, 'edit', ['a' => 'b'], true));
    }

    public function testGenerateUrlWithAffectedVersions()
    {
        $generatedUrl = 'the-url';
        $decoratedUrl = 'the-decorated-url';
        $this->generator->expects($this->once())->method('generateUrl')->with($this->admin, 'edit', ['a' => 'b'], true)->will($this->returnValue($generatedUrl));
        $version = new EntityVersion();
        $version->setVersionNumber(rand());
        $this->urlHelper->expects($this->once())->method('decorateVersionsUrl')->with('the-url', [$version])->will($this->returnValue($decoratedUrl));
        $this->versioning->expects($this->once())->method('getAffectedVersions')->will($this->returnValue([[new \stdClass, $version]]));
        $this->assertEquals($decoratedUrl, $this->decorator->generateUrl($this->admin, 'edit', ['a' => 'b'], true));
    }

    public function testGenerateUrlWithLoadedVersion()
    {
        $generatedUrl = 'the-url';
        $decoratedUrl = 'the-decorated-url';
        $this->generator->expects($this->once())->method('generateUrl')->with($this->admin, 'edit', ['a' => 'b'], true)->will($this->returnValue($generatedUrl));
        $version = new EntityVersion();
        $version->setVersionNumber(rand());
        $this->urlHelper->expects($this->once())->method('decorateVersionsUrl')->with('the-url', [$version])->will($this->returnValue($decoratedUrl));
        $this->versioning->expects($this->once())->method('getLoadedVersions')->will($this->returnValue([$version]));
        $this->assertEquals($decoratedUrl, $this->decorator->generateUrl($this->admin, 'edit', ['a' => 'b'], true));
    }

    public function testGenerateUrlWithLoadedVersionDoesNotDecorateUrlForUnknownAction()
    {
        $generatedUrl = 'the-url';
        $decoratedUrl = 'the-decorated-url';
        $action = 'bogus';

        $this->generator->expects($this->once())->method('generateUrl')->with($this->admin, $action, ['a' => 'b'], true)->will($this->returnValue($generatedUrl));
        $version = new EntityVersion();
        $version->setVersionNumber(rand());
        $this->urlHelper->expects($this->never())->method('decorateVersionsUrl')->with('the-url', [$version])->will($this->returnValue($decoratedUrl));
        $this->versioning->expects($this->any())->method('getLoadedVersions')->will($this->returnValue([$version]));
        $this->assertEquals($generatedUrl, $this->decorator->generateUrl($this->admin, $action, ['a' => 'b'], true));
    }

    /**
     * @dataProvider methods
     */
    public function testOtherGeneratorsAreUnaffected($method, $parameters)
    {
        if (null === $parameters[0]) {
            $parameters[0] = $this->admin;
        }

        $return = rand(0, 100);
        $this->generator->expects($this->once())->method($method)->with(...$parameters)->will($this->returnValue($return));
        $this->assertEquals($return, $this->decorator->$method(...$parameters));
    }

    /**
     */
    public function methods()
    {
        return [
            ['generateMenuUrl', [null, 'test', ['foo' => 'bar'], true]],
            ['generateMenuUrl', [null, 'test', [], false]],
            ['generate', ['test', ['foo' => 'bar'], true]],
            ['generate', ['test', [], false]],
            ['hasAdminRoute', [null, 'foo']],
            ['hasAdminRoute', [null, 'bar']]
        ];
    }
}
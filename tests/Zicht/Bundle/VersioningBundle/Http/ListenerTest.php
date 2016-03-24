<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;


/**
 * @covers Zicht\Bundle\VersioningBundle\Http\Listener
 */
class ListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $event;
    protected $urlHelper;
    protected $versionManager;

    public function setUp()
    {
        $this->versionManager = $this->getMock(VersioningManager::class, ['setVersionToLoad'], [], '', false);
        $this->event = $this->getMock(GetResponseEvent::class, ['isMasterRequest', 'getRequest'], [], '', false);
        $this->urlHelper = $this->getMock(UrlHelper::class, ['extractVersionInfo']);
    }


    public function testListenerDoesNotActivateOnSubRequest()
    {
        $this->event->expects($this->once())->method('isMasterRequest')->will($this->returnValue(false));
        $this->urlHelper->expects($this->never())->method('extractVersionInfo');

        (new Listener($this->versionManager, $this->urlHelper))->onKernelRequest($this->event);
    }

    public function testListenerDoesActivateOnMasterRequest()
    {
        $this->event->expects($this->once())->method('isMasterRequest')->will($this->returnValue(true));
        $req = new Request();
        $this->event->expects($this->once())->method('getRequest')->will($this->returnValue($req));
        $this->urlHelper->expects($this->once())->method('extractVersionInfo')->with($req)->will($this->returnValue(
            [
                'foo' => [
                    12 => 34,
                    56 => 78
                ],
                'bar' => [
                    1234 => 5678
                ]
            ]
        ));
        $this->versionManager->expects($this->exactly(3))->method('setVersionToLoad');
        $this->versionManager->expects($this->at(0))->method('setVersionToLoad')->with('foo', 12, 34);
        $this->versionManager->expects($this->at(1))->method('setVersionToLoad')->with('foo', 56, 78);
        $this->versionManager->expects($this->at(2))->method('setVersionToLoad')->with('bar', 1234, 5678);

        (new Listener($this->versionManager, $this->urlHelper))->onKernelRequest($this->event);
    }
}
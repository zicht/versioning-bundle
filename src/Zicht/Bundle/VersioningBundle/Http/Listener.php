<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Http;


use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Zicht\Bundle\VersioningBundle\Services\VersioningManager;

class Listener
{
    public function __construct(VersioningManager $versioning, UrlHelper $urlHelper)
    {
        $this->versioning = $versioning;
        $this->urlHelper = $urlHelper;
    }


    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        foreach ($this->urlHelper->extractVersionInfo($event->getRequest()) as $entityName => $versions) {
            foreach ($versions as $id => $version) {
                $this->versioning->setVersionToLoad($entityName, $id, $version);
            }
        }
    }
}
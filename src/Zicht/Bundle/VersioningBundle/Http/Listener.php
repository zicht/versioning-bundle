<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Http;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;

/**
 * Class Listener
 *
 * @package Zicht\Bundle\VersioningBundle\Http
 */
class Listener
{
    /**
     * Constructor
     *
     * @param VersioningManager $versioning
     * @param UrlHelper $urlHelper
     */
    public function __construct(VersioningManager $versioning, UrlHelper $urlHelper)
    {
        $this->versioning = $versioning;
        $this->urlHelper = $urlHelper;
    }

    /**
     * Listens to the master request and informs the version manager to load a specific version if specified in the URL
     *
     * @param GetResponseEvent $event
     * @return void
     */
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
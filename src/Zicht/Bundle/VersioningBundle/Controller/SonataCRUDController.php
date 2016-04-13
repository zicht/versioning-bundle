<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DateTimeNormalizer;

/**
 * Class SonataCRUDController
 *
 * @package Zicht\Bundle\VersioningBundle\Controller
 */
class SonataCRUDController extends CRUDController
{
    /**
     * Lists all available versions. This is typically intended for functional testing.
     *
     * @return JsonResponse
     */
    public function versionsAction()
    {
        if (!$this->isGranted(['EDIT'], $this->admin->getSubject())) {
            throw new AccessDeniedException;
        }

        $versions = $this->container->get('zicht_versioning.manager')->findVersions($this->admin->getSubject());

        // todo this probably should be implemented using a serializer in stead.
        $on = new ObjectNormalizer();
        $on->setCallbacks([
            'data' =>
                function($var) {
                    return json_decode($var, true);
                }
        ]);
        $ser = new Serializer([
            $on,
            new DateTimeNormalizer(DateTimeNormalizer::STRATEGY_STRING)
        ]);
        return new JsonResponse($ser->normalize($versions));
    }
}
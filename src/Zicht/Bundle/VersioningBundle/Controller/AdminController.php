<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Controller;

use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sonata\AdminBundle\Admin\Pool;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;

/**
 * Class AdminController
 */
class AdminController
{
    /**
     * Constructor.
     *
     * @param VersioningManager $versioning
     * @param Pool $pool
     */
    public function __construct(VersioningManager $versioning, Pool $pool)
    {
        $this->versioning = $versioning;
        $this->pool = $pool;
    }


    /**
     * Renders a list of versions that were not activated but have an older active version
     *
     * @return array
     *
     * @Template
     */
    public function unactivatedVersionsAction()
    {
        $ret = $this->versioning->getUnactivatedVersions();
        $ret['pool']= $this->pool;
        return $ret;
    }

    /**
     * Returns a list of latest changes
     *
     * @param bool $active
     * @return array
     *
     * @Template
     */
    public function latestChangesAction($active = null)
    {
        $ret = $this->versioning->getLatestChanges($active);
        $ret['pool']= $this->pool;
        return $ret;
    }
}

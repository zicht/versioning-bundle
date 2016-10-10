<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Controller;

use Sonata\AdminBundle\Admin\AdminHelper;
use Sonata\AdminBundle\Admin\Pool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class HelperController
 *
 * @package Zicht\Bundle\VersioningBundle\Controller
 */
class HelperController
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var \Sonata\AdminBundle\Admin\AdminHelper
     */
    protected $helper;

    /**
     * @var \Sonata\AdminBundle\Admin\Pool
     */
    protected $pool;

    /**
     * @var \Symfony\Component\Validator\ValidatorInterface
     */
    protected $validator;

    /**
     * Constructor
     *
     * @param \Twig_Environment                               $twig
     * @param \Sonata\AdminBundle\Admin\Pool                  $pool
     * @param \Sonata\AdminBundle\Admin\AdminHelper           $helper
     * @param \Symfony\Component\Validator\ValidatorInterface $validator
     */
    public function __construct(\Twig_Environment $twig, Pool $pool, AdminHelper $helper, ValidatorInterface $validator)
    {
        $this->twig      = $twig;
        $this->pool      = $pool;
        $this->helper    = $helper;
        $this->validator = $validator;
    }

    /**
     * Overwritten, so we can extract the parentId and use the $objectId as the index instead of the id
     *
     * @param Request $request
     * @return Response
     * @throws NotFoundHttpException
     * @throws \Twig_Error_Runtime
     */
    public function appendFormFieldElementAction(Request $request)
    {
        $code      = $request->get('code');
        $elementId = $request->get('elementId');
        $objectId  = $request->get('objectId');
        $uniqid    = $request->get('uniqid');

        $admin = $this->pool->getAdminByAdminCode($code);
        $admin->setRequest($request);

        if (strpos($objectId, '|') > -1) {
            list($parentId, $objectId) = explode('|', $objectId);
            $admin->getParent()->setSubject($admin->getModelManager()->find($admin->getParent()->getClass(), $parentId));
        }

        if ($uniqid) {
            $admin->setUniqid($uniqid);
        }

        if (isset($parentId)) {
            $subject = $admin->getObject($objectId, true);
        } else {
            $subject = $admin->getObject($objectId);
        }

        if ($objectId && !$subject) {
            throw new NotFoundHttpException();
        }

        if (!$subject) {
            $subject = $admin->getNewInstance();
        }

        $admin->setSubject($subject);

        list($fieldDescription, $form) = $this->helper->appendFormFieldElement($admin, $subject, $elementId);

        /* @var $form \Symfony\Component\Form\Form */
        $view = $this->helper->getChildFormView($form->createView(), $elementId);

        // render the widget
        // todo : fix this, the twig environment variable is not set inside the extension ...

        $extension = $this->twig->getExtension('form');
        $extension->initRuntime($this->twig);
        $extension->renderer->setTheme($view, $admin->getFormTheme());

        return new Response($extension->renderer->searchAndRenderBlock($view, 'widget'));
    }
}

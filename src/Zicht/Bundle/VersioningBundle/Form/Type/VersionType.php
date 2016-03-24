<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Form\Type;

use Sonata\AdminBundle\Admin\Pool;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;

class VersionType extends AbstractType
{
    function __construct(VersioningManager $v, Pool $sonata)
    {
        $this->versioning = $v;
        $this->sonata = $sonata;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false
        ]);
    }


    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['versions']= $this->versioning->getVersions($form->getParent()->getData());
        $view->vars['admin'] = $this->sonata->getAdminByClass(get_class($form->getParent()->getData()));
        $view->vars['object'] = $form->getParent()->getData();
    }


    public function getName()
    {
        return 'zicht_version';
    }
}
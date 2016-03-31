<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Form\Type;

use Sonata\AdminBundle\Admin\Pool;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;

/**
 * Class VersionType
 */
class VersionType extends AbstractType
{
    /**
     * Constructor.
     *
     * @param VersioningManager $v
     * @param Pool $sonata
     */
    public function __construct(VersioningManager $v, Pool $sonata)
    {
        $this->versioning = $v;
        $this->sonata = $sonata;
    }

    /**
     * @{inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false
        ]);
    }

    /**
     * @{inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'operation',
                'choice', [
                    'choices' => [
                        VersioningManager::VERSION_OPERATION_NEW => 'Nieuwe versie opslaan',
                        VersioningManager::VERSION_OPERATION_ACTIVATE => 'Activeren',
                        VersioningManager::VERSION_OPERATION_UPDATE => 'Deze versie bewerken',
                    ]
                ]
            )
            ->add('version', 'hidden');

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $e) {
                $entity = $e->getForm()->getParent()->getData();
                if ($entity === null) {
                    return;
                }
//                if (!$entity->getId()) {
//                    $e->getForm()->remove('operation');
//                    $e->getForm()->remove('version');
//                } else {
                    list($op, $version) = $this->versioning->getVersionOperation($entity);
                    $e->setData([
                        'operation' => $op,
                        'version' => $version
                    ]);
//                }
            }
        );
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function(FormEvent $e) {
                $this->versioning->setVersionOperation(
                    $e->getForm()->getParent()->getData(),
                    $e->getData()['operation'],
                    $e->getData()['version']
                );
            }
        );
    }

    /**
     * @{inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['versions']= $this->versioning->findVersions($form->getParent()->getData());
        $view->vars['admin'] = $this->sonata->getAdminByClass(get_class($form->getParent()->getData()));
        $view->vars['object'] = $form->getParent()->getData();
    }

    /**
     * @{inheritDoc}
     */
    public function getName()
    {
        return 'zicht_version';
    }
}
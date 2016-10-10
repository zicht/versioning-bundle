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
     * @param string $defaultDateTimeType
     */
    public function __construct(VersioningManager $v, Pool $sonata, $defaultDateTimeType = 'sonata_type_datetime_picker')
    {
        $this->versioning = $v;
        $this->sonata = $sonata;
        $this->dateTimeType = $defaultDateTimeType;
    }

    /**
     * @{inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'mapped' => false,
                'datetime_type' => $this->dateTimeType
            ]
        );
    }

    /**
     * @{inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('version', 'hidden')
            ->add('notes', 'textarea', ['required' => false, 'label' => 'form_label.notes', 'translation_domain' => 'admin'])
            ->add(
                'dateActiveFrom',
                'sonata_type_datetime_picker',
                [
                    'required' => false,
                    'label' => 'form_label.date_active_from',
                    'translation_domain' => 'admin',
                    'format' => 'dd-MM-yyyy HH:mm'
                ]
            );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $e) {
                $entity = $e->getForm()->getParent()->getData();
                if ($entity === null) {
                    return;
                }
                if (!$entity->getId()) {
                    $e->getForm()->remove('version');
                    $e->getForm()->remove('notes');
                    $e->getForm()->remove('dateActiveFrom');
                    return;
                }

                list($op, $version) = $this->versioning->getVersionOperation($entity);

                $data = [
                    'operation' => $op,
                    'version' => $version,
                ];

                $versionInstance = $this->versioning->findVersion($entity, $version);
                $e->getForm()->add(
                    'operation',
                    'zicht_version_operation_choice',
                    [
                        'operations' => $this->versioning->getAvailableOperations($entity, $versionInstance),
                        'translation_domain' => 'admin'
                    ]
                );
                if ($versionInstance) {
                    $data['dateActiveFrom']= $versionInstance->getDateActiveFrom();
                    $data['notes']= $versionInstance->getNotes();
                }
                if (!in_array(VersioningManager::VERSION_OPERATION_ACTIVATE, $this->versioning->getAvailableOperations($entity, $versionInstance))) {
                    unset($data['dateActiveFrom']);
                    $e->getForm()->remove('dateActiveFrom');
                }

                $e->setData($data);
            }
        );
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $e) {
                if (!$e->getData()) {
                    return;
                }

                $this->versioning->setVersionOperation(
                    $e->getForm()->getParent()->getData(),
                    $e->getData()['operation'],
                    $e->getData()['version'],
                    $e->getData()
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

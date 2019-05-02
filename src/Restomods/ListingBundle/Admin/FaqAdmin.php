<?php

namespace Restomods\ListingBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;

class FaqAdmin extends AbstractAdmin
{
    protected $datagridValues = array(
		'_sort_order' => 'ASC',
		'_sort_by'    => 'position',
	);

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('question')
            ->add('answer')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('question')
            ->add('answer')
            ->add('position', null, array('label' => 'Order'))
            ->add('_action', null, array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                )
            ))
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $em = $this->getModelManager()->getEntityManager($this->getClass());
        $faq= $em->getRepository('RestomodsListingBundle:Faq')->findOneBy(array(), array('position' => 'DESC'));
        $default = 1;
        if ($faq != null) {
            $default = $faq->getPosition() + 1;
        }

        $formMapper
            ->add('question')
            ->add('answer')
            ->add('position', null, array('required'=>false, 'label'=>'Order', 'attr'=>array('placeholder'=>$default)))
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('question')
            ->add('answer')
            ->add('position', null, array('label' => 'Order'))
        ;
    }
    public function preUpdate($object){
        $em = $this->getModelManager()->getEntityManager($this->getClass());
        $originalObject = $em->getUnitOfWork()->getOriginalEntityData($object);
        if (!$object->getPosition()) {
            $object->setPosition($originalObject->getPosition());
        }
    }

    public function prePersist($object){
        $em = $this->getModelManager()->getEntityManager($this->getClass());
        if (!$object->getPosition()) {
            $em = $this->getModelManager()->getEntityManager($this->getClass());
            $faq= $em->getRepository('RestomodsListingBundle:Faq')->findOneBy(array(), array('position' => 'DESC'));
            $default = 1;
            if ($faq != null) {
                $default = $faq->getPosition() + 1;
            }
            $object->setPosition($default);
        }

    }
}

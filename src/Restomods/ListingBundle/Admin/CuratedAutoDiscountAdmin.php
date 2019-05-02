<?php

namespace Restomods\ListingBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;

class CuratedAutoDiscountAdmin extends AbstractAdmin
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
            ->add('title')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('', null, array('template' => 'RestomodsListingBundle:Admin:_list_item_image_preview.html.twig'))
            ->add('title')
            ->add('description')
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
        $item= $em->getRepository('RestomodsListingBundle:CuratedAutoDiscount')->findOneBy(array(), array('position' => 'DESC'));
        $default_position = 1;
        if ($item != null) {
            $default_position = $item->getPosition() + 1;
        }

        $item = $this->getSubject();

        $imageFiledOption['help'] = '<div class="col-sm-12 text-center"><img src="'.$item->getImage().'" style="max-height:200px;max-width:100%;" /></div>';

        $formMapper
            ->add('image', null, $imageFiledOption)
            ->add('title')
            ->add('description')
            ->add('link')
            ->add('position', null, array('required'=>false, 'label'=>'Order', 'attr'=>array('placeholder'=>$default_position)))
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('title')
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
            $item= $em->getRepository('RestomodsListingBundle:CuratedAutoDiscount')->findOneBy(array(), array('position' => 'DESC'));
            $default = 1;
            if ($item != null) {
                $default = $item->getPosition() + 1;
            }
            $object->setPosition($default);
        }

    }
}

<?php

namespace Restomods\ListingBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;

class MagazineAdmin extends AbstractAdmin
{
    protected $datagridValues = array(
		'_sort_order' => 'ASC',
		'_sort_by'    => 'createdAt',
	);

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title')
            ->add('active')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('title')
            ->add('createdAt', null, array('label' => 'Date'))
            ->add('active')
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

        $formMapper
            ->add('title')
            ->add('content')
            ->add('action')
            ->add('link')
            ->add('mediaHtml', null, array('label' => 'Media HTML'))
            ->add('active')
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
        // $em = $this->getModelManager()->getEntityManager($this->getClass());
        // $originalObject = $em->getUnitOfWork()->getOriginalEntityData($object);
        // if (!$object->getPosition()) {
        //     $object->setPosition($originalObject->getPosition());
        // }
    }

    public function prePersist($object){
        // $em = $this->getModelManager()->getEntityManager($this->getClass());
        // if (!$object->getPosition()) {
        //     $em = $this->getModelManager()->getEntityManager($this->getClass());
        //     $faq= $em->getRepository('RestomodsListingBundle:Faq')->findOneBy(array(), array('position' => 'DESC'));
        //     $default = 1;
        //     if ($faq != null) {
        //         $default = $faq->getPosition() + 1;
        //     }
        //     $object->setPosition($default);
        // }

    }
}

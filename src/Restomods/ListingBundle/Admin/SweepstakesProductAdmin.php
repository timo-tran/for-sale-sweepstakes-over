<?php

namespace Restomods\ListingBundle\Admin;

use Doctrine\ORM\EntityRepository;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;

class SweepstakesProductAdmin extends AbstractAdmin
{

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
			->add('sweepstakes', null, array(
			'query_builder' => function(EntityRepository $er){
				return $er->createQueryBuilder('qb')
					->groupBy('sweepstakes');

			}))
            ->add('type');
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('sweepstakes')
            ->add('type', 'choice', array(
                'choices' => array(
                    'upsell' => 'Upsell',
                    'product' => 'Product',
                    'downsell' => 'Downsell',
                    'bump_offer' => 'Bump Offer'
                )
            ))
            ->add('name')
            ->add('price','currency', array(
                'currency' => '$'
            ))
			->add('entries')
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
        $container = $this->getConfigurationPool()->getContainer();
        // $woocommerce_products = $container->get('restomods.woocommerce')->listProducts();
        $woocommerce_options = array();
        $woocommerce_options[''] = '';
        // foreach ($woocommerce_products as $woocommerce_product) {
        //     $woocommerce_options[$woocommerce_product['id']] = $woocommerce_product['id'].' '.$woocommerce_product['name'];
        // }

        $limelight_products = $container->get('restomods.limelight.v2')->getProducts();
        $limelight_options = array();
        foreach($limelight_products as $id => $product) {
            $limelight_options[$id] = $id.'. '.$product['product_name'].' ($'.$product['product_price'].')';
        }
		$formMapper
            ->with('General', array('class' => 'col-md-6'))
                ->add('name')
                ->add('type', ChoiceType::class, array(
                    'choices' => array(
                        'upsell' => 'Upsell',
                        'product' => 'Product',
                        'downsell' => 'Downsell',
                        'bump_offer' => 'Bump Offer'
                    )
                ))
                ->add('sweepstakes')
                ->add('price')
                ->add('entries', null, array(
                    'help' => 'Amount of entries for this product'
                ))
                ->add('limeLightProductId', ChoiceType::class, array(
                    'choices' => $limelight_options,
                    'required' => true
                ))
                ->add('woocommerceProductId', ChoiceType::class, array(
                    'choices' => $woocommerce_options,
                    'required' => false
                ))
                ->add('active')
            ->end()

            ->with('Display', array('class' => 'col-md-6'))
                ->add('title', null, array(
                    'help' => 'Title of the package',
                    'required' => true
                ))
                ->add('description', CKEditorType::class,array(
                    'help' => 'HTML description of the package',
                    'required' =>false,
                    'config' => array(
                        'toolbar' => 'standard',
                        'allowedContent' => true
                    ),
                ))
                ->add('action', null, array(
                    'help' => 'Action title. e.g. \'Add Package\'',
                    'label'=>'Action'
                ))
                ->add('actionSub', null, array(
                    'help' => 'Sub text below the action \'Add Package\'',
                    'required' => false,
                    'label'=>'Action Description'
                ))
            ->end();
    }

	/**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('name');
        ;
    }

}

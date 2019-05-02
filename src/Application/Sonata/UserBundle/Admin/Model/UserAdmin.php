<?php

namespace Application\Sonata\UserBundle\Admin\Model;

use Restomods\ListingBundle\Helper\StripeHelper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class UserAdmin extends \Sonata\UserBundle\Admin\Model\UserAdmin
{
	public $parameters = array();

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        parent::configureDatagridFilters( $datagridMapper );
        $datagridMapper
            ->remove('groups')
            ->add('phone', null, array('label' => 'Phone'))
            ->add('enabled')
        ;
    }

	protected function configureListFields( ListMapper $listMapper )
	{
		parent::configureListFields( $listMapper );
		$listMapper->get( 'username' )->setOption( 'route', array( 'name' => 'show', 'parameters' => array() ) );
		$listMapper->remove('groups');
	}

	protected function configureShowFields( ShowMapper $showMapper )
	{
		$this->getRoutes();

		/** @var StripeHelper $stripe */
		$stripe       = $this->getConfigurationPool()->getContainer()->get( 'restomods.stripe' );
		$limelight       = $this->getConfigurationPool()->getContainer()->get( 'restomods.limelight' );
		$payments     = $stripe->getCharges( $this->getSubject() );
		$limelight_orders		  = $limelight->getOrders( $this->getSubject() );
		$stripe_subscription = $stripe->getSubscription( $this->getSubject() );
		$limelight_subscription = $limelight->getSubscription( $this->getSubject() );

		$this->parameters = array( 'payments' => array('stripe'=>$payments, 'limelight'=>$limelight_orders), 'subscription' => array('stripe'=>$stripe_subscription, 'limelight'=>$limelight_subscription) );

		$showMapper->tab( "Account Overview" );

		if ( $stripe_subscription != null || $limelight_subscription != null) {
			$showMapper
				->with( "Subscription",
					array(
						'class' => 'col-md-12',
					) )
				->add( 'subscription',
					null,
					array(
						'template' => 'ApplicationSonataUserBundle:StripeAdmin:subscription.html.twig',
					) )
				->end();
		}

		$showMapper
			->with( "Payments",
				array(
					'class' => 'col-md-12',
				) )
			->add( 'payments',
				null,
				array(
					'template' => 'ApplicationSonataUserBundle:StripeAdmin:payments.html.twig',
				) )
			->end();

		$showMapper->end();

		$showMapper->tab( "User Info" );
		parent::configureShowFields( $showMapper );
		$showMapper->end();
	}

	public function prePersist($user)
	{
		$user->generateReferrerCode();
	}

	public function preUpdate($user)
	{
		$user->generateReferrerCode();
	}
}

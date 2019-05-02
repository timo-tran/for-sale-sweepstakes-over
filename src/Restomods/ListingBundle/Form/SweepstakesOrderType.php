<?php

namespace Restomods\ListingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SweepstakesOrderType extends AbstractType
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm( FormBuilderInterface $builder, array $options )
	{
		$builder
			->add('suid', 'hidden', array(
					'required' => true,
					'data' => ''
				)
			)
			->add('cc_type', 'hidden', array(
				'required' => true,
				'data' => ''
			))
			->add('cc', 'text', array(
					'required' => true,
					'attr' => array('class'=>'card-number', 'autocomplete' =>'nope', 'data-threeds' => 'pan')
				)
			)
			->add('exp_month', 'text', array(
					'required' => true,
					'attr' => array('class'=>'expiry-month', 'autocomplete' =>'nope', 'data-threeds' => 'month')
				)
			)
			->add('exp_year', 'text', array(
					'required' => true,
					'attr' => array('class'=>'expiry-year', 'autocomplete' =>'nope', 'data-threeds' => 'year')
				)
			)
			->add('cvc', 'text', array(
					'required' => true,
					'attr' => array('class'=>'cvc', 'autocomplete' =>'nope')
				)
			)
			->add('bump_offers', 'hidden', array(
					'required' => true,
					'data' => ''
				)
			)
			->add('xid', 'hidden', array(
					'required' => true,
					'data' => ''
				)
			)
			->add('cavv', 'hidden', array(
					'required' => true,
					'data' => ''
				)
			)
			->add('eci', 'hidden', array(
					'required' => true,
					'data' => ''
				)
			)
			->add('status', 'hidden', array(
					'required' => true,
					'data' => ''
				)
			)
			->add('token', 'hidden', array(
				'required' => true
			));
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array()
        );
    }

	public function getName()
    {
        return 'restomods_listingbundle_sweepstakesordertype';
    }
}

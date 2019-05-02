<?php

namespace Restomods\ListingBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListingType extends AbstractType
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm( FormBuilderInterface $builder, array $options )
	{
		$builder
			->add( 'title',
				null,
				array(
					'attr' => array(
						'placeholder' => "Enter your title",
					),
				) )
			->add( 'description',
				null,
				array(
					'attr' => array(
						'placeholder' => "Enter description",
						'rows'        => '5',
					),
				) )
			->add( 'price',
				null,
				array(
					'attr' => array(
						'placeholder' => "Enter the price",
					),
				) )
			->add( 'make',
				null,
				array(
					'required'    => true,
					'placeholder' => "-- Select one -- ",
				) )
			->add( 'model',
				null,
				array(
					'required'    => true,
					'placeholder' => "-- Select a Make first -- ",
				) )
			->add( 'trim',
				null,
				array(
					'required' => false,
					'attr'     => array(
						'placeholder' => "Enter trim",
					),
				) )
			->add( 'year',
				ChoiceType::class,
				array(
					'choices' => array_combine( range( 1920, date( 'Y' ) ), range( 1920, date( 'Y' ) ) ),
				) )
			->add( 'mileage',
				null,
				array(
					'attr' => array(
						'placeholder' => "Enter mileage",
					),
				) )
			->add( 'engine',
				null,
				array(
					'attr' => array(
						'placeholder' => "Enter engine",
					),
				) )
			->add( 'vin',
				null,
				array(
					'label' => 'VIN',
					'attr'  => array(
						'placeholder' => "Enter VIN",
					),
				) )
			->add( 'state',
				null,
				array(
					'required' => true,
					'label'    => "Condition",
				) )
			->add( 'titleStatus',
				null,
				array(
					'attr' => array(
						'placeholder' => "Enter status",
					),
				) )
			->add( 'transmission',
				null,
				array(
					'required' => true,
				) )
			->add( 'drivetrain',
				null,
				array(
					'required' => true,
				) )
			->add( 'fuel',
				null,
				array(
					'required' => true,
				) )
			->add( 'type',
				null,
				array(
					'required' => true,
				) )
			->add( 'sold',
				null,
				array(
					'required' => false,
				) )
			->add( 'location',
				null,
				array(
					'attr' => array(
						'placeholder' => "Enter your location",
					),
				) )
			->add( 'videoLink',
				null,
				array(
					'attr' => array(
						'placeholder' => "Enter your youtube, vimeo, facebook link",
					),
				) )
			->add( 'submit', 'submit' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions( OptionsResolver $resolver )
	{
		$resolver->setDefaults( array(
			'data_class' => 'Restomods\ListingBundle\Entity\Listing',
		) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlockPrefix()
	{
		return 'restomods_listingbundle_listing';
	}


}

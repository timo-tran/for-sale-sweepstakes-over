<?php

namespace Restomods\ListingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ListingSearchType extends AbstractType
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm( FormBuilderInterface $builder, array $options )
	{
		$builder
			->add( 'text',
				null,
				array(
					'label' => false,
					'attr'  => array(
						'placeholder' => "... e.g. 1970 Chevrolet Chevelle",
					),
				) )
			->add( 'submit', SubmitType::class )
			->setAction( $options['action'] )
			->setMethod( 'POST' );
	}
}

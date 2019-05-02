<?php

namespace Restomods\ListingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class ContactFormType extends AbstractType
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm( FormBuilderInterface $builder, array $options )
	{
		$builder
			->add('name', 'text', array(
					'required' => true,
					'attr' => array('placeholder' => 'Full Name', 'class'=>'form-control')
				)
			)
			->add('email', 'text', array(
					'required' => true,
					'attr' => array('placeholder' => 'Email', 'class'=>'form-control')
				)
			)
			->add('message', TextareaType::class, array(
					'required' => true,
					'attr' => array('placeholder' => 'Message', 'class'=>'form-control')
				)
			)
			->setAction( $options['action'] )
			->setMethod( 'POST' );
	}
}

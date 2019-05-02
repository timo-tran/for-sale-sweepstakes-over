<?php

namespace Restomods\ListingBundle\Form;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MessageType extends AbstractType
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm( FormBuilderInterface $builder, array $options )
	{
		$builder
			->add('name',TextType::class,array(
				'attr' => array(
					'placeholder' => 'Your name',
				),
				'label' => false,
			))
			->add('from',EmailType::class,array(
				'attr' => array(
					'placeholder' => 'Your email',
				),
				'label' => false,
			))
			->add( 'message',
				TextareaType::class,
				array(
					'attr'        => array(
						'placeholder' => "Message details",
						'rows'        => "10",
					),
					'constraints' => array( new NotBlank() ),
				) )
			->add( 'recaptcha', EWZRecaptchaType::class, array( 'mapped' => false, 'constraints' => array( new RecaptchaTrue() ) ) )
			->add( 'send', SubmitType::class )
			->setMethod( $options['method'] );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlockPrefix()
	{
		return 'restomods_listingbundle_message';
	}


}

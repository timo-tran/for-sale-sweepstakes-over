<?php

namespace Application\Sonata\UserBundle\Form;

use Doctrine\DBAL\Types\StringType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
	public function buildForm( FormBuilderInterface $builder, array $options )
	{
		$builder
            ->add( 'phone',
			null,
			array(
				'required'    => true,
				'attr'        => array(
					'placeholder' => "Enter your phone number",
				),
				'constraints' => array(
					new NotBlank(),
				),
			) )
        ;
	}

	public function getParent()
	{
		return 'sonata_user_registration';
	}

	public function getName()
	{
		return 'restomods_user_registration';
	}

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'allow_extra_fields' => true,
        ));
    }
}
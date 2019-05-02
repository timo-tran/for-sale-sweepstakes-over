<?php

namespace Restomods\ListingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SweepstakesProfileType extends AbstractType
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm( FormBuilderInterface $builder, array $options )
	{
		$builder
			->add('full_name', 'text', array(
					'required' => true,
					'attr' => array('placeholder' => 'Full Name...', 'class'=>'form-control')
				)
			)
			->add('email', 'text', array(
					'required' => true,
					'attr' => array('placeholder' => 'Email Address...', 'class'=>'form-control')
				)
			)
			->add('phone', 'text', array(
					'required' => true,
					'attr' => array(
						'placeholder' => 'Phone Number...',
						'class'=>'form-control',
						'data-inputmask' => "'mask': '(999) 999-9999'"
					)
				)
			)
			->add('country', 'choice', array(
					'required' => true,
					'attr' => array(
						'placeholder' => 'Select State', 'class'=>'form-control'
					),
					'choices' => array(
						'US' => 'United States',
						'CA' => 'Canada'
					)
				)
			)
			->add('address', 'text', array(
					'required' => true,
					'attr' => array('placeholder' => 'Full Address...', 'class'=>'form-control')
				)
			)
			->add('city', 'text', array(
					'required' => true,
					'attr' => array('placeholder' => 'City Name...', 'class'=>'form-control')
				)
			)
			->add('zip', 'text', array(
					'required' => true,
					'attr' => array('placeholder' => 'Zip Code...', 'class'=>'form-control')
				)
			);

		$stateGetter = function($country) {
			if (strcmp($country, 'CA') == 0) {
				return array(
					'AB' => 'Alberta',
					'BC' => 'British Columbia',
					'MB' => 'Manitoba',
					'NB' => 'New Brunswick',
					'NL' => 'Newfoundland and Labrador',
					'NT' => 'Northwest Territories',
					'NS' => 'Nova Scotia',
					'NU' => 'Nunavut',
					'ON' => 'Ontario',
					'PE' => 'Prince Edward Island',
					'QC' => 'Quebec',
					'SK' => 'Saskatchewan',
					'YT' => 'Yukon Territory',
				);
			} else {
				return array(
					'AL' => 'Alabama',
					'AK' => 'Alaska',
					'AZ' => 'Arizona',
					'AR' => 'Arkansas',
					'CA' => 'California',
					'CO' => 'Colorado',
					'CT' => 'Connecticut',
					'DE' => 'Delaware',
					'DC' => 'District Of Columbia',
					'FL' => 'Florida',
					'GA' => 'Georgia',
					'HI' => 'Hawaii',
					'ID' => 'Idaho',
					'IL' => 'Illinois',
					'IN' => 'Indiana',
					'IA' => 'Iowa',
					'KS' => 'Kansas',
					'KY' => 'Kentucky',
					'LA' => 'Louisiana',
					'ME' => 'Maine',
					'MD' => 'Maryland',
					'MA' => 'Massachusetts',
					'MI' => 'Michigan',
					'MN' => 'Minnesota',
					'MS' => 'Mississippi',
					'MO' => 'Missouri',
					'MT' => 'Montana',
					'NE' => 'Nebraska',
					'NV' => 'Nevada',
					'NH' => 'New Hampshire',
					'NJ' => 'New Jersey',
					'NM' => 'New Mexico',
					'NY' => 'New York',
					'NC' => 'North Carolina',
					'ND' => 'North Dakota',
					'OH' => 'Ohio',
					'OK' => 'Oklahoma',
					'OR' => 'Oregon',
					'PA' => 'Pennsylvania',
					'RI' => 'Rhode Island',
					'SC' => 'South Carolina',
					'SD' => 'South Dakota',
					'TN' => 'Tennessee',
					'TX' => 'Texas',
					'UT' => 'Utah',
					'VT' => 'Vermont',
					'VA' => 'Virginia',
					'WA' => 'Washington',
					'WV' => 'West Virginia',
					'WI' => 'Wisconsin',
					'WY' => 'Wyoming'
				);
			}
		};

		$formModifier = function (FormInterface $form, $options = null) {

            $form->add('state', 'choice', array(
				'required' => true,
				'attr' => array(
					'placeholder' => 'Select State', 'class'=>'form-control'
				),
				'choices' => ($options === null ? array() : $options),
            ));
        };

		$builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier, $stateGetter) {
                // this would be your entity, i.e. SportMeetup
                $country = $event->getForm()->get('country')->getData();
				$data = $event->getData();
				if (isset($data) && isset($data['country'])) {
					$country = $data['country'];
				}

                $formModifier($event->getForm(), $stateGetter($country));
            }
        );

		$builder->get('country')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier, $stateGetter) {
                // It's important here to fetch $event->getForm()->getData(), as
                // $event->getData() will get you the client data (that is, the ID)
                $country = $event->getForm()->getData();

                // since we've added the listener to the child, we'll have to pass on
                // the parent to the callback functions!
				$formModifier($event->getForm()->getParent(), $stateGetter($country));
            }
        );
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array()
        );
    }

	public function getName()
    {
        return 'restomods_listingbundle_sweepstakesprofiletype';
    }
}

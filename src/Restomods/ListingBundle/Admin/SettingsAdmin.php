<?php

namespace Restomods\ListingBundle\Admin;

use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Restomods\ListingBundle\Entity\Settings;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SettingsAdmin extends AbstractAdmin
{
	protected $baseRoutePattern = 'settings';

	public function configure()
	{
		$container = $this->getConfigurationPool()->getContainer();
		$settings = $container->get('doctrine')->getRepository('RestomodsListingBundle:Settings')->findOneBy(array('id' => 'site'));

		if(!$settings){
			$settings = new Settings();
			$settings->setId('site');
			$em = $container->get('doctrine')->getEntityManager();
			$em->persist($settings);
			$em->flush();
		}
	}

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $container = $this->getConfigurationPool()->getContainer();
        $image = $this->getSubject();
        $fileFieldOptions = array('required' => $this->id($this->getSubject()) ? false : true, 'data_class' => null, 'label' => 'Sweepstakes Logo');

        if ($image && $image->getSweepstakesLogo()) {
            $fullPath = $image->getSweepstakesLogo() ? $container->get('liip_imagine.cache.manager')->getBrowserPath($image->getSweepstakesLogo(), 'original') : '';
            $fileFieldOptions['help'] = '<div class="col-sm-12 text-center"><img src="'.$fullPath.'" style="max-height:200px;max-width:100%;" /></div>';
        }

        $formMapper
	        ->tab('Settings')
		        // ->with('Options', array('class' => 'col-sm-6'))
				// 	->add('siteName', null, array(
				// 		'required' => false,
				// 		'attr' => array(
				// 			'placeholder' => 'Restomods'
				// 		)
				// 	))
			    //     ->add('referralLandingPage', 'choice', array(
				//         'required' => true,
				//         'choices'   => Settings::$referralLandingOptions,
				//         'empty_value' => false
			    //     ))
                //     ->add('noActiveSweepstakesCopy',CKEditorType::class,array(
                //         'required' =>false,
                //         'config' => array(
                //             'toolbar' => 'standard',
                //             'allowedContent' => true
                //         ),
                //     ))
	            // ->end()
				->with('Sweepstakes API', array('class' => 'col-sm-6'))
					->add('entriesApiKey', 'text', array(
						'required' => true,
						'label' => 'API Key',
						'attr' => array(
							'placeholder' => 'Y3J1c2h4bzpwYXNzIUAj',
						)
					))
					->add('entriesApi', 'text', array(
						'required' => false,
						'label' => 'Entries API Endpoint',
						'attr' => array(
							'placeholder' => '/webhook/entries',
							'disabled' => true
						)
					))
	            ->end()
				->with('Shopify Store', array('class' => 'col-sm-6'))
					->add('shopifyDomain', 'text', array(
						'required' => true,
						'label' => 'Domain',
						'attr' => array(
							'placeholder' => 'crushxo.com',
						)
					))
			        ->add('shopifyApiKey', 'text', array(
				        'required' => true,
						'label' => 'API Key',
						'attr' => array(
							'placeholder' => '8ddb47ce1593d8acdd00611a9fe825d9',
						)
			        ))
                    ->add('shopifyPassword',null,array(
                        'required' =>false,
						'label' => 'API Password',
						'attr' => array(
							'placeholder' => '45fb698d622acd90bca21bf70b1beedd',
						)
                    ))
	            ->end()
				->with('CrushXO API', array('class' => 'col-sm-6'))
			        ->add('joinApi', 'text', array(
				        'required' => true,
						'label' => 'Join API Endpoint',
						'attr' => array(
							'placeholder' => 'https://api.crushxo.com/webhook/join',
						)
			        ))
					->add('joinApiUsername', 'text', array(
						'required' => true,
						'label' => 'API Username',
						'attr' => array(
							'placeholder' => 'Y3J1c2h4bzpwYXNzIUAj',
						)
					))
					->add('joinApiPassword', 'text', array(
						'required' => true,
						'label' => 'API Password',
						'attr' => array(
							'placeholder' => 'Y3J1c2h4bzpwYXNzIUAj',
						)
					))
	            ->end()
				->with('Landing Scripts', array('class' => 'col-sm-6'))
					->add('landingHeaderScript', null, array(
						'required' => false,
						'attr' => array(
							'style' => 'font-family:monospace;min-height:200px'
						)
					))
			        ->add('landingNoScript', null, array(
				        'required' => false,
						'attr' => array(
							'style' => 'font-family:monospace;min-height:200px'
						)
			        ))
                    ->add('landingFooterScript',null,array(
                        'required' =>false,
						'attr' => array(
							'style' => 'font-family:monospace;min-height:200px'
						)
                    ))
	            ->end()
	        ->end()
        ;
    }

	protected function configureRoutes(RouteCollection $collection)
	{
		$collection->clearExcept(array('edit'));
	}

    public function prePersist($object)
    {
        $this->preUpdate($object);
    }

    public function preUpdate($object)
    {
        if($object->getSweepstakesLogoFile() instanceof UploadedFile){
            $container = $this->getConfigurationPool()->getContainer();
            $manager = $container->get('stof_doctrine_extensions.uploadable.manager');
            $manager->markEntityToUpload($object, $object->getSweepstakesLogoFile());
        }
    }
}

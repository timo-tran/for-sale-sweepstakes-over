<?php

namespace Restomods\ListingBundle\Admin;

use Restomods\ListingBundle\Entity\SweepstakesUserEntries;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SweepstakesAdmin extends UploadAdmin
{
	protected $baseRoutePattern = 'sweepstakes';

    public function getUploadField(){
        return "images";
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add('active')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('', null, array('template' => 'RestomodsListingBundle:Admin:Sweepstakes/_list_image_preview.html.twig'))
            ->add('name')
            ->add('startDate', 'datetime', array(
				'format' => 'Y-m-d H:i:s',
				'timezone' => 'US/Pacific'
			))
	        ->add('endDate', 'datetime', array(
				'format' => 'Y-m-d H:i:s',
				'timezone' => 'US/Pacific'
			))
            ->add('active')
            ->add('_action', null, array(
                'actions' => array(
					'activate'         => array(
						'template' => 'RestomodsListingBundle:CRUD:sweepstakes__action_activate.html.twig',
					),
					'edit'         => array(
						'template' => 'RestomodsListingBundle:CRUD:list__action_edit.html.twig',
					),
					'delete'       => array(
						'template' => 'RestomodsListingBundle:CRUD:list__action_delete.html.twig',
					),
					'clone'       => array(
						'template' => 'RestomodsListingBundle:CRUD:sweepstakes__action_clone.html.twig',
					),
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
        $image = $this->getSubject();
        $featuredImage1FileFieldOptions = array('required' => $this->id($this->getSubject()) ? false : true, 'data_class' => null, 'label' => 'Featured Image1');
        $featuredImage2FileFieldOptions = array('required' => $this->id($this->getSubject()) ? false : true, 'data_class' => null, 'label' => 'Featured Image2');
		$benefitsImageFileFieldOptions = array('required' => $this->id($this->getSubject()) ? false : true, 'data_class' => null, 'label' => 'Image');
        if($image){
            if ($image->getFeaturedImage1()) {
                $fullPath = $image->getFeaturedImage1() ? $container->get('liip_imagine.cache.manager')->getBrowserPath($image->getFeaturedImage1(), 'medium') : '';
                $featuredImage1FileFieldOptions['help'] = '<div class="col-sm-12 text-center"><img src="'.$fullPath.'" style="max-height:200px;max-width:100%;" /></div>';
            }
            if ($image->getFeaturedImage2()) {
                $fullPath = $image->getFeaturedImage2() ? $container->get('liip_imagine.cache.manager')->getBrowserPath($image->getFeaturedImage2(), 'medium') : '';
                $featuredImage2FileFieldOptions['help'] = '<div class="col-sm-12 text-center"><img src="'.$fullPath.'" style="max-height:200px;max-width:100%;" /></div>';
            }
			if ($image->getBenefitsSectionImage()) {
                $fullPath = $image->getBenefitsSectionImage() ? $container->get('liip_imagine.cache.manager')->getBrowserPath($image->getBenefitsSectionImage(), 'medium') : '';
                $benefitsImageFileFieldOptions['help'] = '<div class="col-sm-12 text-center"><img src="'.$fullPath.'" style="max-height:200px;max-width:100%;" /></div>';
            }
        }

        $formMapper
	        ->tab('Sweepstakes')
                ->with('General', array('class' => 'col-md-6'))
                    ->add('name')
                    ->add('carName')
                    ->add('prize')
                    ->add('requirements')
                    ->add('startDate','sonata_type_datetime_picker', array('format' => 'MMM d, y hh:mm a', 'view_timezone' => 'US/Pacific'))
                    ->add('endDate','sonata_type_datetime_picker', array('format' => 'MMM d, y hh:mm a', 'view_timezone' => 'US/Pacific'))
					->add('selectWinnerDate','sonata_type_datetime_picker', array('format' => 'MMM d, y hh:mm a', 'view_timezone' => 'US/Pacific'))
					->add('contactWinnerDate','sonata_type_datetime_picker', array('format' => 'MMM d, y hh:mm a', 'view_timezone' => 'US/Pacific'))
					->add('revealWinnerDate','sonata_type_datetime_picker', array('format' => 'MMM d, y hh:mm a', 'view_timezone' => 'US/Pacific'))
                    ->add('layout', 'choice', array(
                        'required' => false,
                        'choices'  => array(1 => 'Layout 1', 2 => 'Layout 2'),
                        'empty_value' => false
                    ))
                    ->add('active')
                ->end()
                ->with('Details', array('class' => 'col-md-6'))
					->add('video', null, array('label' => 'Landing Video(Wistia video id or Youtube video link)'))
					->add('closingVideo', null, array('label' => 'Closing Video(Wistia video id or Youtube video link)'))
                    ->add('featuredImage1File', 'file', $featuredImage1FileFieldOptions)
                    ->add('featuredImage2File', 'file', $featuredImage2FileFieldOptions)
                    ->add('enterCaptionCopy')
                    ->add('finalStepSubCopy')
                    ->add('termsAndCondition',CKEditorType::class,array(
                        'required' =>false,
                        'config' => array(
                            'toolbar' => 'standard',
                            'allowedContent' => true
                        ),
                    ))
                    ->add('block',CKEditorType::class,array(
                        'required' =>false,
                        'config' => array(
                            'toolbar' => 'standard',
                            'allowedContent' => true
                        ),
                    ))
                    ->add('contestEndedHeaderCopy',CKEditorType::class,array(
                        'required' =>false,
                        'config' => array(
                            'toolbar' => 'standard',
                            'allowedContent' => true
                        ),
                    ))
                ->end()
	        ->end()
			->tab('Car Information')
				->with('General', array('class' => 'col-md-6'))
				->add('carInfoTitle', CKEditorType::class, array(
					'label' => 'Title',
					'required' =>false,
					'config' => array(
						'toolbar' => 'standard',
						'allowedContent' => true
					)))
				->add('carInfoFeatures', CKEditorType::class, array(
					'label' => 'Features',
					'required' =>false,
					'config' => array(
						'toolbar' => 'standard',
						'allowedContent' => true
					)))
				->end()
				->with(null, array('label'=>false, 'class' => 'col-md-6 image-uploader-main-wrapper'))
	                ->add('images', 'sonata_type_collection', array(
	                    'btn_add' => false,
	                    'label' => false,
	                    'help' => $this->getImageUploadWidget($this->getSubject()->getImages()),
	                    'attr' => array(
	                        'class' => 'image-fields-wrapper'
	                    )),array(
	                        'edit' => 'inline',
	                        'inline' => 'table',
	                        'sortable' => 'position'
	                    )
	                )
                ->end()
			->end()
			->tab('Last Winner')
				->with(null, array('label'=>false, 'class' => 'col-md-12'))
					->add('winnerSectionTitle', null, array(
						'label' => 'Title',
						'required' =>true))
					->add('winnerSectionSubTitle', null, array(
						'label' => 'Sub title',
						'required' =>true))
					->add('winnerSectionText', CKEditorType::class, array(
						'label' => 'Text',
						'required' =>true,
						'config' => array(
							'toolbar' => 'standard',
							'allowedContent' => true
						)))
					->add('winnerSectionVideo', null, array(
						'label' => 'Youtube Video Link',
						'required' =>false))
				->end()
			->end()
			->tab('Benefits')
				->with(null, array('label'=>false, 'class' => 'col-md-12'))
					->add('benefitsSectionText', CKEditorType::class, array(
						'label' => 'Text',
						'required' =>false,
						'config' => array(
							'toolbar' => 'standard',
							'allowedContent' => true
						)))
					->add('benefitsSectionImageFile', 'file', $benefitsImageFileFieldOptions)
				->end()
			->end()
			->tab('Additional Description')
				->with(null, array('label'=>false, 'class' => 'col-md-12'))
					->add('extraSectionTitle', CKEditorType::class, array(
						'label' => 'Title',
						'required' =>false,
						'config' => array(
							'toolbar' => 'standard',
							'allowedContent' => true
						)))
					->add('extraSectionText', CKEditorType::class, array(
						'label' => 'Text',
						'required' =>false,
						'config' => array(
							'toolbar' => 'standard',
							'allowedContent' => true
						)))
				->end()
			->end()
        ;
    }

    public function preUpdate($object){
        $em = $this->getModelManager()->getEntityManager($this->getClass());
        $originalObject = $em->getUnitOfWork()->getOriginalEntityData($object);
        $sweepstakes= $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => true));
		if ($object->getSweepstakesLimit() == NULL) {
			$object->setSweepstakesLimit(500);
		}
        if($sweepstakes){
            if($object->getActive() && !$originalObject['active']){
                $object->setActive(false);
                $this->getConfigurationPool()->getContainer()->get('session')->getFlashBag()->add('warning','Sweepstakes is already active');
            }
        }
        $this->updateImages($object);
    }

    public function prePersist($object){
        $em = $this->getModelManager()->getEntityManager($this->getClass());
        $sweepstakes= $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => true));
		if ($object->getSweepstakesLimit() == NULL) {
			$object->setSweepstakesLimit(500);
		}
        if($sweepstakes && $sweepstakes->getId() != $object->getId()){
            if($object->getActive()){
                $object->setActive(false);
                $this->getConfigurationPool()->getContainer()->get('session')->getFlashBag()->add('warning','Sweepstakes is already active');
            }
        }
        $this->updateImages($object);
    }

    public function postPersist($object){
        $this->postUpdate($object);
    }

    public function postUpdate($object){
		//this should be done via command
        // $em = $this->getModelManager()->getEntityManager($this->getClass());
        // $membershipUsers = $em->getRepository("ApplicationSonataUserBundle:User")->createQueryBuilder('u')
        //     ->select('u')
        //     ->where('u.stripeCustomerId IS NOT NULL')
        //     ->andWhere('u.stripeSubscriptionId IS NOT NULL')
        //     ->getQuery()
        //     ->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        // foreach ($membershipUsers as $member){
        //     if(!$object->getUsers()->contains($member) && in_array('ROLE_SUBSCRIBER_USER', $member->getRoles())) {
        //         $object->addUser($member);
        //         $sweepstakesUserEntries = new SweepstakesUserEntries();
        //         $sweepstakesUserEntries->setDescription('join');
        //         $sweepstakesUserEntries->setEntries(5);
        //         $sweepstakesUserEntries->setSweepstakes($object);
        //         $sweepstakesUserEntries->setUser($member);
        //         $em->persist($sweepstakesUserEntries);
        //         $em->flush();
        //     }
        // }
    }

    private function updateImages($object){
        $object->setImages($object->getImages());
        // if($object->getFeaturedImage1File() instanceof UploadedFile){
        //     $container = $this->getConfigurationPool()->getContainer();
        //     $manager = $container->get('stof_doctrine_extensions.uploadable.manager');
        //     $manager->markEntityToUpload($object, $object->getFeaturedImage1File());
        // }
		// if($object->getFeaturedImage2File() instanceof UploadedFile){
        //     $container = $this->getConfigurationPool()->getContainer();
        //     $manager = $container->get('stof_doctrine_extensions.uploadable.manager');
        //     $manager->markEntityToUpload($object, $object->getFeaturedImage2File());
        // }
		if($object->getBenefitsSectionImageFile() instanceof UploadedFile){
            $container = $this->getConfigurationPool()->getContainer();
            $manager = $container->get('stof_doctrine_extensions.uploadable.manager');
            $manager->markEntityToUpload($object, $object->getBenefitsSectionImageFile());
        }
    }

	protected function configureRoutes( RouteCollection $collection )
	{
		$collection->add( 'activate', $this->getRouterIdParameter() . '/activate' );
		$collection->add( 'clone', $this->getRouterIdParameter().'/clone');
	}

}

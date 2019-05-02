<?php

namespace Restomods\ListingBundle\Controller;

use Restomods\ListingBundle\Entity\Listing;
use Restomods\ListingBundle\Entity\SweepstakesUserEntries;
use Restomods\ListingBundle\Entity\UserReferrer;
use Restomods\ListingBundle\Helper\StripeHelper;
use Restomods\ListingBundle\Helper\UploadHandler;
use Restomods\ListingBundle\Form\ContactFormType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class DefaultController extends Controller
{
    public function removeTrailingSlashAction(Request $request) {
        $pathInfo = $request->getPathInfo();
        $requestUri = $request->getRequestUri();
        $url = str_replace($pathInfo, rtrim($pathInfo, ' /'), $requestUri);
        return $this->redirect($url, 301);
    }

    public function uploadMediaAction(Request $request)
    {
        $options = array(
            'image_versions' => array(),
            'upload_dir' => $this->container->getParameter('upload_dir').DIRECTORY_SEPARATOR,
            'uid' => $request->get('uid')
        );
        new UploadHandler($options);
        exit;
    }

	public function sweepstakesEntryPoints($subscription_order_id = null, $addOnEntries = 0){
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $userReferrer = $em->getRepository('RestomodsListingBundle:UserReferrer')->findOneBy(array('signUp'=> $user));
        $activeSweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->getDateRange();
        if($activeSweepstakes){
	        $checkSweepstakes = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findOneBy(array('user' =>$user,'sweepstakes' => $activeSweepstakes));
	        if(!$checkSweepstakes){
		        $sweepstakesUserEntries = new SweepstakesUserEntries();
		        $sweepstakesUserEntries->setUser($user);
		        $sweepstakesUserEntries->setSweepstakes($activeSweepstakes);
		        $sweepstakesUserEntries->setEntries('5');
		        $sweepstakesUserEntries->setDescription('join');
                $sweepstakesUserEntries->setReturning(false);
                if ($subscription_order_id != null)
                    $sweepstakesUserEntries->setOrderId($subscription_order_id);
		        $activeSweepstakes->addUser($user);
		        $em->persist($sweepstakesUserEntries);
	        }
            if($addOnEntries){
                $addOnEntry = new SweepstakesUserEntries();
                $addOnEntry->setUser($user);
                $addOnEntry->setSweepstakes($activeSweepstakes);
                $addOnEntry->setEntries($addOnEntries);
                $addOnEntry->setDescription('add-on');
                $addOnEntry->setReturning(false);
                $em->persist($addOnEntry);
            }
            if($userReferrer){
                $checkMember = $em->getRepository('ApplicationSonataUserBundle:User')->findOneBy(array('id' => $userReferrer->getReferBy()->getId()));
                if($checkMember->getStripeCustomerId()){
                    $sweepstakesUserEntriesReferrer = new SweepstakesUserEntries();
                    $sweepstakesUserEntriesReferrer->setUser($userReferrer->getReferBy());
                    $sweepstakesUserEntriesReferrer->setSweepstakes($activeSweepstakes);
                    $sweepstakesUserEntriesReferrer->setEntries('5');
                    $sweepstakesUserEntriesReferrer->setDescription('referrer');
                    $sweepstakesUserEntriesReferrer->setReturning(false);
                    $em->persist($sweepstakesUserEntriesReferrer);
                }
            }
            $em->flush();
        }
    }

	/**
	 * @param Session $session
	 */
	private function cleanSession( Session $session )
	{
		$session->remove( 'listing' );
		$session->remove( 'stripe_customer_id' );
		$session->remove( 'stripe_subscription_id' );
		$session->remove( 'new_member_listing_paid' );
        $session->remove( 'subscription_order_id' );
        $session->remove( 'limelight_customer_id' );
	}

	public function uploadCSVAction( Request $request )
	{
		// TODO: Centralize this form generation
		/** @var Form $form */
		$upload_form = $this->createFormBuilder()
		                    ->add( 'submitFile', FileType::class, array( 'label' => "CSV File" ) )
		                    ->setAction( $this->generateUrl( 'restomods_upload_csv' ) )
		                    ->setMethod( 'POST' )
		                    ->getForm();

		if ( $request->getMethod( 'post' ) == 'POST' ) {
			$em = $this->getDoctrine()->getManager();
			$upload_form->handleRequest( $request );

			if ( $upload_form->isValid() ) {
				$file  = $upload_form->get( 'submitFile' );
				$lorem = $file->getData();
				$rows  = array();

				if ( ( $handle = fopen( $lorem, "r" ) ) !== false ) {
					$headers = fgetcsv( $handle, null, "," );
					$i       = 0;

					while ( ( $data = fgetcsv( $handle, null, "," ) ) !== false ) {
						$i ++;
						$rows[] = array_combine( $headers, $data );
					}
					fclose( $handle );
				}

				$batchSize = 15;
				foreach ( $rows as $i => $row ) {
					// TODO: Improve log of failed
					$make         = $this->getDoctrine()->getRepository( "RestomodsListingBundle:Make" )->findOneByName( $row['MAKE_ID'] );
					$type         = $this->getDoctrine()->getRepository( "RestomodsListingBundle:Type" )->findOneByLabel( $row['TYPE_ID'] );
					$fuel         = $this->getDoctrine()->getRepository( "RestomodsListingBundle:Fuel" )->findOneByLabel( $row['FUEL_ID'] );
					$model        = $this->getDoctrine()->getRepository( "RestomodsListingBundle:Model" )->findOneByName( $row['MODEL_ID'] );
					$state        = $this->getDoctrine()->getRepository( "RestomodsListingBundle:State" )->findOneByLabel( $row['CONDITION_ID'] );
					$transmission = $this->getDoctrine()->getRepository( "RestomodsListingBundle:Transmission" )->findOneByLabel( $row['TRANSMISSION_ID'] );

					$listing = new Listing();
					$listing->setTitle( $row['title'] );
					$listing->setDescription( $row['description'] );
					$listing->setPrice( $row['price'] );
					$listing->setMake( $make );
					$listing->setModel( $model );
					$listing->setYear( $row['year'] );
					$listing->setMileage( $row['mileage'] );
					$listing->setEngine( $row['engine'] );
					$listing->setVin( $row['vin'] );
					$listing->setState( $state );
					$listing->setTitleStatus( $row['titleStatus'] );
					$listing->setTransmission( $transmission );
					$listing->setFuel( $fuel );
					$listing->setType( $type );
					$listing->setLocation( $row['location'] );
					$listing->setVideoLink( $row['videoLink'] );
					$listing->setUser( $this->getUser() );

					$em->merge( $listing );
					if ( ( $i % $batchSize ) === 0 ) {
						$em->flush();
						$em->clear();
					}
				}
				$em->flush();
				$em->clear();

				return new RedirectResponse( $this->generateUrl( 'sonata_user_profile_show' ) );
			}
		}
	}

    Public function exportToCsvAction($id){
        $em = $this->getDoctrine()->getManager();
        $data = array();
        $sweepstakesUserEntries = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->getSweepstakesUserEntries($id);
        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('id' => $id ));
        $sweepstakes_entries_limit = $sweepstakes->getSweepstakesLimit();
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=export.csv");
        $handle = fopen('php://output', 'r+');
        foreach($sweepstakesUserEntries as $entry) {
            $phone = $entry['phone'];
            $string = $phone."";
            $formatted_phone = preg_replace('/[^\d]+/', "", $string);
            if (strlen($formatted_phone) == 11 && substr($formatted_phone, 0, 1) == "1") {
                $formatted_phone = substr($formatted_phone, 1);
            }
            if (strlen($formatted_phone) == 10) {
                $formatted_phone = '('.substr($formatted_phone, 0, 3).')'.substr($formatted_phone, 3, 3).'-'.substr($formatted_phone, 6);
            }
            $data[] =array(
                'UserId' => $entry['user_id'],
                'FirstName' => $entry['firstname'],
                'LastName' => $entry['lastname'],
                'Email' => $entry['email'],
                'Phone' => $formatted_phone,
                'Entries' => min($entry['points'], $sweepstakes_entries_limit),
                'ReferrerCount' => $entry['referrer_count'],
                'CreatedAt' => date('m-d-Y H:iA', strtotime($entry['created_at'])),
                'Sweepstakes' =>  $sweepstakes->getName()
            );
        }
        fputcsv($handle, array('User Id', 'First Name', 'Last Name', 'Email', 'Phone', 'Entries', 'Referral Count', 'Signup Date', 'Sweepstakes Name'));
        foreach($data as $sweepstakesData) {
            fputcsv($handle,$sweepstakesData);
        }
        fclose($handle);
        exit;
    }
    public function signUpAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        $email = strtolower(trim($request->get('email')));
        $firstname = trim($request->get('firstname'));
        $lastname = trim($request->get('lastname'));
        $phone = trim($request->get('phone'));
        if(filter_var($email, FILTER_VALIDATE_EMAIL) === false){
            return new JsonResponse(array('success' => false,'message' => 'Invalid Email Address'));
        }
        if($request->isMethod('POST') && $email && $firstname && $lastname && $phone){
            $found = $em->getRepository('ApplicationSonataUserBundle:User')->findOneBy(array('username' => $email));
            if($found){
                return new JsonResponse(array('success' => false,'message' => 'Username already exists'));
            }
            $found = $em->getRepository('ApplicationSonataUserBundle:User')->findOneBy(array('email' => $email));
            if($found){
                return new JsonResponse(array('success' => false,'message' => 'Email already exists'));
            }
            $userManager = $this->container->get('fos_user.user_manager');
            $userEntry = $userManager->createUser();
            $userEntry->setUsername($email);
            $userEntry->setFirstname($firstname);
            $userEntry->setLastname($lastname);
            $userEntry->setEmail($email);
            $userEntry->setPhone($phone);
            $userEntry->setPlainPassword(md5(time()));
            $userEntry->addRole('ROLE_FREE_USER');
            $userEntry->setEnabled(false);
            $userEntry->setSweepstakesPaymentCompleted(false);
	        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
	        $userEntry->setConfirmationToken($tokenGenerator->generateToken());
	        $userEntry->setPasswordRequestedAt(new \DateTime());
	        $userManager->updateUser($userEntry, true);

	        $token = new UsernamePasswordToken($userEntry, null, 'main', $userEntry->getRoles());
            $this->get('security.token_storage')->setToken($token);
            $this->get('session')->set('_security_main',serialize($token));

            if($referrerCode = $this->get('session')->get('referrerCode')){
                $referrerUser = $em->getRepository('ApplicationSonataUserBundle:User')->findOneBy(array('referrerCode' => $referrerCode));
                if($referrerUser){
                    $userReferrer = new UserReferrer();
                    $userReferrer->setReferBy($referrerUser);
                    $userReferrer->setSignUp($this->getUser());
                    $em->persist($userReferrer);
                    $em->flush();
                    $this->get('session')->remove('referrerCode');
                }
            }
            return new JsonResponse(array('success' => true));
        }
        return new JsonResponse(array('success' => false,'message' => 'Required fields are missing'));
    }
    public function contactAction(Request $request) {
        $form = $this->createForm( ContactFormType::class,
			null,
			array(
				'method' => 'POST',
			)
		);
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $user = $this->getUser();
                $data = $form->getData();
                $email = $data['email'];
                $name = $data['name'];
                $message = isset($data['message']) ? $data['message'] : '';

                $renderedHTML = $this->get('twig')->render('RestomodsListingBundle:Emails:contact_us.html.twig', array(
                    'email' => $email,
                    'name' => $name,
                    'message' => $message
                ));
                $renderedText = $this->get('twig')->render('RestomodsListingBundle:Emails:contact_us.txt.twig', array(
                    'email' => $email,
                    'name' => $name,
                    'message' => $message
                ));

                $res = $this->get('restomods.mailer')->sendMail("support@restomods.com", "Support request", $renderedHTML, $renderedText, false);
                return new JsonResponse(array('success' => true));
            }
        }
        return new JsonResponse(array('success' => false, 'error' => $error));
    }
}

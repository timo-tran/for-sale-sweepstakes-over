<?php

namespace Application\Sonata\UserBundle\Controller;

use FOS\UserBundle\Model\UserInterface;
use Restomods\ListingBundle\Entity\UserReferrer;
use Restomods\ListingBundle\Helper\StripeHelper;
use Restomods\ListingBundle\Form\ContactFormType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Sonata\UserBundle\Controller\ProfileFOSUser1Controller as BaseProfileController;
use Psr\Log\LoggerInterface;

class ProfileFOSUser1Controller extends BaseProfileController
{
	/**
	 * @return Response
	 *
	 * @throws AccessDeniedException
	 */
	public function showAction()
	{
		/** @var Session $session */
		$session = $this->get( 'session' );
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        if(!$user){

			$request  = $this->getRequest();
			$referer  = $request->headers->get( 'referer' );

			// No referer, expected it's from clickfunnels.com
			if (empty($referer)) { return $this->redirectToRoute( 'sonata_user_security_login' ); }

			$parts = parse_url($referer);

			if (empty($parts['query']))  { return $this->redirectToRoute( 'sonata_user_security_login' ); }
			parse_str($parts['query'], $query);

			// has referer, but it's not from clickfunnels.com
			if (!isset($query) || !isset($query['cf_uvid'])) { return $this->redirectToRoute( 'sonata_user_security_login' ); }

			$userManager = $this->container->get('fos_user.user_manager');
			$timeout = 10;

			for ($i = 0; $i < $timeout; $i ++) {
				$user = $this->container->get('fos_user.user_manager')->findUserBy(array('cfUvid'=>$query['cf_uvid']));
				if ($user) { break;}
				sleep(1);
			}

			// No user reigstered yet.
			if (!$user) {
				$this->get('session')->getFlashBag()->set('sonata_user_success', 'Check your email to activate your account.');
				return $this->redirectToRoute( 'sonata_user_security_login' );
			}

			$token = new UsernamePasswordToken($user, null, 'secured_area', $user->getRoles());
			$this->get('security.token_storage')->setToken($token);
			$this->get('session')->set('_security_secured_area', serialize($token));
        }

        if(!$user->getReferrerCode()){
            $user->generateReferrerCode();
            $em->persist($user);
            $em->flush();
        }
        $referralLink = str_replace('http:', $this->container->getParameter('restomods.url.scheme'), $this->get('router')->generate('restomods_referral',array('code' => $user->getReferrerCode()),true));
        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active'=> true));
		$sweepstakes_add_on = 0;
		$sweepstakes_entries_count = 0;
        if($sweepstakes  && $this->getUser() && $this->getUser()->getId()){
			$entries = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->getAllSweepstakesUserEntries($sweepstakes->getId(), $this->getUser()->getId());
			foreach ($entries as $entry) {
				$sweepstakes_entries_count += $entry['entries'];
				if (stripos($entry['description'], 'Membership') !== false) {
					continue;
				}

				$add_on_level = 0;
				if (stripos($entry['description'], 'Platinum') !== false) { //Platinum 190 Entries
					$add_on_level = 4;
				} else if (stripos($entry['description'], 'Gold') !== false) { // Gold 70 Entries
					$add_on_level = 3;
				} else if (stripos($entry['description'], 'Silver') !== false) { // Silver 20 Entries
					$add_on_level = 2;
				} else if ($entry['entries'] == 20) { // VIP 20 Entries
					$add_on_level = 1;
				}

				if ($add_on_level > $sweepstakes_add_on) {
					$sweepstakes_add_on = $add_on_level;
				}
			}
        }
		if ($sweepstakes_entries_count > $sweepstakes->getSweepstakesLimit()) {
			$sweepstakes_entries_count = $sweepstakes->getSweepstakesLimit();
		}
        $settings = $em->getRepository('RestomodsListingBundle:Settings')->findOneBy(array('id' => 'site'));
		if ( !is_object( $user ) || !$user instanceof UserInterface ) {
			throw new AccessDeniedException( 'This user does not have access to this section.' );
		}

		$coupons = $em->getRepository('RestomodsListingBundle:Coupon')->findBy(array('user' => $user, 'used' => 0));
		foreach($coupons as &$coupon) {
			$alias = base64_decode($coupon->getCode());
			if ($alias) {
				$coupon->setAlias($alias);
			} else {
				$coupon->setAlias($coupon->getCode());
			}
		}

		$is_subscriber = $user->hasSubscription();

		$contact_form = $this->createForm( ContactFormType::class,
			null,
			array(
				'method' => 'POST',
			)
		);

		return $this->render( 'SonataUserBundle:Profile:show.html.twig',
			array(
				'is_premium'			   => $is_subscriber,
				'user'                     => $user,
				'blocks'                   => $this->container->getParameter( 'sonata.user.configuration.profile_blocks' ),
				'referral_link'            => $referralLink,
				'sweepstakes'			   => $sweepstakes,
                'sweepstakes_user_points'  => $sweepstakes_entries_count,
                'settings'                 => $settings,
				'coupons'				   => empty($coupons) ? FALSE : $coupons,
				'contact_form'			   => $contact_form->createView()
			) );
	}

	public function cancelSubscriptionAction()
	{

		$user = $this->getUser();
		$logger = $this->get('logger');

		/** @var StripeHelper $stripe */

		if ( !empty($user->getSubscriptionOrderId()) ) {
			//
			// cancel limelight subscription
			//
			$limelight = $this->get( 'restomods.limelight' );
			$cancel = $limelight->cancelSubcription( $user );

			if ( $cancel['success'] ) {
				if ( $cancel['object'] != null ) {
					$user->setSubscriptionOrderId( null );
					$user->removeRole( 'ROLE_SUBSCRIBER_USER' )->addRole( 'ROLE_FREE_USER' );
					$em = $this->getDoctrine()->getManager();
					$em->persist( $user );
					$em->flush();
				} else {
					$logger->error('Could not handle membership cancel request', array('email'=>$user->getEmail(), 'response'=>$response));
					$this->get('session')->getFlashBag()->set('sonata_user_error', 'Failed to cancel your membership, please contact the support team.');
				}
			} else {
				$logger->error('Could not handle membership cancel request', array('email'=>$user->getEmail(), 'response'=>$response));
				$this->get('session')->getFlashBag()->set('sonata_user_error', 'Failed to cancel your membership, please contact the support team.');
			}
		} else if (!empty($user->getStripeSubscriptionId())){

			$stripe   = $this->get( 'restomods.stripe' );
			$response = $stripe->cancelSubcription( $user );

			if ( $response['object'] != null ) {
				$user->setStripeSubscriptionId( null );
				$user->removeRole( 'ROLE_SUBSCRIBER_USER' )->addRole( 'ROLE_FREE_USER' );
				$em = $this->getDoctrine()->getManager();
				$em->persist( $user );
				$em->flush();
			} else {
				$logger->error('Could not handle membership cancel request', array('email'=>$user->getEmail(), 'response'=>$response));
				$this->get('session')->getFlashBag()->set('sonata_user_error', 'Failed to cancel your membership, please contact the support team.');
			}
		}
		return $this->redirectToRoute( 'sonata_user_profile_edit_authentication' );
	}

	/**
	 * @return Response|RedirectResponse
	 *
	 * @throws AccessDeniedException
	 */
	public function editAuthenticationAction()
	{
		$user = $this->getUser();
		if (!is_object($user) || !$user instanceof UserInterface) {
			throw new AccessDeniedException('This user does not have access to this section.');
		}

		$form = $this->get('sonata.user.authentication.form');
		$formHandler = $this->get('sonata.user.authentication.form_handler');

		$process = $formHandler->process($user);
		if ($process) {
			$this->setFlash('sonata_user_success', 'profile.flash.updated');

			return $this->redirect($this->generateUrl('sonata_user_profile_show'));
		}

		/** @var StripeHelper $stripe */
		$stripe       = $this->get( 'restomods.stripe' );
		$pastPayments = $stripe->getCharges( $this->getUser() );
		$stripeSubscription = $stripe->getSubscription( $this->getUser() );

		/** @var LimeLightHelp $limelight */
		$limelight       = $this->get( 'restomods.limelight' );
		$limelightPayments = $limelight->getOrders( $this->getUser(), true );
		$limelightSubscription = $limelight->getSubscription( $this->getUser() );

		$cancelSubscriptionForm = $this->createFormBuilder()
		                               ->setAction( $this->generateUrl( 'restomods_cancel_subscription' ) )
		                               ->setMethod( 'POST' )
		                               ->getForm()
		                               ->createView();

		return $this->render('@ApplicationSonataUser/Profile/edit_authentication.html.twig', array(
			'form' => $form->createView(),
			'pastPayments'             => array('stripe' => $pastPayments, 'limelight' => $limelightPayments),
			'subscription'             => array('stripe' => $stripeSubscription, 'limelight' => $limelightSubscription),
			'cancel_subscription_form' => $cancelSubscriptionForm,
		));
	}
}

<?php

namespace Restomods\ListingBundle\Controller;

use Application\Sonata\UserBundle\Entity\User;
use Restomods\ListingBundle\Helper\LimeLightHelper;
use Restomods\ListingBundle\Traits\Referer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class LimeLightController extends Controller
{
	use Referer;

	/**
	 * @param Request $request
	 * @param $chargeId
	 *
	 * @return RedirectResponse
	 */
	public function refundAction( Request $request, $orderId )
	{
		/** @var LimeLightHelper $limelight */
		$limelight = $this->get( 'restomods.limelight' );
		$refund = $limelight->refund( $orderId );

		if ( $refund['success'] ) {
			$this->addFlash( 'sonata_flash_info', $refund['message'] );

			if ( array_key_exists('listing', $refund) && $refund['listing'] != null ) {
				$em = $this->getDoctrine()->getManager();
				$em->persist( $refund['listing'] );
				$em->flush();
			}
			if ( array_key_exists('sweepstakes_entries',$refund) && $refund['sweepstakes_entries'] != null ) {
				$em = $this->getDoctrine()->getManager();
				foreach ($refund['sweepstakes_entries'] as $entries) {
					$em->persist( $entries );
				}
				$em->flush();
			}

		} else {
			$this->addFlash( 'sonata_flash_error', $refund['message'] );
		}

		$route = $this->getRefererParams();

		return $this->redirectToRoute( $route['_route'], $this->getRouteParams( $route ) );
	}

	/**
	 * @param Request $request
	 * @param User $user
	 * @param string $orderId
	 *
	 * @return RedirectResponse
	 * @ParamConverter(name="user", class="Application\Sonata\UserBundle\Entity\User")
	 */
	public function cancelAction( Request $request, User $user, $orderId )
	{
		/** @var LimeLightHelper $limelight */
		$limelight = $this->get( 'restomods.limelight' );
		if ( $user->getSubscriptionOrderId() == $orderId ) {
			$cancel = $limelight->cancelSubcription( $user );

			if ( $cancel['success'] ) {
				$this->addFlash( 'sonata_flash_info', $cancel['message'] );

				if ( $cancel['object'] != null ) {
					$user->setSubscriptionOrderId( null );
					$user->removeRole( 'ROLE_SUBSCRIBER_USER' )->addRole( 'ROLE_FREE_USER' );
					$em = $this->getDoctrine()->getManager();
					$em->persist( $user );
					$em->flush();
				}

			} else {
				$this->addFlash( 'sonata_flash_error', $cancel['message'].'\n Error code:'.$cancel['code'] );
			}
		} else {
			$this->addFlash( 'sonata_flash_error', "The user and subscription id doesn't match." );
		}

		$route = $this->getRefererParams();

		return $this->redirectToRoute( $route['_route'], $this->getRouteParams( $route ) );
	}
}

<?php

namespace Restomods\ListingBundle\Controller;

use Restomods\ListingBundle\Entity\Listing;
use Restomods\ListingBundle\Helper\StripeHelper;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ListingAdminController extends CRUDController
{
	/**
	 * @param Request $request
	 *
	 * @return RedirectResponse
	 */
	public function approveAction( Request $request )
	{
		/** @var Listing $listing */
		$listing = $this->admin->getSubject();
		$listing->setApproved( true );

		$this->admin->update( $listing );
		$this->addFlash( 'sonata_flash_success', "Listing approved" );

		return new RedirectResponse( $this->admin->generateUrl( 'list' ) );
	}

	/**
	 * @param Request $request
	 *
	 * @return RedirectResponse
	 */
	public function flagAction( Request $request )
	{
		/** @var Listing $listing */
		$listing = $this->admin->getSubject();
		$listing->setApproved( false );

		$this->admin->update( $listing );
		$this->addFlash( 'sonata_flash_info', "Listing flagged" );

		return new RedirectResponse( $this->admin->generateUrl( 'list' ) );
	}

	/**
	 * @param Request $request
	 *
	 * @return RedirectResponse
	 */
	public function refundAction( Request $request )
	{
		/** @var Listing $listing */
		$listing = $this->admin->getSubject();

		if ( $listing->getStripeChargeId() == null || $listing->getStripeChargeId() == "" ) {
			$this->addFlash( 'sonata_flash_error', "The selected Listings doesn't have a charge id associated" );

			return new RedirectResponse(
				$this->admin->generateUrl( 'list', array( 'filter' => $this->admin->getFilterParameters() ) )
			);
		}

		/** @var StripeHelper $stripe */
		$stripe = $this->get( 'restomods.stripe' );
		$refund = $stripe->refund( $listing );

		if ( $refund['success'] ) {
			$this->addFlash( 'sonata_flash_info', $refund['message'] );
			$this->admin->update( $refund['listing'] );
		} else {
			$this->addFlash( 'sonata_flash_error', $refund['message'] );
		}

		return new RedirectResponse( $this->admin->generateUrl( 'list' ) );
	}

	/**
	 * @param ProxyQueryInterface $query
	 * @param Request $request
	 *
	 * @return RedirectResponse
	 */
	public function batchActionApprove( ProxyQueryInterface $query, Request $request = null )
	{
		return $this->setApprovedInBatchAction( true, $query, $request );
	}

	/**
	 * @param bool $approved
	 * @param ProxyQueryInterface $query
	 * @param Request|null $request
	 *
	 * @return RedirectResponse
	 */
	private function setApprovedInBatchAction( $approved, ProxyQueryInterface $query, Request $request = null )
	{
		$modelManager = $this->admin->getModelManager();

		$selectedModels = $query->execute();

		try {
			/** @var Listing $selectedModel */
			foreach ( $selectedModels as $selectedModel ) {
				$selectedModel->setApproved( $approved );
			}

			$modelManager->update( $selectedModel );
		} catch ( \Exception $e ) {
			$this->addFlash( 'sonata_flash_error', "Error executing the batch action" );

			return new RedirectResponse(
				$this->admin->generateUrl( 'list', array( 'filter' => $this->admin->getFilterParameters() ) )
			);
		}

		$this->addFlash( 'sonata_flash_success', sprintf( "%d listings affected", count( $selectedModels ) ) );

		return new RedirectResponse(
			$this->admin->generateUrl( 'list', array( 'filter' => $this->admin->getFilterParameters() ) )
		);
	}

	/**
	 * @param ProxyQueryInterface $query
	 * @param Request $request
	 *
	 * @return RedirectResponse
	 */
	public function batchActionFlag( ProxyQueryInterface $query, Request $request = null )
	{
		return $this->setApprovedInBatchAction( false, $query, $request );
	}
}

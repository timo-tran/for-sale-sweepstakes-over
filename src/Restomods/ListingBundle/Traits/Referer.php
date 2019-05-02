<?php

namespace Restomods\ListingBundle\Traits;

use Symfony\Component\HttpFoundation\Request;

trait Referer
{
	private function getRefererParams()
	{
		/** @var Request $request */
		$request  = $this->getRequest();
		$referer  = $request->headers->get( 'referer' );
		$baseUrl  = $request->getSchemeAndHttpHost().'';
		$lastPath = substr( $referer, strpos( $referer, $baseUrl ) + strlen( $baseUrl ) );

		return $this->get( 'router' )->match( $lastPath );
	}

	private function getRouteParams( $route )
	{
		return array_filter( $route,
			function ( $key ) {
				return strpos( $key, "_" ) !== 0;
			},
			ARRAY_FILTER_USE_KEY );
	}
}

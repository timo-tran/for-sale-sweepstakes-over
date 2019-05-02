<?php

namespace Restomods\ListingBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Application\Sonata\UserBundle\Entity\User;
use Geocoder\Provider\GoogleMaps;
use Ivory\HttpAdapter\CurlHttpAdapter;
use Restomods\ListingBundle\Entity\Listing;
use Restomods\ListingBundle\Entity\SweepstakesImages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class AdminEventListener {
	protected $container;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $token = $this->container->get('security.token_storage')->getToken();
        if(strpos($request->getPathInfo(), 'admin') === false && strpos($request->get('_route'), '_wdt') === false && $token){
            $user = $token->getUser();
            if($user instanceof User){
                $paymentCompleted = $user->getSweepstakesPaymentCompleted();
                if(!is_null($paymentCompleted) && !$paymentCompleted && !$user->getStripeCustomerId() && !$user->getSubscriptionOrderId()){
                    if(!in_array($request->get('_route'), array('restomods_sweepstakes_step2', 'restomods_sweepstakes_payment', 'restomods_sweepstakes_order'))){
                        $session = $this->container->get('session');
                        $url = $this->container->get('router')->generate('restomods_sweepstakes_order').$session->get('_utm_');
                        $session->remove('_utm_');
                        $event->setResponse(new RedirectResponse($url));
                    }
                }
            }
        }
    }

	public function preRemove( LifecycleEventArgs $args ) {
		$entity = $args->getEntity();
		if ( $entity instanceof SweepstakesImages ) {
			$upload_dir      = $this->container->getParameter( 'upload_dir' );
            $media_cache_dir = $this->container->getParameter( 'media_cache_dir' );
			$file            = $entity->getSrc();
			@unlink( $upload_dir . '/' . $file );
			$media_dir = array_filter( array_diff( scandir( $media_cache_dir ), array( '.', '..' ) ), function ( $item ) {
				return ! is_dir( '$media_cache_dir' . $item );
			} );
			if ( $media_dir ) {
				foreach ( $media_dir as $media ) {
					@unlink( $media_cache_dir . '/' . $media . '/' . $file );
				}
			}
		}
	}

    public function prePersist( LifecycleEventArgs $args ) {
	    $this->preUpdate($args);
    }

    public function preUpdate( LifecycleEventArgs $args ) {
        $entity = $args->getEntity();
        if ( $entity instanceof Listing ) {
			if((!$entity->getId() || $args->hasChangedField('location')) && trim($entity->getLocation()) && $coordinate = $this->getLatitudeAndLongitude($entity->getLocation())){
				$entity->setLatitude($coordinate->getLatitude());
				$entity->setLongitude($coordinate->getLongitude());
			}
        }
    }

    private function getLatitudeAndLongitude($location) {
        $coordinate = null;
        if($location){
			try {
				$googleMapsApiKey = $this->container->getParameter( 'restomods.gmaps.api_key' );
	            $curl = new CurlHttpAdapter();
	            $geoCoder = new GoogleMaps( $curl, null, null, true, $googleMapsApiKey );
	            $address = $geoCoder->geocode($location);
	            $coordinate = $address->first()->getCoordinates();
			} catch (\Geocoder\Exception\Exception $e) {
			}
        }
        return $coordinate;
    }
}

<?php

namespace Restomods\ListingBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class EcommerceController extends Controller
{
    public function indexAction( Request $request )
	{
        $em = $this->getDoctrine()->getManager();
        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        $settings = $em->getRepository('RestomodsListingBundle:Settings')->findOneBy(array('id' => 'site'));
        $images = array(
            'https://d2q2bkkwoz3pe2.cloudfront.net/images/cobra1.jpg',
            'https://d2q2bkkwoz3pe2.cloudfront.net/images/cobra2.jpg',
            'https://d2q2bkkwoz3pe2.cloudfront.net/images/cobra3.jpg',
        );
        $winners = array(
            array(
                "name" => "PAUL WAGNER - '69 CAMARO",
                'saying' => "No Way, Did I win? .... Wow ... I'm taking the car",
                "desc" => "Premium Member Paul from Ravenna, Ohio was caught off guard when we first called, but he quickly came around to being our first Winner.",
                "prize" => "SELECTED $35,000 CASH",
                "video" => "gfcmu3l9zq"
            ),
            array(
                "name" => "TIM DEUTSCH - '57 CHEVY",
                'saying' => "I'm still shocked... I was standing in Walmart to buy tires",
                "desc" => "Another Restomods VIP member takes him the prize! Tim went with the cash, so he could buy a new car. Just like that, 2-days later Tim had $35,000 in his bank account",
                "prize" => "SELECTED $35,000 CASH",
                "video" => "qnss7tzn7g"
            ),
            array(
                "name" => "JASON CLOSE - '65 MUSTANG",
                'saying' => "This is the highlight of my month!",
                "desc" => "Taking the keys!  One lucky guy to have this Mustang as one of his first cars. Already working at a garage and collecting cars, we know this Mustang is in good hands",
                "prize" => "SELECTED '65 MUSTANG",
                "video" => "sjza649yz3"
            ),
            array(
                "name" => "TOBIN FELTER - '72 CHEVELLE",
                'saying' => "Are you kidding me?!!...I can't believe it",
                "desc" => "After interrupting Tobin's work day we gave him the choice between a Chevelle SS 454 or $35,000. With a '65 Mustang already in the garage he decided on the cash. Once a Ford guy always a Ford guy. Check back for more from this winner.",
                "prize" => "SELECTED $35,000 CASH",
                "video" => "xh4y67nax7"
            ),
        );
        return $this->render('RestomodsListingBundle:Ecommerce:index.html.twig', array(
            'settings' => $settings,
            'sweepstakes' => $sweepstakes,
            'error' => null,
            'winners' => $winners,
            'images' => $images,
        ));
    }

    public function productsAction( Request $request )
	{
        $shopify = $this->get( 'restomods.shopify' );
        $res = array();
        $em = $this->getDoctrine()->getManager();
        $settings = $em->getRepository('RestomodsListingBundle:Settings')->findOneBy(array('id' => 'site'));
        try {
            $products = $shopify->listProducts();
            $shopify_host = $settings->getShopifyDomain();
            foreach($products as $product) {
                $price_max = 0;
                $price_min = 0;
                $purchasable = false;
                foreach($product['variants'] as $variant) {
                    if ($price_min == 0 || $price_min > $variant['price']) {
                        $price_min = $variant['price'];
                    }
                    if ($price_max == 0 || $price_max < $variant['price']) {
                        $price_max = $variant['price'];
                    }
                    if ($variant['inventory_policy'] != 'deny' || $variant['inventory_quantity'] > 0) {
                        $purchasable = true;
                    }
                }

                if ($price_min == $price_max) {
                    $price_html = '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">$</span>'.$price_min.'</span>';
                } else {
                    $price_html = '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">$</span>'.$price_min.'</span>'
                        .'-'
                        .'<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">$</span>'.$price_max.'</span>';
                }


                $json = array();
                $json['order'] = $product['id'];
                $json['name'] = $product['title'];
                $json['link'] = '//'.$shopify_host.'/products/'.$product['handle'];
                $json['price_html'] = $price_html;
                $json['purchasable'] = $purchasable;
                $json['in_stock'] = $purchasable;
                $json['entries'] = ceil($price_min);
                if (isset($product['image']) && isset($product['image']['src'])) {
                    $json['image'] = $product['image']['src'];
                } else {
                    $json['image'] = '';
                }
                if ($purchasable)
                    $res[] = $json;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return new JsonResponse($res);
    }
}

<?php

namespace Restomods\ListingBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Restomods\ListingBundle\Form\SweepstakesProfileType;
use Restomods\ListingBundle\Form\SweepstakesOrderType;
use Restomods\ListingBundle\Form\SweepstakesMembershipType;
use Restomods\ListingBundle\Entity\SweepstakesUserEntries;
use Restomods\ListingBundle\Entity\Event;
use Restomods\ListingBundle\Entity\Coupon;
use Application\Sonata\UserBundle\Entity\User;
use Restomods\ListingBundle\Helper\SendgridHelper;

class SweepstakesController extends Controller
{
    const SWEEPSTAKES_STEP_NONE = 0;
    const SWEEPSTAKES_STEP_ORDER = 1;
	const SWEEPSTAKES_STEP_MEMBERSHIP = 3;
    const SWEEPSTAKES_STEP_CONFIRM = 7;
    const DEBUGGING = false;

    public function visitAction(Request $request)
    {
        $session = $this->get( 'session' );
        $queryParams = $session->get('utm_params');
        $this->logEvent('sweepstakes.visit', $request, $queryParams, null);
        return new JsonResponse(array('success' => true));
    }

    public function previewAction(Request $request, $type = 'order') {
        return $this->orderActionWithType($request, $type, true);
    }

    public function orderAction(Request $request)
    {
        return $this->orderActionWithType($request, 'order');
    }

    private function orderActionWithType(Request $request, $type = 'order', $preview = false)
    {

        $country = isset($queryParams['country']) ? strtolower($queryParams['country']) : 'us';
        if ($country == 'ca' || $country == 'canada') {
            $country = 'CA';
        } else {
            $country = 'US';
        }

        $form = $this->createForm( SweepstakesProfileType::class,
			array('country' => $country),
			array(
				'method' => 'POST',
			)
		);

        $em = $this->getDoctrine()->getManager();
        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        if ($preview) {
            $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array(), array('id' => 'DESC'));
        }
        // $user = $this->getUser();
        // if (!self::DEBUGGING && $user && this->get('restomods.rawsqlhelper')->isUserInSweepstakes($user, $sweepstakes)) {
        //     return $this->redirectToRoute('sonata_user_profile_show');
        // }

        // check for expiration
        if (!self::DEBUGGING && $sweepstakes->getEndDate()->getTimestamp() < time()) {
            return $this->redirectToRoute('restomods_sweepstakes_over');
        }

        $bump_products = $em->getRepository( 'RestomodsListingBundle:SweepstakesProduct' )->getSweepstakesProducts( $sweepstakes->getId(), 'bump_offer' );
        $error = null;
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $userManager = $this->container->get('fos_user.user_manager');
                $user = $userManager->findUserByUsernameOrEmail($data['email']);

                $session = $this->get( 'session' );
                $queryParams = $session->get('utm_params');

                $email = $data['email'];
                $phone = preg_replace('/[^\d]+/','',$data['phone']);
                $address = $data['address'];
                $city = $data['city'];
                $state = $data['state'];
                $country = $data['country'];
                $zip = $data['zip'];
                $names = $this->split_name($data['full_name']);

                if (!$user) {
                    $existing = $userManager->findOneBy(array("phone" => $phone));
                    if ($existing)
                        $error = 'The phone number is already in use.';
                }

                if (!$error) {
                    if (!$user) {
                        $user = $userManager->createUser();
                        $user->setPlainPassword(md5(time()));
                        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
                        $user->setConfirmationToken($tokenGenerator->generateToken());
                        $user->setPasswordRequestedAt(new \DateTime());
                        $user->setSweepstakesPaymentCompleted(false);
                        $user->setFromSweepstakes(true);

                        $user->setUsername($email);
                        $user->setEmail($email);

                        if (isset($queryParams)) {
                            if (isset($queryParams['affiliate_id'])) {
                                $user->setCFAffiliateId($queryParams['affiliate_id']);
                            }
                            if (isset($queryParams['utm_medium'])) {
                                $user->setUtmMedium($queryParams['utm_medium']);
                            }
                            if (isset($queryParams['utm_source'])) {
                                $user->setUtmSource($queryParams['utm_source']);
                            }
                            if (isset($queryParams['utm_campaign'])) {
                                $user->setUtmCampaign($queryParams['utm_campaign']);
                            }
                            if (isset($queryParams['utm_term'])) {
                                $user->setUtmTerm($queryParams['utm_term']);
                            }
                            if (isset($queryParams['utm_content'])) {
                                $user->setUtmContent($queryParams['utm_content']);
                            }
                        }
                    }

                    $user->setAddress($address);
                    $user->setCity($city);
                    $user->setState($state);
                    $user->setCountry($country);
                    $user->setZip($zip);
                    $user->setFirstname($names[0]);
                    $user->setLastname(count($names) > 1 ? $names[1] : '');
                    $user->setPhone($phone);

                    $userManager->updateUser($user, true);
                    $this->setSweepstakesUserEmail($email);
                    $this->logEvent('sweepstakes.profile', $request, $queryParams, $user);

                    return $this->redirectToRoute('restomods_sweepstakes_membership');
                }
            } else {
                $error = $form->getErrorsAsString();
            }
        }

        $this->beginSweepstakes();
        $queryParams = $request->query->all();
        $session = $this->get( 'session' );
        $session->set('utm_params', $queryParams);
        $session->set('last_initial_purchase', 0);

        // if ($request->getMethod() == 'POST') {
        //     $profileForm->handleRequest($request);
        // }

        $sweepstakes_array = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findAll();
        $winners = array();
        foreach($sweepstakes_array as $s) {
            if ($s->getWinnerSectionText() != null && $sweepstakes->getId() >= $s->getId()) {
                $winner = array();
                $winner["text"] = $s->getWinnerSectionText();
                $winner["video"] = $s->getWinnerSectionVideo();
                $winners[] = $winner;
            }
        }

        $faq = $em->getRepository('RestomodsListingBundle:Faq')->findAll(array(), array('position'=>'ASC'));
        $settings = $em->getRepository('RestomodsListingBundle:Settings')->findOneBy(array('id' => 'site'));
        return $this->render('RestomodsListingBundle:Sweepstakes:'.$type.'.html.twig', array(
            'form' => $form->createView(),
            'settings' => $settings,
            'error' => $error,
            'sweepstakes' => $sweepstakes,
            'bump_products' => $bump_products,
            'winners' => $winners,
            'faq' => $faq,
        ));
    }

    public function membershipAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        if (!$this->checkSweepstakesSession(self::SWEEPSTAKES_STEP_MEMBERSHIP)) {
            return $this->redirectToRoute( 'sonata_user_profile_show' );
        }

        $email = $this->getSweepstakesUserEmail();
        if (!$email) {
            return $this->redirectToRoute( 'sonata_user_profile_show' );
        }

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserByUsernameOrEmail($email);
        if (!$user) {
            return $this->redirectToRoute( 'sonata_user_profile_show' );
        }

        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        // check for expiration
        if (!self::DEBUGGING && $sweepstakes->getEndDate()->getTimestamp() < time()) {
            return $this->redirectToRoute('restomods_sweepstakes_over');
        }

        $alreadyJoined = $this->get('restomods.rawsqlhelper')->isUserInSweepstakes($user, $sweepstakes);

        $error = null;
        $form = $this->createForm( SweepstakesMembershipType::class,
			null,
			array(
				'method' => 'POST',
			)
		);

        $utmParams = $this->get( 'session' )->get('utm_params');

        if ($request->getMethod() == 'POST') {

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                // If already joined, just bring to dashboard
                if ($this->get('restomods.rawsqlhelper')->isUserInSweepstakes($user, $sweepstakes)) {
                    // Authorize with new user
                    $token = new UsernamePasswordToken($user, null, 'secured_area', $user->getRoles());
        			$this->get('security.token_storage')->setToken($token);
        			$this->get('session')->set('_security_secured_area', serialize($token));
                    $this->logEvent('sweepstakes.order.post', $request, $utmParams, $user);
                    return $this->redirectToRoute('sonata_user_profile_show');
                }

                $data = $form->getData();
                $package = $data['package'];

                // If the product does not exist, just bring to dashboard.
                $product = $em->getRepository( 'RestomodsListingBundle:SweepstakesProduct' )->find($package);
                if (!$product) {
                    return $this->redirectToRoute( 'sonata_user_profile_show' );
                }

                $limelight = $this->get( 'restomods.limelight' );
                $cc_number = $data['cc'];
                $cc_type = $data['cc_type'];
                $cc_exp_date = sprintf("%02d%02d", intval($data['exp_month']), intval($data['exp_year']));
                $cc_cvc = $data['cvc'];

                if (stripos($cc_type, 'visa') !== false) {
                    $cc_type = 'visa';
                } else if (stripos($cc_type, 'discover') !== false) {
                    $cc_type = 'discover';
                } else if (stripos($cc_type, 'master') !== false) {
                    $cc_type = 'master';
                } else if (stripos($cc_type, 'diners') !== false) {
                    $cc_type = 'diners';
                } else {
                    $cc_type = strtolower($cc_type);
                }
                $encoded_cc = base64_encode(implode(',', array($cc_type, $cc_number, $cc_exp_date, $cc_cvc)));

                try {

                    $customer = $limelight->retrieveCustomer($user, $encoded_cc);
                    $order = $limelight->subscribe($product->getLimeLightProductId(), $customer, array(), null, $utmParams);
                    $u = $this->subscribeWithLimeLightResponse($order, $user, $product, $utmParams);
                    if ($u) {
                        $user = $u;
                        $previous_order_id = $order['orderId'];
                        // save to Session
                        $this->addSweepstakesPurchase(array(
                            'tid'=>$previous_order_id,
                            'pid'=>0,
                            'name'=>$product->getName(),
                            'price'=>'$'.$product->getPrice(),
                            'price_value'=>$product->getPrice(),
                            'entries'=>$product->getEntries(),
                            'type'=> 'membership'
                        ));

                        // Authorize with new user
                        $token = new UsernamePasswordToken($user, null, 'secured_area', $user->getRoles());
            			$this->get('security.token_storage')->setToken($token);
            			$this->get('session')->set('_security_secured_area', serialize($token));
                        $this->setSweepstakesStep(self::SWEEPSTAKES_STEP_MEMBERSHIP);
                        $this->logEvent('sweepstakes.order.post', $request, $utmParams, $user);
                        return $this->redirectToRoute('restomods_sweepstakes_confirm');
                    } else {
                        $error = $order['errorMessage'];
                    }
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }

        $membership_products = $em->getRepository( 'RestomodsListingBundle:SweepstakesProduct' )->getSweepstakesProducts( $sweepstakes->getId(), 'subscription' );

        $this->setSweepstakesStep(self::SWEEPSTAKES_STEP_MEMBERSHIP);
        $settings = $em->getRepository('RestomodsListingBundle:Settings')->findOneBy(array('id' => 'site'));
        return $this->render('RestomodsListingBundle:Sweepstakes:membership.html.twig', array(
            'form' => $form->createView(),
            'settings' => $settings,
            'error' => $error,
            'sweepstakes' => $sweepstakes,
            'products'=>$membership_products,
            'alreadyJoined'=>$alreadyJoined
        ));
    }

    public function confirmAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $error = null;

        if (!$user) {
            return $this->redirectToRoute( 'sonata_user_security_login' );
        }

        if (!$this->checkSweepstakesSession(self::SWEEPSTAKES_STEP_CONFIRM)) {
            return $this->redirectToRoute( 'sonata_user_profile_show' );
        }

        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        // check for expiration
        if (!self::DEBUGGING && $sweepstakes->getEndDate()->getTimestamp() < time()) {
            return $this->redirectToRoute('restomods_sweepstakes_over');
        }

        /*
        * purchase reservations if have any
        */
        $reservations = $this->getSweepstakesProductReservations();
        if (!empty($reservations)) {
            $products = $em->getRepository( 'RestomodsListingBundle:SweepstakesProduct' )->getSweepstakesProducts( $sweepstakes->getId());
            $utmParams = $this->get( 'session' )->get('utm_params');
            $threedparams = $this->get( 'session' )->get('threedparams');
            $last_order_id = $user->getLastOrderId();
            $encoded_cc = $user->getCc();
            $reserved_products = array();
            $reserved_limelight_product_ids = array();
            foreach($reservations as $reservation) {

                $product = null;
                foreach($products as $p) {
                    if ($p->getId() == $reservation['product_id']) {
                        $product = $p;
                        break;
                    }
                }
                if (isset($product)) {
                    $reserved_datas[] = array('product'=>$product, 'reservation' => $reservation);
                    $reserved_limelight_product_ids[] = $product->getLimeLightProductId();
                }
            }

            if (!empty($reserved_datas)) {
                try {
                    $order = null;
                    $limelight = $this->get( 'restomods.limelight' );
                    $main_product_id = $reserved_limelight_product_ids[0];
                    $upsells = count($reserved_limelight_product_ids) > 1 ? array_slice($reserved_limelight_product_ids, 1) : null;
                    if (!empty($last_order_id)) {
                        $order = $limelight->orderProduct($main_product_id, $upsells, null, $last_order_id, $threedparams, null);
                    } else {
                        $customer = $limelight->retrieveCustomer($user, $encoded_cc);
                        $order = $limelight->orderProduct($main_product_id, $upsells, $customer, null, $threedparams, $utmParams);
                    }

                    if(intval($order['errorFound']) == 0){
                        // $woocommerce_purchases = array();
                        foreach($reserved_datas as $reserved_data) {
                            $product = $reserved_data['product'];
                            $product_name = $product->getName();
                            $product_entries = $product->getEntries();
                            $product_price_desc = '$'.$product->getPrice();
                            $product_price_cent = $product->getPrice() * 100;
                            $product_type = $product->getType();
                            $this->addSweepstakes($product_name, $product_entries, $order['orderId'], $user, $product, $utmParams);
                            $this->addSweepstakesPurchase(array(
                                'tid'=>$order['orderId'],
                                'pid'=>$product->getId(),
                                'name'=>$product_name,
                                'price'=>$product_price_desc,
                                'price_value'=>$product->getPrice(),
                                'entries'=>$product_entries,
                                'type'=> $product_type
                            ));

                            if ($product_type == 'product') {
                                $reservation = $reserved_data['reservation'];
                                $woocommerce_purchases[] = $reservation['variation'];
                            } else {
                                // if (!empty($product->getWoocommerceProductId())) {
                                //     $woocommerce_purchases[] = $product->getWoocommerceProductId();
                                // }
                            }
                        }

                        if (!empty($woocommerce_purchases)) {
                            // $woocommerce = $this->get( 'restomods.woocommerce' );
                            // $woocommerce->createOrderForProducts($woocommerce_purchases, $order['orderId'], $user);
                        }
                    } else {
                        $error = $order['errorMessage'];
                    }
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
            $this->clearSweepstakesProductReservation();
        }

        if (empty($error)) {
            if (!self::DEBUGGING)
                $this->sendReceiptEmail($sweepstakes);

            $purchases_packages = array();
            $purchases = $this->getSweepstakesPurchases();
            $price = 0;
            $total_entries = 0;
            foreach ($purchases as $purchase) {
                if (strcmp($purchase['type'],'upsell') == 0 || strcmp($purchase['type'],'product') == 0 || strcmp($purchase['type'],'downsell') == 0) {
                    $purchases_packages[] = $purchase;
                    $price = $price + $purchase['price_value'];
                    $total_entries = $total_entries + $purchase['entries'];
                }
            }

            $session = $this->get( 'session' );
            $initial_purchase_amount = 0;
            $last_initial_purchase = $session->get('last_initial_purchase');
            if ($last_initial_purchase == 0) {
                $purchases = $this->getSweepstakesPurchases();
                foreach ($purchases as $purchase) {
                    $initial_purchase_amount = $initial_purchase_amount + $purchase['price_value'];
                }

                if ($initial_purchase_amount > 0) {
                    $session->set('last_initial_purchase', $initial_purchase_amount);

                    // log purchase event
                    $utmParams = $session->get('utm_params');
                    $this->logEvent('sweepstakes.purchase', $request, $utmParams, $user);
                }
            }
        } else {
            $purchases = array();
            $purchases_packages = array();
            $purchases_all = $this->getSweepstakesPurchases();
            $price = 0;
            $total_entries = 0;
            foreach ($purchases as $purchase) {
                if (strcmp($purchase['type'],'upsell') == 0 || strcmp($purchase['type'],'product') == 0 || strcmp($purchase['type'],'downsell') == 0) {
                } else {
                    $purchases[] = $purchase;
                }
            }
            $total_entries = 0;
            $price = 0;
            $initial_purchase_amount = 0;

            file_put_contents('logs/limelight_request_'.date('Y-m-d').'.txt',date('Y-m-d H:i:s').": ".$user->getEmail().":".$error."\n\n", FILE_APPEND);
            $error = $error.PHP_EOL."<br/>Please contact the support team with your email.";
        }

        $this->logEvent('sweepstakes.confirm', $request, $this->get( 'session' )->get('utm_params'), $user);
        $settings = $em->getRepository('RestomodsListingBundle:Settings')->findOneBy(array('id' => 'site'));
        return $this->render('RestomodsListingBundle:Sweepstakes:confirm.html.twig', array(
            'sweepstakes' => $sweepstakes,
            'settings' => $settings,
            'purchases' => $purchases,
            'purchases_packages' => $purchases_packages,
            'total_price' => $price,
            'total_entries' => $total_entries,
            'initial_purchase_amount' => $initial_purchase_amount,
            'error' => $error,
            )
        );
    }

    public function overAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        if (!self::DEBUGGING && $sweepstakes->getEndDate()->getTimestamp() > time()) {
            return $this->redirectToRoute('restomods_sweepstakes_order');
        }
        $settings = $em->getRepository('RestomodsListingBundle:Settings')->findOneBy(array('id' => 'site'));

        return $this->render('RestomodsListingBundle:Sweepstakes:over.html.twig', array(
            'sweepstakes' => $sweepstakes,
            'settings' => $settings
        ));
    }

    private function beginSweepstakes() {
        $session = $this->get( 'session' );
        $session->set('sweepstakes', array('purchases' => array(), 'step'=>self::SWEEPSTAKES_STEP_NONE, 'sentReceiptEmail' => false));
    }

    private function checkSweepstakesSession($step) {

        if (self::DEBUGGING) {
            return true;
        }

        $session = $this->get( 'session' );
        if (!$session->has('sweepstakes')) {
            return false;
        }

        $sweepstakes = $session->get('sweepstakes');
        $prevStep = $sweepstakes['step'] ? $sweepstakes['step'] : self::SWEEPSTAKES_STEP_NONE;

        switch ( $step ) {
            case self::SWEEPSTAKES_STEP_MEMBERSHIP:
                return $prevStep <= self::SWEEPSTAKES_STEP_MEMBERSHIP;
            case self::SWEEPSTAKES_STEP_CONFIRM:
                return $prevStep != self::SWEEPSTAKES_STEP_NONE;
        }
        return false;
    }

    private function setSweepstakesUserEmail($email) {
        $session = $this->get( 'session' );
        $sweepstakes = $session->get('sweepstakes');
        $sweepstakes['email'] = $email;
        $session->set('sweepstakes', $sweepstakes);
    }

    private function getSweepstakesUserEmail() {
        $session = $this->get( 'session' );
        $sweepstakes = $session->get('sweepstakes');
        return isset($sweepstakes) && isset($sweepstakes['email']) ? $sweepstakes['email'] : FALSE;
    }

    private function setSweepstakesStep($step) {
        $session = $this->get( 'session' );
        $sweepstakes = $session->get('sweepstakes');
        $sweepstakes['step'] = $step;
        $session->set('sweepstakes', $sweepstakes);
    }

    private function addSweepstakesPurchase($purchase) {
        $session = $this->get( 'session' );
        $sweepstakes = $session->get('sweepstakes');
        if (!$sweepstakes) {
            $sweepstakes = array('purchase'=>array());
        }
        $purchases = $sweepstakes['purchases'];
        $purchases[] = $purchase;
        $sweepstakes['purchases'] = $purchases;
        $session->set('sweepstakes', $sweepstakes);
    }

    private function getSweepstakesPurchases() {
        $session = $this->get( 'session' );
        $sweepstakes = $session->get('sweepstakes');
        if (!$sweepstakes) {
            return array();
        }
        return $sweepstakes['purchases'];
    }

    private function addSweepstakesProductReservation($product_details) {
        $session = $this->get( 'session' );
        $sweepstakes = $session->get('sweepstakes');
        if (!$sweepstakes) {
            $sweepstakes = array('reservation'=>array());
        }
        $reservations = isset($sweepstakes['reservation']) ? $sweepstakes['reservation'] : array();
        $reservations[] = $product_details;
        $sweepstakes['reservation'] = $reservations;
        $session->set('sweepstakes', $sweepstakes);
    }

    private function getSweepstakesProductReservations() {
        $session = $this->get( 'session' );
        $sweepstakes = $session->get('sweepstakes');
        if (!$sweepstakes) {
            return null;
        }
        return isset($sweepstakes['reservation']) ? $sweepstakes['reservation'] : null;
    }

    private function clearSweepstakesProductReservation() {
        $session = $this->get( 'session' );
        $sweepstakes = $session->get('sweepstakes');
        if ($sweepstakes) {
            if (isset($sweepstakes['reservation'])) {
                unset($sweepstakes['reservation']);
                $session->set('sweepstakes', $sweepstakes);
            }
        }
    }

    private function sendReceiptEmail($sweepstakesActive) {
        $user = $this->getUser();
        $session = $this->get( 'session' );
        $sweepstakes = $session->get('sweepstakes');
        if (!self::DEBUGGING && $sweepstakes['sentReceiptEmail']) {
            return;
        }

        $confirmationUrl = null;
        if ($user->getConfirmationToken()) {
            $confirmationUrl = str_replace('http:', $this->container->getParameter('restomods.url.scheme'), $this->generateUrl('fos_user_resetting_reset', array('token' => $user->getConfirmationToken()), true));
        }

        $em = $this->getDoctrine()->getManager();

        $purchases = $this->getSweepstakesPurchases();
        $entries = 0;
        foreach($purchases as $purchase) {
            $entries = $entries + $purchase['entries'];
        }

        if ($entries > 0) {
            $renderedHTML = $this->get('twig')->render('RestomodsListingBundle:Emails:receipt.html.twig', array(
                'entries' => $entries,
                'confirmationUrl' => $confirmationUrl,
                'purchases' => $purchases,
                'sweepstakesName' => $sweepstakesActive->getCarName()
            ));
            $renderedText = $this->get('twig')->render('RestomodsListingBundle:Emails:receipt.txt.twig', array(
                'entries' => $entries,
                'confirmationUrl' => $confirmationUrl,
                'purchases' => $purchases,
                'sweepstakesName' => $sweepstakesActive->getCarName()
            ));
            $res = $this->get('restomods.mailer')->sendMail($user->getEmail(), "CrushXO Sweepstakes Receipt", $renderedHTML, $renderedText, false);

            $sweepstakes['sentReceiptEmail'] = true;
            $session->set('sweepstakes', $sweepstakes);
        }
    }

    private function split_name($name) {
        $name = trim($name);
        $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $first_name = trim( preg_replace('#'.$last_name.'#', '', $name ) );
        return array($first_name, $last_name);
    }

    private function addSweepstakes($product_name, $entries, $order, $user, $product, $utmParams) {
        $em = $this->getDoctrine()->getManager();
        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        if ($sweepstakes) {
            $date = new \DateTime();
            $date->sub(new \DateInterval('PT1H'));

            $count = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->createQueryBuilder('e')
                        ->select('count(e.id)')
                        ->where('e.active = true')
                        ->andWhere("e.description not like '%Bonus%'")
                        ->andWhere("e.user = :user")
                        ->andWhere("e.createdAt < :date")
                        ->setParameter('user', $user)
                        ->setParameter('date', $date)
                        ->getQuery()->getSingleScalarResult();
            $userEntries = new SweepstakesUserEntries();
            $userEntries->setUser($user);
            $userEntries->setSweepstakes($sweepstakes);
            $userEntries->setEntries($entries);
            $userEntries->setDescription($product_name);
            $userEntries->setOrderId($order);
            $userEntries->setSweepstakesProduct($product);
            $userEntries->setReturning($count == 0 ? false : true);

            if ($utmParams != null) {
                if (isset($utmParams['utm_medium'])) {
                    $userEntries->setUtmMedium($utmParams['utm_medium']);
                }
                if (isset($utmParams['utm_source'])) {
                    $userEntries->setUtmSource($utmParams['utm_source']);
                }
            }

            $em->persist($userEntries);
            $em->flush();
        }
        return true;
    }

    private function subscribeWithLimeLightResponse($order, User $user, $product, $utmParams = null) {
        if(intval($order['errorFound']) == 0){
            $em = $this->getDoctrine()->getManager();
            $userManager = $this->container->get('fos_user.user_manager');
            $order_id = $order['orderId'];

            $name = '';
            $email = $user->getEmail();
            $phone = $user->getPhone();
            $address = $user->getAddress();
            $city = $user->getCity();
            $state = $user->getState();
            $zip = $user->getZip();
            $country = $user->getCountry();
            if (!empty($user->getFirstname())) {
                $name .= $user->getFirstname();
            }
            if (!empty($user->getFirstname())) {
                $name .= ' '.$user->getLastName();
            }

            $joinParams = array(
                'name' => $name,
                'email' => $email,
                'phone' => isset($phone) ? $phone : '',
                'address' => isset($address) ? $address : '',
                'city' => isset($city) ? $city : '',
                'state' => isset($state) ? $state : '',
                'zip' => isset($zip) ? $zip : '',
                'country_code' => isset($country) ? $country : '',
                'order_id' => $order_id,
                'membership' => strtolower($product->getName()),
                'membership_date' => date('Y-m-d'),
                'rebill' => 0
            );

            $join_data = $this->get( 'restomods.shopify' )->join($joinParams);
            if (!empty($join_data['item']) && !empty($join_data['item']['coupon'])) {
                $code = $join_data['item']['coupon'];
                $url = $join_data['item']['url'];
                $coupon = $em->getRepository('RestomodsListingBundle:Coupon')->findOneBy(array('code' => $code, 'user'=>$user));
                if (!$coupon) {
                    $coupon = new Coupon();
                    $coupon->setUser($user);
                    $coupon->setCode($code);
                    $coupon->setUrl($url);
                    $coupon->setUsed(false);
                    $em->persist($coupon);
                }
            }

            $user->removeRole('ROLE_FREE_USER');
            $user->setSubscribedAt(new \DateTime());
            $user->addRole('ROLE_SUBSCRIBER_USER');
            $user->setLastOrderId($order_id);
            $user->setCc(null);
            $user->setSubscriptionOrderId($order_id);
            $user->setLimeLightCustomerId($order['customerId']);
            $user->setEnabled(true);
            $user->setSweepstakesPaymentCompleted(true);
            if ($utmParams != null) {
                if (isset($utmParams['utm_medium'])) {
                    $user->setUtmMedium($utmParams['utm_medium']);
                }
                if (isset($utmParams['utm_source'])) {
                    $user->setUtmSource($utmParams['utm_source']);
                }
            }
            $userManager->updateUser($user, true);

            $user->generateReferrerCode();
            $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
            if ($sweepstakes) {
                $date = new \DateTime();
                $date->sub(new \DateInterval('PT1H'));
                $count = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->createQueryBuilder('e')
                            ->select('count(e.id)')
                            ->where('e.active = true')
                            ->andWhere("e.description not like '%Bonus%'")
                            ->andWhere("e.user = :user")
                            ->andWhere("e.createdAt < :date")
                            ->setParameter('user', $user)
                            ->setParameter('date', $date)
                            ->getQuery()->getSingleScalarResult();

                if (!$this->get('restomods.rawsqlhelper')->isUserInSweepstakes($user, $sweepstakes))
                    $sweepstakes->addUser($user);

                $userEntries = new SweepstakesUserEntries();
                $userEntries->setUser($user);
                $userEntries->setSweepstakes($sweepstakes);
                $userEntries->setSweepstakesProduct($product);
                $userEntries->setEntries($product->getEntries());
                $userEntries->setDescription($product->getName());
                $userEntries->setOrderId($order_id);
                $userEntries->setReturning($count == 0 ? false : true);
                if ($utmParams != null) {
                    if (isset($utmParams['utm_medium'])) {
                        $userEntries->setUtmMedium($utmParams['utm_medium']);
                    }
                    if (isset($utmParams['utm_source'])) {
                        $userEntries->setUtmSource($utmParams['utm_source']);
                    }
                }
                $em->persist($userEntries);
            }
            $em->flush();

            return $user;
        }

        return null;
    }

    private function logEvent($name, $request, $utmParams = null, $user = null) {
        $em = $this->getDoctrine()->getManager();
        $session = $this->get( 'session' );
        $event = new Event();
        $event->setName($name);
        $event->setUser($user);

        if ($session != null) {
            $event->setSession($session->getId());
        }

        if ($request != null) {
            $event->setRequestMethod($request->getMethod());
            $event->setRequestPath($request->getPathInfo());
            $event->setClientIp($request->getClientIp());
        }

        if ($utmParams != null) {
            if (isset($utmParams['utm_medium'])) {
                $event->setUtmMedium($utmParams['utm_medium']);
            }
            if (isset($utmParams['utm_source'])) {
                $event->setUtmSource($utmParams['utm_source']);
            }
        }

        $em->persist($event);
        $em->flush();
    }
}

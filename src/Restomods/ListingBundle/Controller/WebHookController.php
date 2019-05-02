<?php

namespace Restomods\ListingBundle\Controller;

use Restomods\ListingBundle\Entity\SweepstakesUserEntries;
use Restomods\ListingBundle\Entity\Coupon;
use Restomods\ListingBundle\Entity\SubscriptionFailure;
use Stripe\Invoice;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Customer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use DateInterval;
use DateTime;

class WebHookController extends Controller
{
    public function joinAction(Request $request) {
        $headers = $request->headers->all();
        $required_params = array('name', 'email', 'phone', 'country_code', 'address', 'city', 'state', 'zip');

        if (empty($headers['authorization']) || $headers['authorization'][0] != 'Bearer Y3J1c2h4bzpwYXNzIUAj') {
            return new JsonResponse(array('success' => false, 'error' => 'Not authorized'), 403);
        }

        $body = json_decode($request->getContent(), true);
        //
        // check required parameters
        //
        foreach($required_params as $param) {
            if (empty($body[$param])) {
                return new JsonResponse(array('success' => false, 'error' => $param.' is required'), 400);
            }
        }

        return new JsonResponse(array('success' => true, 'coupon' => '123', 'email'=>'s123@gmail.com'));

    }

    public function entriesAction(Request $request) {
        $headers = $request->headers->all();

        file_put_contents('logs/entries_'.date('Y-m-d').'.txt',date('Y-m-d H:i:s').": ".json_encode($headers)."\n\n".$request->getContent()."\n\n", FILE_APPEND);

        $em = $this->getDoctrine()->getManager();
        $settings = $em->getRepository('RestomodsListingBundle:Settings')->findOneBy(array('id' => 'site'));

        if (empty($headers['x-shopify-shop-domain']) || $headers['x-shopify-shop-domain'][0] != $settings->getShopifyDomain()) {
            file_put_contents('logs/entries_'.date('Y-m-d').'.txt',date('Y-m-d H:i:s').": Wrong domain\n\n", FILE_APPEND);
            return new JsonResponse(array('success' => false, 'error' => 'Not authorized'), 403);
        }

        if (empty($headers['x-shopify-hmac-sha256'])) {
            file_put_contents('logs/entries_'.date('Y-m-d').'.txt',date('Y-m-d H:i:s').": missing hash\n\n", FILE_APPEND);
            return new JsonResponse(array('success' => false, 'error' => 'Not authorized'), 403);
        }

        $sha256 = $headers['x-shopify-hmac-sha256'][0];
        $content = $request->getContent();
        $calculated_hmac = base64_encode(hash_hmac('sha256', $content, $settings->getEntriesApiKey(), true));
        if ($calculated_hmac != $sha256) {
            file_put_contents('logs/entries_'.date('Y-m-d').'.txt',date('Y-m-d H:i:s').": hash mismatch(".$sha256."\n".$calculated_hmac.")\n\n", FILE_APPEND);
            return new JsonResponse(array('success' => false, 'error' => 'Not authorized'), 403);
        } else {
            file_put_contents('logs/entries_'.date('Y-m-d').'.txt',date('Y-m-d H:i:s').": Authorized\n\n", FILE_APPEND);
        }

        $body = json_decode($content, true);
        $required_params = array('billing_address', 'customer', 'total_price', 'order_number');

        //
        // check required parameters
        //
        foreach($required_params as $param) {
            if (empty($body[$param])) {
                return new JsonResponse(array('success' => false, 'error' => $param.' is required'), 400);
            }
        }

        $customer = $body['customer'];
        if (empty($customer)) {
            return new JsonResponse(array('success' => false, 'error' => 'Email is required'), 400);
        }

        $email = $customer['email'];
        $transaction_id = $body['order_number'];
        $entries = round($body['total_price']);
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail($email);

        //
        // check if user exists
        //
        if (!$user) {
            // create new user with billing address
            $billing_address = $body['billing_address'];
            $first_name = isset($billing_address['first_name']) ? $billing_address['first_name'] : '';
            $last_name = isset($billing_address['last_name']) ? $billing_address['last_name'] : '';
            $address = isset($billing_address['address1']) ? $billing_address['address1'] : '';
            $city = isset($billing_address['city']) ? $billing_address['city'] : '';
            $state = isset($billing_address['province_code']) ? $billing_address['province_code'] : '';
            $country = isset($billing_address['country_code']) ? $billing_address['country_code'] : '';
            $zip = isset($billing_address['zip']) ? $billing_address['zip'] : '';
            $phone = isset($billing_address['phone']) ? $billing_address['phone'] : '';

            $user = $userManager->createUser();
            $user->setPlainPassword(md5(time()));
            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
            $user->setPasswordRequestedAt(new \DateTime());
            $user->setSweepstakesPaymentCompleted(false);
            $user->setFromSweepstakes(false);

            $user->setUsername($email);
            $user->setEmail($email);

            $user->setAddress($address);
            $user->setCity($city);
            $user->setState($state);
            $user->setCountry($country);
            $user->setZip($zip);
            $user->setFirstname($first_name);
            $user->setLastname($last_name);
            $user->setPhone($phone);

            $userManager->updateUser($user, true);
        }

        //
        // check if already claimed
        //
        if (!empty($body['transaction_id'])) {
            $count = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->createQueryBuilder('e')
                        ->select('count(e.id)')
                        ->andWhere("e.shopifyOrderId = :orderId")
                        ->andWhere("e.user = :user")
                        ->setParameter('user', $user)
                        ->setParameter('orderId', $transaction_id)
                        ->getQuery()->getSingleScalarResult();
            if ($count > 0) {
                return new JsonResponse(array('success' => true, 'error' => 'Already added.'), 200);
            }
        }

        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        if (!$sweepstakes) {
            $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array(), array('id' => 'DESC'));
        }

        if ($sweepstakes) {

            $userEntries = new SweepstakesUserEntries();
            $userEntries->setUser($user);
            $userEntries->setSweepstakes($sweepstakes);
            $userEntries->setEntries($entries);
            $userEntries->setDescription('API');
            $userEntries->setShopifyOrderId($transaction_id);
            $userEntries->setReturning(false);
            $em->persist($userEntries);

            if (!empty($body['discount_codes'])) {
                foreach($body['discount_codes'] as $discount_code) {
                    $coupon = $em->getRepository('RestomodsListingBundle:Coupon')->findOneBy(array('code' => $discount_code));
                    if ($coupon) {
                        $coupon->setUsed(true);
                        $em->persist($coupon);
                    } else {
                        $coupon = new Coupon();
                        $coupon->setUser($user);
                        $coupon->setCode($discount_code);
                        $coupon->setUsed(true);
                        $em->persist($coupon);
                    }
                }

            }
            $em->flush();
            return new JsonResponse(array('success' => true), 200);
        } else {
            return new JsonResponse(array('success' => false, 'error' => 'No active sweepstakes'), 400);
        }
    }

    public function limelightAction(Request $request) {
        $is_debug = false;
        if ($this->container->get('kernel')->getEnvironment() == 'dev') {
            $is_debug = true;
        }

        $params = $request->query->all();
        $content = json_encode($params);
        file_put_contents('logs/limelight_'.date('Y-m-d').'.txt',date('Y-m-d H:i:s').": ".$content."\n\n", FILE_APPEND);
        $product_id_csv = $request->query->get('product_id_csv');
        $product_ids = explode(',', $product_id_csv.'');

        $em = $this->getDoctrine()->getManager();
        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        if (!$sweepstakes) {
            $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array(), array('id' => 'DESC'));
        }
        $membership_products = $em->getRepository( 'RestomodsListingBundle:SweepstakesProduct' )->getSweepstakesProducts( $sweepstakes->getId(), 'subscription' );
        $membership_product = null;
        foreach($membership_products as $product) {
            if (in_array($product->getLimeLightProductId(), $product_ids)) {
                $membership_product = $product;
                break;
            }
        }

        $subscription_product_id = $this->container->getParameter('restomods.limelight.membership_product_id');

        if ($request->query->get('ischargeback') == 1 || $request->query->get('is_fraud') == 1) {

            // if chargeback, we need to mark the listing or sweepstakes user entries inactive

            $order_id = $request->query->get('order_id');
            $sweepstakesEntries  = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->findByOrderId( $order_id );

            if (count($sweepstakesEntries) > 0) {
                foreach ($sweepstakesEntries as $entries) {
                    $entries->setActive(false);
                    $em->persist( $entries );
                }
            }

            $listing  = $em->getRepository( 'RestomodsListingBundle:Listing' )->findOneByOrderId( $order_id );
            if ($listing) {
                $listing->setRefunded( true );
    			$listing->setApproved( false );
                $em->persist( $listing );
            }

            $em->flush();

        } else if (!empty($membership_product)) {

            // it it's subscription order

            $ancestor_id = $request->query->get('ancestor_id');
            $order_id = $request->query->get('order_id');
            $order_status = $request->query->get('order_status');

            //
            // We skip the first order in the subscription
            //
            if ($ancestor_id == $order_id) {
                return new JsonResponse(array('success' => true));
            }

            $utmSource = $request->query->get('sid');
            $utmMedium = $request->query->get('afid');
            if (empty($utmSource)) $utmSource = '';
            if (empty($utmMedium)) $utmMedium = '';

            $email = $request->query->get('email');
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $userManager->findUserByUsernameOrEmail($email);
            if($user){

                if ($order_status == 1) {
                    // The order has been approved

                    // We need to update the user's subscription_order_id, we'll use it later for stopping subscription
                    $user->setSubscriptionOrderId($order_id);

                    // we need to ensure that user is joined
                    $user->setSubscribedAt(new \DateTime());
                    $user->removeRole('ROLE_FREE_USER')->removeRole('ROLE_SUBSCRIBER_USER')->addRole('ROLE_SUBSCRIBER_USER');

                    // if there is failure record for the order, we need to remove it.
                    $failure = $em->getRepository('RestomodsListingBundle:SubscriptionFailure')->findOneBy(array('orderId' => $order_id));
                    if ($failure) {
                        $em->remove($failure);
                    }

                    // add new sweepstakes user entries, you earned 5 entries again now.
                    if (!$this->get('restomods.rawsqlhelper')->isUserInSweepstakes($user, $sweepstakes)){
                        $sweepstakes->addUser($user);
                    }

                    $existing_entries = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findBy(array('orderId' => $order_id));
                    if (count($existing_entries) == 0) {
                        $this->claimCoupon($em, $user, $order_id, $ancestor_id, $membership_product);

                        $userEntries = new SweepstakesUserEntries();
                        $userEntries->setUser($user);
                        $userEntries->setSweepstakes($sweepstakes);
                        $userEntries->setEntries($membership_product->getEntries());
                        $userEntries->setSweepstakesProduct($membership_product);
                        $userEntries->setDescription('Membership Renewed');
                        $userEntries->setOrderId($order_id);
                        $userEntries->setReturning(true);
                        $userEntries->setUtmSource($utmSource);
                        $userEntries->setUtmMedium($utmMedium);
                        $em->persist($userEntries);
                    }
                    $em->flush();

                    $userManager->updateUser($user, true);
                } else  if ($order_status == 0) {
                    // The order has been failed.
                    /*
                     * Day 0: Fail
                     * Day 1: Attempt
                     * Day 2: Attempt
                     * Day 7: Attempt
                     * Day 8: Attempt / Suspend cancel
                    */
                    $failure = $em->getRepository('RestomodsListingBundle:SubscriptionFailure')->findOneBy(array('orderId' => $order_id));
                    if (!$failure) {
                        // Day 0: Fail, retry after one day
                        $failure = new SubscriptionFailure();
                        $failure->setOrderId($order_id);
                        $failure->setAncestorId($ancestor_id);
                        $failure->setNextTryAt(date_create(date('Y-m-d H:i:s', strtotime('+1 day'))));
                        $failure->setUser($user);
                        $failure->setRetryCount(1);
                        $em->persist($failure);
                    } else {
                        $retry_count = $failure->getRetryCount();
                        $date = $failure->getCreatedAt();
                        $difference = (new DateTime())->diff($date);
                        if ($is_debug) {
                            $next_try_at = $failure->getNextTryAt();
                            $difference = $next_try_at->diff($date);
                        }
                        if ($difference->d <= 1) {

                            // Day 1: Fail, retry after one day
                            $date->add(new DateInterval('P2D'));
                            $failure->setNextTryAt(date_create(date("Y-m-d H:i:s", $date->getTimestamp())));
                            $failure->setRetryCount($retry_count + 1);
                        } else if ($difference->d <= 2) {
                            // Day 2: Fail, retry after 5 days
                            $date->add(new DateInterval('P7D'));
                            $failure->setNextTryAt(date_create(date("Y-m-d H:i:s", $date->getTimestamp())));
                            $failure->setRetryCount($retry_count + 1);
                        } else if ($difference->d <= 7) {
                            // Day 7: Fail, retry after 1 day
                            $date->add(new DateInterval('P8D'));
                            $failure->setNextTryAt(date_create(date("Y-m-d H:i:s", $date->getTimestamp())));
                            $failure->setRetryCount($retry_count + 1);
                        } else {
                            // Day 8: Fail, suspend/cancel
                            $limelight = $this->get( 'restomods.limelight' );
                            $limelight->cancelSubcriptionWithOrderId( $order_id );
                            $em->remove($failure);
                            $user->setSubscriptionOrderId( null );
                            $user->removeRole('ROLE_FREE_USER')->removeRole('ROLE_SUBSCRIBER_USER')->addRole('ROLE_FREE_USER');
                            $userManager->updateUser($user, true);
                        }
                    }
                    $em->flush();
                }
            }
        }
        return new JsonResponse(array('success' => true));
    }

    private function claimCoupon($em, $user, $order_id, $ancestor_id, $product) {
        $membership_time = time();
        $ancestor = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findOneBy(array('orderId' => $ancestor_id));
        if ($ancestor) {
            $now = time();
            $ancestor_time = $ancestor->getCreatedAt()->getTimestamp();
            $months = round(($now - $ancestor_time) / (30 * 86400));
            $membership_time = strtotime("+$months months", $ancestor_time);
        }
        $membership_date = date('Y-m-d', $membership_time);

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
            'membership_date' => $membership_date,
            'rebill' => 1
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
    }

    public function stripeAction(Request $request){
        $request_content = $request->getContent();
        //$request_content = '{ "id": "evt_1BxubfE3X6hGkeZE5YXLS9oH", "object": "event", "api_version": "2018-02-06", "created": 1519208027, "data": { "object": { "id": "in_1BxubeE3X6hGkeZEKJkO7olV", "object": "invoice", "amount_due": 900, "application_fee": null, "attempt_count": 0, "attempted": true, "billing": "charge_automatically", "charge": "ch_1BxubfE3X6hGkeZEV19BHDAQ", "closed": true, "currency": "usd", "customer": "cus_CMdtzJ2wPI4v8h", "date": 1519208026, "description": null, "discount": null, "due_date": null, "ending_balance": 0, "forgiven": false, "lines": { "object": "list", "data": [ { "id": "sub_CMdtKA9VLlahC1", "object": "line_item", "amount": 900, "currency": "usd", "description": "1 Ã— RM (at $9.00 / month)", "discountable": true, "livemode": false, "metadata": { }, "period": { "start": 1519208026, "end": 1521627226 }, "plan": { "id": "RM", "object": "plan", "amount": 900, "created": 1506419788, "currency": "usd", "interval": "month", "interval_count": 1, "livemode": false, "metadata": { }, "nickname": null, "product": "prod_BTCC0KajmUbmgF", "trial_period_days": null }, "proration": false, "quantity": 1, "subscription": null, "subscription_item": "si_CMdtqebOjvgwHj", "type": "subscription" } ], "has_more": false, "total_count": 1, "url": "/v1/invoices/in_1BxubeE3X6hGkeZEKJkO7olV/lines" }, "livemode": false, "metadata": { }, "next_payment_attempt": null, "number": "77f87b16ae-0001", "paid": true, "period_end": 1519208026, "period_start": 1519208026, "receipt_number": null, "starting_balance": 0, "statement_descriptor": null, "subscription": "sub_CMdtKA9VLlahC1", "subtotal": 900, "tax": null, "tax_percent": null, "total": 900, "webhooks_delivered_at": null } }, "livemode": false, "pending_webhooks": 1, "request": { "id": "req_geMDZ9iwngYl3g", "idempotency_key": null }, "type": "invoice.payment_succeeded" }';
        $success = false;

        if($request_content) {
            $request = json_decode($request_content, true);
            if (isset($request['type']) && $request['type'] == 'invoice.payment_succeeded' && isset($request['data']['object'])) {
                $object = $request['data']['object'];
                //var_dump($object); exit;
                if($object['subscription']){
                    $em = $this->getDoctrine()->getManager();
                    $userManager = $this->container->get('fos_user.user_manager');
                    $user = $em->getRepository('ApplicationSonataUserBundle:User')->findOneBy(array('stripeSubscriptionId' => $object['subscription']));
                    if($user){
                        $utmMedium = $user->getUtmMedium();
                        $utmSource = $user->getUtmSource();
                        if (empty($utmSource)) $utmSource = '';
                        if (empty($utmMedium)) $utmMedium = '';
                        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
                        if($sweepstakes){

                            if ($object['period_start'] <> $object['period_end']) {
                                // if not first payment, add sweepstakes entry
                                $userEntries = new SweepstakesUserEntries();
                                $userEntries->setUser($user);
                                $userEntries->setSweepstakes($sweepstakes);
                                $userEntries->setEntries(5);
                                $userEntries->setDescription('Membership Renewed');
                                $userEntries->setStripeSubscriptionId($object['subscription']);
                                $userEntries->setStripeInvoiceId($object['id']);
                                $userEntries->setStripeChargeId($object['charge']);
                                $userEntries->setReturning(true);
                                $userEntries->setUtmMedium($utmMedium);
                                $userEntries->setUtmSource($utmSource);
                                $em->persist($userEntries);
                                $em->flush();
                                $success = true;
                            } else {
                                // if first payment, update existing
                                $userEntriesArray = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findBy(array('stripeSubscriptionId' => $object['subscription'], 'stripeChargeId' => '', 'stripeInvoiceId' => ''), array( 'createdAt' => 'ASC' ));
                                if (count($userEntriesArray) > 0 && count($userEntriesArray) == $object["lines"]["total_count"]) {
                                    foreach ($userEntriesArray as $userEntries) {
                                        $userEntries->setStripeInvoiceId($object['id']);
                                        $userEntries->setStripeChargeId($object['charge']);
                                        $em->persist($userEntries);
                                    }
                                    if (count($userEntriesArray) > 0) {
                                        $em->flush();
                                        $success = true;
                                    }
                                }
                            }
                        }

                        $user->removeRole('ROLE_FREE_USER')->removeRole('ROLE_SUBSCRIBER_USER')->addRole('ROLE_SUBSCRIBER_USER');
                        $user->setSubscribedAt(new \DateTime());
                        $user->setStripeSubscriptionId($object['subscription']);
                        $user->setStripeCustomerId($object['customer']);
                        $userManager->updateUser($user, true);
                    } else {
                        $prev_entries = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findBy(array('stripeSubscriptionId' => $object['subscription']));
                        if (count($prev_entries) > 0) {
                            $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
                            $user = $prev_entries[0]->getUser();
                            $utmMedium = $user->getUtmMedium();
                            $utmSource = $user->getUtmSource();
                            if (empty($utmSource)) $utmSource = '';
                            if (empty($utmMedium)) $utmMedium = '';
                            if ($object['period_start'] <> $object['period_end']) {
                                $userEntries = new SweepstakesUserEntries();
                                $userEntries->setUser($user);
                                $userEntries->setSweepstakes($sweepstakes);
                                $userEntries->setEntries(5);
                                $userEntries->setDescription('Membership Renewed');
                                $userEntries->setStripeSubscriptionId($object['subscription']);
                                $userEntries->setStripeInvoiceId($object['id']);
                                $userEntries->setStripeChargeId($object['charge']);
                                $userEntries->setReturning(true);
                                $userEntries->setUtmMedium($utmMedium);
                                $userEntries->setUtmSource($utmSource);
                                $em->persist($userEntries);
                                $em->flush();
                                $success = true;
                            }

                            $user->removeRole('ROLE_FREE_USER')->removeRole('ROLE_SUBSCRIBER_USER')->addRole('ROLE_SUBSCRIBER_USER');
                            $user->setSubscribedAt(new \DateTime());
                            $user->setStripeSubscriptionId($object['subscription']);
                            $user->setStripeCustomerId($object['customer']);
                            $userManager->updateUser($user, true);
                        }
                    }
                }
            }
            file_put_contents('logs/stripe_'.date('Y-m-d').'.txt',date('Y-m-d H:i:s').": ".($success ? 'Added > ':'Skipped > ').$request_content."\n\n", FILE_APPEND);
        }
        return new JsonResponse(array('success' => $success));
    }

}

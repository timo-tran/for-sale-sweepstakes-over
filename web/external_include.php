<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Restomods\ListingBundle\Entity\SweepstakesUserEntries;
use Restomods\ListingBundle\Helper\SendgridHelper;

$kernel = null;

// $response = $kernel->handle($request);
//
// $session = new Session(new PhpBridgeSessionStorage());

function wp_symfony_load_kernel() {
    global $kernel;
    if ($kernel != null) { return; }

    /** @var \Composer\Autoload\ClassLoader $loader */
    $loader = require __DIR__.'/../app/autoload.php';
    include_once __DIR__.'/../app/bootstrap.php.cache';


    session_name('PHPSESSID');
    $kernel = new AppKernel('prod', false);
    $kernel->loadClassCache();
    // Debug::enable();

    // $kernel = new AppKernel('dev', true);
    // $kernel->loadClassCache();
    $kernel->boot();
    // //
    if (session_status() == PHP_SESSION_NONE) {
        $session = new Session($kernel->getContainer()->get('session.storage'));
        $session->start();
        $request = Request::createFromGlobals();
        $request->setSession($session);
    }
}

function wp_symfony_get_current_user() {
    wp_symfony_load_kernel();
    global $kernel;
    $sf2_attributes = $_SESSION['_sf2_attributes'];
    $token = null;
    foreach ($sf2_attributes as $key => $attr) {
        if (strpos($key, '_security_') !== false) {
            $token = unserialize($attr);
            // break;
        }
    }

    if ($token == null) {return;}

    $user = $token->getUser();
    if ($user == null) { return; }

    $userManager = $kernel->getContainer()->get('fos_user.user_manager');
    $user = $userManager->findUserByUsernameOrEmail($user->getEmail());
    return $user;
}

function wp_symfony_is_subscribed_user()
{
    $user = wp_symfony_get_current_user();
    return $user != null && in_array('ROLE_SUBSCRIBER_USER', $user->getRoles());
}

function wp_symfony_claim_order($order_id, $amount, $order = null) {
    global $kernel;

    $user = wp_symfony_get_current_user();
    $em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
    $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
    $sendgrid = $kernel->getContainer()->get('restomods.sendgrid.api');
    if ($order != null) {

        $userManager = $kernel->getContainer()->get('fos_user.user_manager');
        $email = $order->get_billing_email();

        // find user with billing email
        $user = $userManager->findUserByUsernameOrEmail($email);
        if ($user != null) {
            $needToSave = true;
            $payment_method = $order->get_payment_method();
            $transaction_id = $order->get_transaction_id();

            if ($payment_method == 'limelight' && isset($transaction_id)) {
                $entries = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findBy(array('orderId' => $transaction_id));
                if (count($entries) > 0) {
                    $needToSave = false;
                }
            }

            if ($needToSave) {
                // if user with the billing email exists, create sweepstakes user entries
                $userEntries = wp_symfony_new_sweepstakes($user, $sweepstakes, $order_id, $amount);
                $em->persist($userEntries);
                $em->flush();

                // update sendgrid receipt
                wp_symfony_update_sendgrid_receipt($sendgrid, $user);
            }
        } else {
            $phone = $order->get_billing_phone();
            $firstName = $order->get_billing_first_name();
            $lastName = $order->get_billing_last_name();
            $address = $order->get_billing_address_1();
            $city = $order->get_billing_city();
            $state = $order->get_billing_state();
            $zip = $order->get_billing_postcode();
            $country = $order->get_billing_country();

            // create user
            $user = $userManager->createUser();
            $user->setPlainPassword(md5(time()));
            $tokenGenerator = $kernel->getContainer()->get('fos_user.util.token_generator');
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
            $user->setFirstname($firstName);
            $user->setLastname($lastName);
            $user->setPhone($phone);
            $userManager->updateUser($user, true);

            // and then create sweepstakes user entries
            $userEntries = wp_symfony_new_sweepstakes($user, $sweepstakes, $order_id, $amount);
            $em->persist($userEntries);
            $em->flush();

            // update sendgrid receipt
            wp_symfony_update_sendgrid_receipt($sendgrid, $user);
        }
    } else if ($user != null) {
        // create sweepstakes user entries
        $userEntries = wp_symfony_new_sweepstakes($user, $sweepstakes, $order_id, $amount);
        $em->persist($userEntries);
        $em->flush();

        // update sendgrid receipt
        wp_symfony_update_sendgrid_receipt($sendgrid, $user);
    }
}

function wp_symfony_new_sweepstakes($user, $sweepstakes, $order_id, $amount) {
    $entries = round($amount);
    $userEntries = new SweepstakesUserEntries();
    $userEntries->setUser($user);
    $userEntries->setSweepstakes($sweepstakes);
    $userEntries->setEntries($entries);
    $userEntries->setDescription('Bonus points for purchasing : '. $order_id);
    return $userEntries;
}

function wp_symfony_update_sendgrid_receipt($sendgrid, $user) {
    $sendgrid_data = array();
    $sendgrid_data[SendgridHelper::FIELD_EMAIL] = $user->getEmail();
    $sendgrid_data[SendgridHelper::FIELD_FIRST_NAME] = $user->getFirstname();
    $sendgrid_data[SendgridHelper::FIELD_LAST_NAME] = $user->getLastname();
    $sendgrid_data[SendgridHelper::FIELD_COMMERCE] = 1;
    $sendgrid->updateReceipt($sendgrid_data);
}

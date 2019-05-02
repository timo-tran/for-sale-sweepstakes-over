<?php

namespace Restomods\ListingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Restomods\ListingBundle\Entity\SweepstakesUserEntries;
use Stripe\Customer;
use Stripe\Stripe;
use DateInterval;
class LimeLightMigrationCommand extends ContainerAwareCommand
{
    const SANDBOX = false;
    protected function configure()
    {
        $this
            ->setName('restomods:sweepstakes:limelight')
            ->addArgument('type', InputArgument::REQUIRED, "Type is required. Either cf or ...")
            ->setDescription('Migrate LimeLight')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('type') == "import") {
            $this->importFromStripe($input, $output);
        } else if ($input->getArgument('type') == "compare") {
            $this->compare($input, $output);
        } else if ($input->getArgument('type') == "migrate") {
            $this->migrate($input, $output);
        } else if ($input->getArgument('type') == "missings") {
            $this->importMissingSweepstakes($input, $output);
        } else if ($input->getArgument('type') == "product") {
            $this->fillProductIds($input, $output);
        } else if ($input->getArgument('type') == "invalid") {
            $this->findInvalidOrders($input, $output);
        } else if ($input->getArgument('type') == "logs") {
            $this->importFromLogs($input, $output);
        }
    }

    private function importFromLogs(InputInterface $input, OutputInterface $output) {
        $timestamp = 1544400000;
        $end_timestamp = 1544745600;
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $userManager = $container->get('fos_user.user_manager');
        $subscription_product_id = $container->getParameter('restomods.limelight.membership_product_id');
        for (;;) {
            $log_path = $container->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'web/logs/limelight_'.date('Y-m-d', $timestamp).'.txt';
            if (!file_exists($log_path)) { goto next_date; }
            $log = file_get_contents($log_path);if (!$log) { goto next_date; }
            preg_match_all('/\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}:\s/', $log, $matches, PREG_OFFSET_CAPTURE);
            $matches = $matches[0];
            $output->writeln([PHP_EOL.'>>>> Log: '.$log_path.' count:'.count($matches)]);
            for ($i = 0; $i < count($matches); $i ++) {
                $match = $matches[$i];
                $word = $match[0];
                $pos = $match[1];

                $body = '';
                if (count($matches) > $i + 1) {
                    $body = substr($log, $pos + strlen($word), $matches[$i + 1][1] - $pos - strlen($word));
                } else {
                    $body = substr($log, $pos + strlen($word));
                }
                $body = trim($body);
                $event = json_decode($body, true);
                $product_id_csv = $event['product_id_csv'];
                $product_ids = explode(',', $product_id_csv.'');

                if ($event['ischargeback'] == 1 || $event['is_fraud'] == 1) {
                    $output->write(['-']);

                    // if chargeback, we need to mark the listing or sweepstakes user entries inactive

                    $order_id = $event['order_id'];
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

                } else if (in_array($subscription_product_id, $product_ids)) {

                    // it it's subscription order

                    $ancestor_id = $event['ancestor_id'];
                    $order_id = $event['order_id'];
                    $order_status = $event['order_status'];

                    //
                    // We skip the first order in the subscription
                    //
                    if ($ancestor_id == $order_id) {
                        // $output->write(['=']);
                    } else if ($order_status != 1) {
                        // $output->write(['.']);
                    } else {
                        $existing_entries = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findBy(array('orderId' => $order_id));
                        if (count($existing_entries) > 0) {
                            // $output->write(['*']);
                        } else {
                            $output->write(['^']);
                            $utmSource = $event['sid'];
                            $utmMedium = $event['afid'];
                            if (empty($utmSource)) $utmSource = '';
                            if (empty($utmMedium)) $utmMedium = '';

                            $email = $event['email'];
                            $user = $userManager->findUserByUsernameOrEmail($email);
                            if($user){

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
                                $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
                                if($sweepstakes){

                                    if (!$container->get('restomods.rawsqlhelper')->isUserInSweepstakes($user, $sweepstakes)){
                                        $sweepstakes->addUser($user);
                                    }

                                    $userEntries = new SweepstakesUserEntries();
                                    $userEntries->setUser($user);
                                    $userEntries->setSweepstakes($sweepstakes);
                                    $userEntries->setEntries(5);
                                    $userEntries->setDescription('Membership Renewed');
                                    $userEntries->setOrderId($order_id);
                                    $userEntries->setReturning(true);
                                    $userEntries->setUtmSource($utmSource);
                                    $userEntries->setUtmMedium($utmMedium);
                                    $em->persist($userEntries);
                                    $output->writeln([": Adding order >>> ".$word." : ".$order_id.")\n"]);
                                }

                                $userManager->updateUser($user, false);
                            }
                        }
                    }
                } else {
                    // $output->write(['~']);
                }
                unset($event);
                unset($body);
            }
            $em->flush();
            next_date:
            $timestamp = $timestamp + 86400;
            if ($timestamp > $end_timestamp) { break; }
        }
    }

    private function findInvalidOrders(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();
        $limelight = $container->get('restomods.limelight.v2');

        $criterias = array();
        $criterias[] = array('all_refunds' => 1);
        // $criterias[] = array('declines' => 1);
        $criterias[] = array('fraud' => 1);
        $criterias[] = array('void' => 1);

        $invalidOrders = array();

        foreach($criterias as $criteria) {
            $res = $limelight->findOrdersWithCriteria($criteria, array(3));
            if (isset($res['order_id']) && is_array($res['order_id'])) {
                $invalidOrders = array_merge($invalidOrders, $res['order_id']);
            }

            $res = $limelight->findOrdersWithCriteria($criteria, array(5));
            if (isset($res['order_id']) && is_array($res['order_id'])) {
                $invalidOrders = array_merge($invalidOrders, $res['order_id']);
            }
        }

        $output->writeln([json_encode($invalidOrders)]);
    }

    private function fillProductIds(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        $query = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->createQueryBuilder('e')
                    ->andWhere("e.sweepstakes = :sweepstakes")
                    ->andWhere("e.orderId != ''")
                    ->andWhere("e.orderId != '0'")
                    ->andWhere("e.description not like '%Membership%'")
                    ->andWhere("e.sweepstakesProduct IS NULL")
                    ->setParameter('sweepstakes', $sweepstakes)
                    ->getQuery();
        $entries_array = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        $order_ids = array();
        foreach($entries_array as $e) {
            $order_ids[] = $e->getOrderId();
        }
        if (count($order_ids) == 0) {
            return;
        }
        $order_ids_separated = implode(",", $order_ids);
        $limelight = $container->get('restomods.limelight');
        $criteria = array();
        $criteria["order_ids"] = $order_ids_separated;
        $criteria["criteria"] = "all";
        $criteria["start_date"] = "06/01/2018";
        $criteria["end_date"] = "07/19/2018";
        $output->writeln(["search:".count($order_ids)]);
        $orders = $limelight->getOrdersWithCriteria($criteria, true);
        $jsonOrders = json_decode(json_encode($orders), true);
        $output->writeln(["found:".count($jsonOrders)]);
        $count = 0;
        foreach($entries_array as $e) {
            if (isset($jsonOrders[$e->getOrderId()])) {
                $count ++;
                $order = $jsonOrders[$e->getOrderId()];
                $product_id = $order['products[0][product_id]'];
                $sweepstakesProduct = $em->getRepository('RestomodsListingBundle:SweepstakesProduct')->findOneBy(array('sweepstakes' => $sweepstakes, 'limeLightProductId' => $product_id));
                $e->setSweepstakesProduct($sweepstakesProduct);
            }
        }
        $em->flush();
        $output->writeln(["updated :".$count]);
    }

    private function importMissingSweepstakes(InputInterface $input, OutputInterface $output) {
        $criteria = array();
        $criteria["start_date"] = "07/10/2018";
        $criteria["start_time"] = "00:10:00";
        $criteria["end_date"] = "07/10/2018";
        $criteria["end_time"] = "13:00:00";
        $criteria["criteria"] = "approved";

        $container = $this->getContainer();
        $userManager = $container->get('fos_user.user_manager');
        $em = $container->get('doctrine')->getManager();
        $limelight = $container->get('restomods.limelight');
        $orders = $limelight->getOrdersWithCriteria($criteria);

        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        $output->writeln([count($orders)]);
        foreach ($orders as $order) {

            $new = false;
            $email = $order->email_address;
            $order_id = $order->order_id;
            $product_id = $order->products[0]->product_id;
            $product_name = $order->products[0]->name;
            $time_stamp = $order->time_stamp;
            $date = \DateTime::createFromFormat("Y-m-d H:i:s",$time_stamp);
            $date->add(new DateInterval('PT4H'));

            $output->writeln([$order_id.'>>>>>>>']);


            $user = $userManager->findUserByUsernameOrEmail($email);
            if (!$user) {
                $output->writeln(['missing user:'.$email.'']);
                continue;
            }

            $entries_count = 5;

            if ($product_id != 1) {
                $sweepstakes_product = $em->getRepository('RestomodsListingBundle:SweepstakesProduct')->findOneBy(array('sweepstakes' => $sweepstakes, 'limeLightProductId' => $product_id));
                if (!$sweepstakes_product) {
                    $output->writeln(['missing product:'.$product_id.'']);
                    continue;
                }

                $entries_count = $sweepstakes_product->getEntries();
            }

            if (stripos($product_name, 'Membership') !== false && !$container->get('restomods.rawsqlhelper')->isUserInSweepstakes($user, $sweepstakes)){
                $sweepstakes->addUser($user);
                $output->writeln(['join user:'.$email]);
                if ($user->getCreatedAt()->getTimestamp() > 1531180800) {
                    $new = true;
                }
            }

            $results = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findBy(array('orderId' => $order_id));
            if (count($results) == 0) {
                $userEntries = new SweepstakesUserEntries();
                $userEntries->setUser($user);
                $userEntries->setSweepstakes($sweepstakes);
                $userEntries->setEntries($entries_count);
                $userEntries->setDescription($product_name);
                $userEntries->setOrderId($order_id);
                $userEntries->setCreatedAt(date_create(date("Y-m-d H:i:s", $date->getTimestamp())));
                $userEntries->setVerifiedAt(date_create(date("Y-m-d H:i:s", $date->getTimestamp())));
                $em->persist($userEntries);
            }
            $em->flush();

            if($new){
                $tokenGenerator = $container->get('fos_user.util.token_generator');
                $user->setConfirmationToken($tokenGenerator->generateToken());
                $user->setPasswordRequestedAt(new \DateTime());
                $user->generateReferrerCode();

                $confirmationUrl = str_replace('http:', $container->getParameter('restomods.url.scheme'), $container->get('router')->generate('fos_user_resetting_reset', array('token' => $user->getConfirmationToken()), true));
                $referralUrl = str_replace('http:', $container->getParameter('restomods.url.scheme'), $container->get('router')->generate('restomods_referral', array('code' => $user->getReferrerCode()), true));
                $rendered = $container->get('twig')->render('RestomodsListingBundle:Emails:funnel.reset.html.twig', array(
                    'sweepstakes' => $sweepstakes ? $sweepstakes->getName() : null,
                    'user' => $user,
                    'referralUrl' => $referralUrl,
                    'confirmationUrl' => $confirmationUrl
                ));
                if (self::SANDBOX) {
                    $res = $container->get('restomods.mailer')->sendMail("fastitteam+test@gmail.com", "Welcome to RestoMods - Activate your account", $rendered);
                    $output->writeln(["sent email(sandbox) to ". $user->getEmail().$rendered.":".$res]);
                } else {
                    $res = $container->get('restomods.mailer')->sendMail($user->getEmail(), "Welcome to RestoMods - Activate your account", $rendered);
                    $output->writeln(["sent email to ". $user->getEmail().$rendered.":".$res]);
                }
                $userManager->updateUser($user, true);
            }
        }
    }

    private function migrate(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $file = $container->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'exports.csv';
        $csv = file_get_contents($file);
        $results = array_map("str_getcsv", explode("\n", $csv));
        $output->writeln([count($results)]);

        $fp = fopen('migrate.csv', 'w');

        $userManager = $container->get('fos_user.user_manager');
        $index = 0;
        foreach($results as $row){
            if ($index == 0) {
                fputcsv($fp, $row);
                $index ++;
                continue;
            }
            try
            {
                $email = $row[9];
                $address = $row[2];
                $city = $row[4];
                $state = $row[5];
                $zip = $row[6];
                $country = $row[7];
                $phone = $row[8];
                $user = $userManager->findUserByUsernameOrEmail($email);
                if (!$user) {
                    fputcsv($fp, $row);
                    continue;
                }

                if ((!isset($address) || empty($address)) &&
                 (!isset($city) || empty($city)) &&
                 (!isset($state) || empty($state)) &&
                 (!isset($zip) || empty($zip))) {
                    $row[2] = $user->getAddress() ? $user->getAddress() : '';
                    $row[4] = $user->getCity() ? $user->getCity() : '';
                    $row[5] = $user->getState() ? $user->getState() : '';
                    $row[6] = $user->getZip() ? $user->getZip() : '';
                    $row[7] = $user->getCountry() ? $user->getCountry() : '';

                    $row[12] = $row[2];
                    $row[14] = $row[4];
                    $row[15] = $row[5];
                    $row[16] = $row[6];
                    $row[17] = $row[7];
                }

                if (!isset($phone) || empty($phone)) {
                    $row[8] = $user->getPhone() ? $user->getPhone() : '';
                }
                fputcsv($fp, $row);
            }
            catch (Exception $e)
            {
                $output->writeln([$e->getMessage(), $index]);
            }
        }
        fclose($fp);
    }

    private function compare(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $file = $container->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'exports.csv';
        $csv = file_get_contents($file);
        $results = array_map("str_getcsv", explode("\n", $csv));
        $output->writeln([count($results)]);

        $fp = fopen('compare.csv', 'w');

        $userManager = $container->get('fos_user.user_manager');
        $index = 0;
        $line = array('Mismatch', 'Email', 'S Address', 'S City', 'S State', 'S Zip', 'S Country', 'S Phone', 'D Address', 'D City', 'D State', 'D Zip', 'D Country', 'D Phone');
        fputcsv($fp, $line);
        foreach($results as $row){
            if ($index == 0) {
                $index ++;
                continue;
            }
            try
            {
                $email = $row[9];
                $address = $row[2];
                $city = $row[4];
                $state = $row[5];
                $zip = $row[6];
                $country = $row[7];
                $phone = $row[8];
                $user = $userManager->findUserByUsernameOrEmail($email);
                $issue_type = '';
                if (!$user) {
                    $issue_type = 'not found';
                    $line = $this->csvLineFromCompareData('not found', $row, null);
                    fputcsv($fp, $line);
                    continue;
                }

                $d_address = $user->getAddress() ? $user->getAddress() : '';
                $d_city = $user->getCity() ? $user->getCity() : '';
                $d_state = $user->getState() ? $user->getState() : '';
                $d_zip = $user->getZip() ? $user->getZip() : '';
                $d_phone = $user->getPhone() ? $user->getPhone() : '';

                if (strcasecmp(trim($address), trim($d_address)) <> 0) {
                    $line = $this->csvLineFromCompareData('address', $row, $user);
                    fputcsv($fp, $line);
                    continue;
                }
                if (strcasecmp(trim($city), trim($d_city)) <> 0) {
                    $line = $this->csvLineFromCompareData('city', $row, $user);
                    fputcsv($fp, $line);
                    continue;
                }
                if (strcasecmp(trim($state), trim($d_state)) <> 0) {
                    $line = $this->csvLineFromCompareData('state', $row, $user);
                    fputcsv($fp, $line);
                    continue;
                }
                if (strcasecmp(trim($zip), trim($d_zip)) <> 0) {
                    $line = $this->csvLineFromCompareData('zip', $row, $user);
                    fputcsv($fp, $line);
                    continue;
                }
                // if ($country != $user->getCountry()) {
                //     $line = $this->csvLineFromCompareData('country', $row, $user);
                //     fputcsv($fp, $line);
                //     continue;
                // }
                if (strcasecmp(str_replace(array(' ', '-', '(', ')'), '',$phone), str_replace(array(' ', '-', '(', ')'), '',$d_phone))) {
                    $line = $this->csvLineFromCompareData('phone', $row, $user);
                    fputcsv($fp, $line);
                    continue;
                }
            }
            catch (Exception $e)
            {
                $output->writeln([$e->getMessage(), $index]);
            }
        }
        fclose($fp);
    }

    private function csvLineFromCompareData($compare, $limelightRow, $user) {
        $row = array();
        $row[] = $compare;
        $row[] = $limelightRow[9];
        $row[] = $limelightRow[2];
        $row[] = $limelightRow[4];
        $row[] = $limelightRow[5];
        $row[] = $limelightRow[6];
        $row[] = $limelightRow[7];
        $row[] = $limelightRow[8];
        if ($user) {
            $row[] = $user->getAddress();
            $row[] = $user->getCity();
            $row[] = $user->getState();
            $row[] = $user->getZip();
            $row[] = $user->getCountry();
            $row[] = $user->getPhone();
        }
        return $row;
    }
    private function importFromStripe(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        Stripe::setApiKey( $container->getParameter('restomods.stripe.secret_key') );

        $customers = array();
        $starting_after = null;

        $fp = fopen('exports.csv', 'w');
        $fields = array("shipping_first_name","shipping_last_name","ship_address1","ship_address2","ship_city","ship_state","ship_zip","ship_country","phone","email_address","billing_first_name","billing_last_name","billing_street_address1","billing_street_address2","billing_city","billing_state","billing_zip","billing_country","payment_type","credit_card_number","credit_card_expiration","CVV","checking_account_number","checking_routing_number","checking_ssn","campaign_Id","shipping_Id","product_Id","product_quantity","product_custom_price","product_attributes","Ip_address","custom_field_1","custom_field_2","custom_field_3","custom_field_4","custom_field_5","AFID","SID","AFFID","C1","C2","C3","BID","AID","OPT","additional_product_ids","additional_product_quantities","additional_product_custom_prices","additional_product_attributes","","","recurring_date");
        fputcsv($fp, $fields);

        $total_count = 0;
        $subscription_count = 0;
        while(true) {
            $params = array("limit" => 10);
            if ($starting_after != null) {
                $params['starting_after'] = $starting_after;
            }

            $response = Customer::all($params);
            foreach($response->data as $customer) {
                // $customers[] = $customer;
                $starting_after = $customer->id;

                $total_count = $total_count ++;

                $subscription = null;
                foreach($customer->subscriptions->data as $s) {
                    if ($s->status == 'active') {
                        $subscription = $s;
                        break;
                    }
                }

                if ($subscription != null) {
                    $subscription_count ++;
                    $row = $this->dataFromCustomer($customer, $subscription);
                    fputcsv($fp, $row);
                }
            }

            if (!$response->has_more) {break;}
        }
        fclose($fp);

        $output->writeln(array('total'=>$total_count, 'real'=>$subscription_count));

        // $output->writeln(json_encode($customer));
    }

    private function dataFromCustomer($customer, $subscription) {
        $data = array();
        $source = $customer->sources->data[0];
        $metadata = $customer->metadata;
        $names = $this->split_name($source->name);
        $data[] = $names[0];
        $data[] = $names[1];
        $data[] = $source->address_line1;
        $data[] = $source->address_line2;
        $data[] = $source->address_city;
        $data[] = $source->address_state;
        $data[] = $source->address_zip;
        $data[] = $source->country;
        $data[] = (isset($metadata->phone) ? $metadata->phone : '');
        $data[] = $customer->email;
        $data[] = $names[0];
        $data[] = $names[1];
        $data[] = $source->address_line1;
        $data[] = $source->address_line2;
        $data[] = $source->address_city;
        $data[] = $source->address_state;
        $data[] = $source->address_zip;
        $data[] = $source->country;
        $data[] = $source->brand;
        $data[] = ''; //credit_card_number
        $data[] = ''; //credit_card_expiration
        $data[] = ''; //CVV
        $data[] = ''; //checking_account_number
        $data[] = ''; //checking_routing_number
        $data[] = ''; //checking_ssn
        $data[] = '2';//campaign_Id
        $data[] = '3';
        $data[] = '1';
        $data[] = '1';
        $data[] = ''; //product_custom_price
        $data[] = '';
        $data[] = '';
        $data[] = $customer->id; //custom_field_1
        $data[] = date("m/d/Y", strtotime("+1 month", $subscription->current_period_start));
        $data[] = '';
        $data[] = '';
        $data[] = '';
        $data[] = '';//AFID
        $data[] = '';
        $data[] = '';
        $data[] = '';
        $data[] = '';
        $data[] = '';
        $data[] = '';
        $data[] = '';
        $data[] = '';
        $data[] = '';//additional_product_ids
        $data[] = '';
        $data[] = '';
        $data[] = '';
        $data[] = '';
        $data[] = '';
        $data[] = date("m/d/Y", $subscription->current_period_start);
        return $data;
    }

    private function split_name($name) {
        if ($name == null) $name = '';
        $name = trim($name);
        $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $first_name = trim( preg_replace('#'.$last_name.'#', '', $name ) );
        return array($first_name, $last_name);
    }
}

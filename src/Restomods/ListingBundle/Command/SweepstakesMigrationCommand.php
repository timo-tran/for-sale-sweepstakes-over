<?php

namespace Restomods\ListingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Restomods\ListingBundle\Entity\SweepstakesUserEntries;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Dispute;
use Stripe\Invoice;
use Stripe\Subscription;
use Stripe\Customer;
use Stripe\Error\InvalidRequest;
class SweepstakesMigrationCommand extends ContainerAwareCommand
{
    const SANDBOX = false;
    const TIMESTAMP_5_1 = 1525132800; // 20180511 00:00:00
    const TIMESTAMP_6_1 = 1527811200; // 20180511 00:00:00

    // admin manually generated sweepstakes user entries with description "join" at the below date/time:
    const TIMESTAMP_4_27 = 1524860220; //2018-04-27 8:17:00 PM
    const TIMESTAMP_4_27_2 = 1524860400; //2018-04-27 8:20:00 PM
    const TIMESTAMP_5_20 = 1526833620; //2018-05-20 16:27:00
    const TIMESTAMP_5_20_2 = 1526833680; //2018-05-20 16:28:00
    const TIMESTAMP_5_31 = 1527740160; //2018-05-31 04:16:00
    const TIMESTAMP_5_31_2 = 1527740220; //2018-05-31 04:17:00

    protected function configure()
    {
        $this
            ->setName('restomods:sweepstakes:migrate')
            ->addArgument('type', InputArgument::REQUIRED, "Type is required. Either cf or ...")
            ->setDescription('Migrate sweepstakes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('type') == "cf") {
            $this->migrateFunnelData($input, $output);
        } else if ($input->getArgument('type') == "import") {
            $this->importFunnelData($input, $output);
        } else if ($input->getArgument('type') == "db_missing_charge") {
            // $this->fillFromFunnelSaleYearData($input, $output);
            // $this->fillFromStripeLog($input, $output);
            $this->fillMissingChargeIdsButSubscriptionId($input, $output);
            $this->verifyAgainstStripe($input, $output);
            // $this->checkDisputes($input, $output);
            // Deprecated Method
            // $this->fillMissingChargeIds($input, $output);
        } else if ($input->getArgument('type') == "join") {
            $this->joinMembersToNewSweepstakes($input, $output);
        } else if ($input->getArgument('type') == "event") {
            $this->migrateStripeEvents($input, $output);
        } else if ($input->getArgument('type') == "utm") {
            $this->migrateSweepstakesUTM($input, $output);
        } else if ($input->getArgument('type') == "cf_utm") {
            $this->migrateCFSweepstakesUTM($input, $output);
        } else if ($input->getArgument('type') == 'bonus') {
            // $this->checkBonusEntries($input, $output);
        } else if ($input->getArgument('type') == 'charges') {
            $this->checkCharges($input, $output);
        }
    }

    private function checkCharges(InputInterface $input, OutputInterface $output) {
        $startAt = 1544774400;
        $endAt = 1544860800;
        $startingAfter = null;
        $response = null;
        $hasMore = true;

        $container = $this->getContainer();
        Stripe::setApiKey($container->getParameter('restomods.stripe.secret_key'));
        $em = $container->get('doctrine')->getManager();
        while($hasMore) {
            if (!isset($startingAfter)) {
                $response = Charge::all( array( 'created' => array('gte' => $startAt, 'lte' => $endAt), 'limit' => 50 ) );
            } else if (isset($startingAfter)) {
                $response = Charge::all( array( 'created' => array('gte' => $startAt, 'lte' => $endAt), 'limit' => 50, 'starting_after' => $startingAfter) );
            }
            if (isset($response)) {
                $hasMore = $response->has_more;
                foreach ($response->data as $charge) {
                    if ($charge->paid && !$charge->refunded) {
                        $existing_entries = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findBy(array('stripeChargeId' => $charge->id));
                        if (count($existing_entries) == 0) {
                            $output->write(["'".$charge->id."',"]);
                        }
                    }
                    $startingAfter = $charge->id.'';
                }
                unset($response);
            } else {
                break;
            }
        }
        $output->writeln(['']);
    }

    // private function checkBonusEntries(InputInterface $input, OutputInterface $output) {
    //     $container = $this->getContainer();
    //     $woocommerce = $container->get( 'restomods.woocommerce' );
    //     $em = $container->get('doctrine')->getManager();
    //     $params = array(
    //         'after'=>'2018-07-17T00:00:00',
    //         'order'=>'asc',
    //         'orderby'=>'id',
    //         'per_page'=>10,
    //         'page'=>1
    //     );
    //     $transactions = array();
    //     $page = 1;
    //     for(;;) {
    //         $params['page'] = $page;
    //         $res = $woocommerce->findOrders($params);
    //         if (!isset($res)) {
    //             break;
    //         }
    //
    //         foreach($res as $order) {
    //             if (!empty($order['transaction_id'])) {
    //                 $query = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->createQueryBuilder('e')
    //                             ->andWhere("e.description like :description")
    //                             ->setParameter('description', '%Bonus%'.$order['id'])
    //                             ->getQuery();
    //                 $entries_array = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
    //                 foreach($entries_array as $entry) {
    //                     if ($order['payment_method'] == 'limelight') {
    //                         $query = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->createQueryBuilder('e')
    //                                     ->andWhere("e.description not like '%Bonus%'")
    //                                     ->andWhere("e.orderId = :orderId")
    //                                     ->setParameter('orderId', $order['transaction_id'])
    //                                     ->getQuery();
    //                         $entries_array2 = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
    //                         $entry->setOrderId($order['transaction_id']);
    //                         foreach($entries_array2 as $e) {
    //                             $entry->setActive($e->getActive());
    //                             break;
    //                         }
    //                     } else {
    //                         $query = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->createQueryBuilder('e')
    //                                     ->andWhere("e.description not like '%Bonus%'")
    //                                     ->andWhere("e.stripeChargeId = :stripeChargeId")
    //                                     ->setParameter('stripeChargeId', $order['transaction_id'])
    //                                     ->getQuery();
    //                         $entries_array2 = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
    //                         $entry->setStripeChargeId($order['transaction_id']);
    //                         foreach($entries_array2 as $e) {
    //                             $entry->setActive($e->getActive());
    //                             break;
    //                         }
    //                     }
    //                 }
    //             }
    //         }
    //
    //         $em->flush();
    //         if (count($res) < 10) {
    //             break;
    //         }
    //
    //         $page = $page + 1;
    //         $output->write(["."]);
    //     }
    //     // $output->writeln([$res]);
    //     $output->writeln([json_encode($transactions)]);
    // }

    private function migrateCFSweepstakesUTM(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();
        $userManager = $container->get('fos_user.user_manager');
        $em = $container->get('doctrine')->getManager();
        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));

        $query = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->createQueryBuilder('e')
                    ->andWhere("e.funnelPurchaseId is not null")
                    ->andWhere("e.createdAt > :date")
                    ->andWhere("e.utmMedium = ''")
                    ->andWhere("e.active = true")
                    ->setParameter('date', date_create(date("Y-m-d H:i:s", 1534896000)))
                    ->getQuery();
        $entries_array = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        $output->writeln([count($entries_array)]);
        $count = 0;
        foreach($entries_array as $entry) {
            $user = $entry->getUser();
            $utmMedium = $user->getUtmMedium();
            $utmSource = $user->getUtmSource();
            if (!empty($utmMedium) || !empty($utmSource)) {
                if (!empty($utmMedium)) {
                    $entry->setUtmMedium($utmMedium);
                }
                if (!empty($utmSource)) {
                    $entry->setUtmSource($utmSource);
                }
                $em->persist($entry);
                $count ++;
            }

            if ($count % 100 == 0) {
                $em->flush();
            }

            $output->write(["."]);
        }
        $em->flush();

        $output->writeln(['Migrated : '.$count]);
    }

    private function migrateSweepstakesUTM(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();
        $userManager = $container->get('fos_user.user_manager');
        $em = $container->get('doctrine')->getManager();
        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));

        $query = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->createQueryBuilder('e')
                    ->andWhere("e.description not like '%Bonus%'")
                    ->andWhere("e.description not like '%join%'")
                    ->andWhere("e.createdAt > :date")
                    ->andWhere("e.utmMedium = ''")
                    ->andWhere("e.active = true")
                    ->setParameter('date', date_create(date("Y-m-d H:i:s", 1532476800)))
                    ->getQuery();
        $entries_array = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        $output->writeln([count($entries_array)]);
        $count = 0;
        $updatedUsers = array();
        $missings = array();
        foreach($entries_array as $entry) {
            $desc = $entry->getDescription();
            $timestamp = $entry->getCreatedAt()->getTimestamp();
            $event_name = null;

            if ($desc == 'RM Car Club Membership') {
                $event_name = 'sweepstakes.order.post';
            } else if ($desc == 'Vip 20') {
                $product = $entry->getSweepstakesProduct();
                if ($product->getId() == 11) {
                    // downsell
                    $event_name = 'sweepstakes.downsell.post';
                } else if ($product->getId() == 12) {
                    // bump offer
                    $event_name = 'sweepstakes.order.post';
                }
            } else if ($desc == 'Silver 20' || $desc == 'Gold 70' || $desc == 'Platinum 190') {
                $event_name = 'sweepstakes.upsell.post';
            } else if ($desc == 'Resto_Tshirt') {
                $event_name = 'sweepstakes.product.post';
            }

            if ($event_name != null) {
                $query = $em->getRepository( 'RestomodsListingBundle:Event' )->createQueryBuilder('e')
                            ->andWhere("e.createdAt < :date1")
                            ->andWhere("e.createdAt > :date2")
                            ->andWhere("e.user = :user")
                            ->andWhere("e.name = :eventName")
                            ->setParameter('date1', date_create(date("Y-m-d H:i:s", $timestamp + 30)))
                            ->setParameter('date2', date_create(date("Y-m-d H:i:s", $timestamp - 30)))
                            ->setParameter('user', $entry->getUser())
                            ->setParameter('eventName', $event_name)
                            ->orderBy( 'e.createdAt', 'DESC' )
                            ->getQuery();

                $events = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
                if (count($events) > 0) {
                    $utmMedium = $events[0]->getUtmMedium();
                    $utmSource = $events[0]->getUtmSource();
                    if (!empty($utmMedium) || !empty($utmSource)) {
                        if (!empty($utmMedium)) {
                            $entry->setUtmMedium($utmMedium);
                        }
                        if (!empty($utmSource)) {
                            $entry->setUtmSource($utmSource);
                        }
                        $em->persist($entry);

                        if ($desc == 'RM Car Club Membership') {
                            $user = $entry->getUser();
                            if ($user->getUtmMedium() != $utmMedium && $user->getUtmSource() != $utmSource) {
                                $user->setUtmMedium($utmMedium);
                                $user->setUtmSource($utmSource);
                                $userManager->updateUser($user, false);
                                $updatedUsers[] = $user->getId();
                            }
                        }
                    }
                } else {
                    $missings[] = $entry->getId();
                }
            } else if ($desc == 'Membership Renewed'){
                // Membership Renew
                $user = $entry->getUser();
                $utmMedium = $user->getUtmMedium();
                $utmSource = $user->getUtmSource();
                if (!empty($utmMedium) || !empty($utmSource)) {
                    if (!empty($utmMedium)) {
                        $entry->setUtmMedium($utmMedium);
                    }
                    if (!empty($utmSource)) {
                        $entry->setUtmSource($utmSource);
                    }
                    $em->persist($entry);
                }
            }

            if ($count % 500 == 0) {
                $em->flush();
            }

            $output->write(["."]);
            $count ++;

        }
        $em->flush();

        $output->writeln(['Found : '.$count]);
        $output->writeln(['Missing event : '.json_encode($missings)]);
        $output->writeln(['Updated user count : '.count($updatedUsers)]);
        $output->writeln(['Updated users : '.json_encode($updatedUsers)]);
    }

    private function migrateStripeEvents(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();
        $userManager = $container->get('fos_user.user_manager');
        $em = $container->get('doctrine')->getManager();
        Stripe::setApiKey($container->getParameter('restomods.stripe.secret_key'));
        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));


        $timestamp = 1543622400;
        $end_timestamp = 1547655520;
        for (;;) {
            $log_path = $container->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'web/logs/stripe_'.date('Y-m-d', $timestamp).'.txt';
            if (!file_exists($log_path)) { goto next_date; }
            $log = file_get_contents($log_path);if (!$log) { goto next_date; }

            $missings = array();
            $missings_previous_records = array();
            $added = 0;

            $output->writeln(['>>>> Log: '.$log_path]);
            preg_match_all('/\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}:\s\w+\s>/', $log, $matches, PREG_OFFSET_CAPTURE);
            $matches = $matches[0];
            for ($i = 0; $i < count($matches); $i ++) {
                $match = $matches[$i];
                $word = $match[0];
                $pos = $match[1];

                if (stripos($word, 'Skipped') !== false) {
                    // need to check against
                    $body = '';
                    if (count($matches) > $i + 1) {
                        $body = substr($log, $pos + strlen($word), $matches[$i + 1][1] - $pos - strlen($word));
                    } else {
                        $body = substr($log, $pos + strlen($word));
                    }
                    $event = json_decode($body, true);

                    if ($event['type'] == 'invoice.payment_succeeded') {

                        $invoice = $event['data']['object'];
                        $prev_entries = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findBy(array('stripeChargeId' => $invoice['charge']));
                        if (count($prev_entries) > 0) {
                            goto next_event;
                        }

                        $missings[] = $event['id'];
                        $user = null;
                        $prev_entries = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findBy(array('stripeSubscriptionId' => $invoice['subscription']));
                        if (count($prev_entries) == 0) {
                            try {
                                $customer = Customer::retrieve($invoice['customer']);
                                if (isset($customer) && isset($customer->email)) {
                                    $userManager = $container->get('fos_user.user_manager');
                                    $user = $userManager->findUserByUsernameOrEmail($customer->email);
                                } else {
                                    $missings_previous_records[] = $event['id'];
                                }
                            } catch (InvalidRequest $e) {
                                $missings_previous_records[] = $event['id'];
                            } catch (Excception $e) {
                                $missings_previous_records[] = $event['id'];
                            }
                        } else {
                            $user = $prev_entries[0]->getUser();
                        }

                        if (!isset($user)) { goto next_event; }

                        $utmMedium = $user->getUtmMedium();
                        $utmSource = $user->getUtmSource();
                        if (empty($utmSource)) $utmSource = '';
                        if (empty($utmMedium)) $utmMedium = '';

                        $userEntries = new SweepstakesUserEntries();
                        $userEntries->setUser($user);
                        $userEntries->setSweepstakes($sweepstakes);
                        $userEntries->setEntries(5);
                        $userEntries->setDescription('Membership Renewed');
                        $userEntries->setStripeSubscriptionId($invoice['subscription']);
                        $userEntries->setStripeInvoiceId($invoice['id']);
                        $userEntries->setStripeChargeId($invoice['charge']);
                        $userEntries->setReturning(true);
                        $userEntries->setCreatedAt(date_create(date("Y-m-d H:i:s", $event['created'])));
                        $userEntries->setVerifiedAt(date_create(date("Y-m-d H:i:s", $event['created'])));
                        $userEntries->setUtmMedium($utmMedium);
                        $userEntries->setUtmSource($utmSource);
                        $em->persist($userEntries);
                        $added ++;
                    }
                    next_event:
                    unset($event);
                    unset($body);
                }
            }

            $em->flush();


            $output->writeln(['missing: '.json_encode($missings)]);
            $output->writeln(['missing prev records: '.json_encode($missings_previous_records)]);
            $output->writeln(['added records: '.$added]);

            unset($matches);
            unset($log);

            next_date:
            $timestamp = $timestamp + 86400;
            if ($timestamp > $end_timestamp) { break; }
        }
        $output->writeln(['done']);
    }

    private function joinMembersToNewSweepstakes(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $sweepstakesActive = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array(), array('id' => 'DESC'));
        $lastSweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->find(4);
        $output->writeln([count($lastSweepstakes->getUsers())]);
        $output->writeln([$sweepstakesActive->getId()]);
        $updated = 0;
        foreach ($lastSweepstakes->getUsers() as $member){
            if(!$container->get('restomods.rawsqlhelper')->isUserInSweepstakes($member, $sweepstakesActive) && in_array('ROLE_SUBSCRIBER_USER', $member->getRoles())) {
                $sweepstakesActive->addUser($member);
                $updated ++;
            }
            $output->write(["."]);
        }
        $em->flush();
        $output->writeln(["Done:" .$updated."/".count($lastSweepstakes->getUsers())]);
    }

    private function migrateFunnelData(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $file = $container->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'funnel_sales.csv';
        $csv = file_get_contents($file);
        $results = array_map("str_getcsv", explode("\n", $csv));
        $output->writeln([count($results)]);

        $index = 0;
        foreach($results as $row){
            if ($index == 0) {
                $index ++;
                continue;
            }
            try
            {
                $funnel_purchase_id = $row[19];
                $stripe_charge_id = $row[20];
                $date = \DateTime::createFromFormat("Y-m-d H:i:s T",$row[21]);
                $stripe_subscription_id = $row[26];
                $em = $container->get('doctrine')->getManager();
                $results = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findBy(array('funnelPurchaseId' => $funnel_purchase_id));
                foreach ($results as $sweepstakes) {
                    if (abs($date->getTimestamp() - $sweepstakes->getCreatedAt()->getTimestamp()) > 120) {
                        $sweepstakes->setCreatedAt(date_create(date("Y-m-d H:i:s", $date->getTimestamp())));
                    }
                    $sweepstakes->setStripeChargeId($stripe_charge_id);
                    $sweepstakes->setStripeSubscriptionId($stripe_subscription_id);
                    $em->persist($sweepstakes);
                }
                unset($results);
                $em->flush();
            }
            catch (Exception $e)
            {
                $output->writeln([$e->getMessage(), $index]);
            }

            $index ++;
        }
    }

    private function importFunnelData(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $file = $container->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'funnel_sales.csv';
        $csv = file_get_contents($file);
        $results = array_map("str_getcsv", explode("\n", $csv));
        $output->writeln([count($results)]);

        $context = $container->get('router')->getContext();
        $context->setHost($container->getParameter('restomods.host'));
        $context->setScheme('http');
        $context->setBaseUrl($container->getParameter('restomods.base_url'));

        $errors = 0;
        $index = 0;
        foreach($results as $row){
            $output->writeln([$index.' ...']);
            try {
                if($row[25] == 'paid'){
                    $purchase_id = $row[19];
                    $first_name = $row[1];
                    $last_name = $row[2];
                    $email = strtolower($row[3]);
                    $phone = $row[10];
                    $address = $row[4];
                    $city = $row[6];
                    $state = $row[7];
                    $country = $row[8];
                    $zip = $row[9];
                    $stripe_charge_id = $row[20];
                    $subscription_id = $row[26];
                    $additional_info_str = $row[28];
                    $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s e',$row[21]);
                    $additional_info_str = preg_replace('/:([^":>,]*)>/i', '"${1}":', $additional_info_str);
                    $additional_info_str = preg_replace('/"([^"]*)">/i', '"${1}":', $additional_info_str);
                    $additional_info = json_decode($additional_info_str, true);
                    $utmSource = '';
                    $utmMedium = '';
                    if ($email) {
                        $em = $container->get('doctrine')->getManager();
                        $userManager = $container->get('fos_user.user_manager');
                        $user = $userManager->findUserByUsernameOrEmail($email);
                        $new = false;
                        if (!$user) {
                            $user = $userManager->findUserByUsername($email);
                            if (!$user) {
                                $user = $userManager->createUser();
                                $user->setPlainPassword(md5(time()));
                                $user->setFunnelImportedUser(true);
                                $new = true;
                            } else if (!$user->getFunnelImportedUser()){
                                $user->setFunnelImportedUser(false);
                            }
                        }elseif(!$user->getFunnelImportedUser()){
                            $user->setFunnelImportedUser(false);
                        }
                        $funnel_purchase_id = $user->getFunnelPurchaseId();
                        $sid = $user->getStripeSubscriptionId();
                        $user->setUsername($email);
                        $user->setEmail($email);
                        $user->setAddress($address);
                        $user->setCity($city);
                        $user->setState($state);
                        $user->setCountry($country);
                        $user->setZip($zip);

                        if ($additional_info) {
                            $utmSource = $additional_info['utm_source'];
                            $utmMedium = $additional_info['utm_medium'];
                            if (empty($utmSource)) $utmSource = '';
                            if (empty($utmMedium)) $utmMedium = '';

                            $user->setCFAffiliateId($additional_info['cf_affiliate_id']);
                            $user->setUtmSource($additional_info['utm_source']);
                            $user->setUtmMedium($additional_info['utm_medium']);
                            $user->setUtmCampaign($additional_info['utm_campaign']);
                            $user->setUtmTerm($additional_info['utm_term']);
                            $user->setUtmContent($additional_info['utm_content']);
                            if (isset($additional_info['cf_uvid']) && $additional_info['cf_uvid'] != "null") {
                                $user->setCfUvid($additional_info['cf_uvid']);
                            }
                            // $user->setTimezone($additional_info['time_zone']);
                        }

                        if($first_name){$user->setFirstname($first_name);}
                        if($last_name){$user->setLastname($last_name);}
                        if($phone){$user->setPhone($phone);}
                        if($subscription_id && (!$user->getStripeSubscriptionId() || strcmp($user->getStripeSubscriptionId(), $subscription_id) !== 0 )) {
                            $output->writeln([": Updating subscription >>> ".$subscription_id." >>> ".$user->getStripeSubscriptionId()."\n"]);
                            $user->removeRole('ROLE_FREE_USER');
                            $user->removeRole('ROLE_SUBSCRIBER_USER');
                            $user->addRole('ROLE_SUBSCRIBER_USER');
                            $subscription = $this->getSubscription($subscription_id);
                            $user->setStripeSubscriptionId($subscription_id);
                            $user->setStripeCustomerId($subscription['customer_id']);
                            $user->setEnabled(true);
                        }

                        $user->setFunnelPurchaseId($purchase_id);
                        $userManager->updateUser($user, true);

                        $user->generateReferrerCode();
                        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
                        if($funnel_purchase_id <> $purchase_id && $sweepstakes && $row[17]){
                            $products = explode(",", $row[17]);
                            foreach ($products as $product){
                                $entries = 0;
                                $product_name = trim($product);
                                if (stripos($product_name, 'Membership') !== false) {
                                    $entries = 5;
                                    if ($new || !$container->get('restomods.rawsqlhelper')->isUserInSweepstakes($user, $sweepstakes)) {
                                        $sweepstakes->addUser($user);
                                    }
                                } else if (stripos($product_name, 'Tshirt') !== false) {
                                    $entries = 24;
                                } else if (stripos($product_name, 'Hat') !== false) {
                                    $entries = 26;
                                } elseif(($new && stripos($product_name, 'Offer') !== false) || (!$new && stripos($product_name, 'Offer') !== false && !$sid)){
                                    $entries = 20;
                                } else {
                                    preg_match('!\d+!i', $product_name, $matches);
                                    if(count($matches)){
                                        $entries = intval($matches[0]);
                                    }
                                }
                                if($entries){
                                    $found = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findOneBy(array('sweepstakes' => $sweepstakes, 'user' => $user, 'entries' => $entries, 'funnelPurchaseId' => $purchase_id));
                                    if (!$found) {
                                        $userEntries = new SweepstakesUserEntries();
                                        $userEntries->setUser($user);
                                        $userEntries->setSweepstakes($sweepstakes);
                                        $userEntries->setEntries($entries);
                                        $userEntries->setDescription($product_name);
                                        $userEntries->setFunnelPurchaseId($purchase_id);
                                        $userEntries->setCreatedAt($dateTime);
                                        $userEntries->setStripeChargeId($stripe_charge_id);
                                        $userEntries->setStripeSubscriptionId($subscription_id);
                                        $userEntries->setUtmSource($utmSource);
                                        $userEntries->setUtmMedium($utmMedium);
                                        if ($subscription_id) {
                                            if (stripos($product_name, 'Membership') == false) {
                                                // Bump Offer
                                                $sweepstakesProduct = $em->getRepository('RestomodsListingBundle:SweepstakesProduct')->findOneBy(array('sweepstakes' => $sweepstakes, 'type' => 'bump_offer'));
                                                $userEntries->setSweepstakesProduct($sweepstakesProduct);
                                            }
                                        }
                                        if ($stripe_charge_id) {
                                            $sweepstakesProducts = $em->getRepository('RestomodsListingBundle:SweepstakesProduct')->findBy(array('sweepstakes' => $sweepstakes, 'entries' => $entries));
                                            $sweepstakesProduct = null;
                                            foreach ($sweepstakesProducts as $product) {
                                                if ($product->getType() != 'bump_offer') {
                                                    $sweepstakesProduct = $product;
                                                    break;
                                                }
                                            }
                                            $userEntries->setSweepstakesProduct($sweepstakesProduct);
                                        }
                                        $em->persist($userEntries);

                                        $output->writeln([": Adding product >>> ".$product_name." >>> Purchase(".$purchase_id.")\n"]);
                                    } else {
                                        $output->writeln([": Skipping product(Already exists) >>> ".$product_name." >>> Purchase(".$purchase_id.")\n"]);
                                    }
                                } else {
                                      $output->writeln([": Skipping product(No entries) >>> ".$product_name." >>> Purchase(".$purchase_id.")\n"]);
                                }
                            }
                        } else {
                            $output->writeln([": Skipping purchase >>> Purchase(".$purchase_id.") >>> Funnel Purchase(".$funnel_purchase_id.")\n"]);
                        }

                        if($new){
                            $tokenGenerator = $container->get('fos_user.util.token_generator');
                            $user->setConfirmationToken($tokenGenerator->generateToken());
                            $user->setPasswordRequestedAt(new \DateTime());

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

                            $user->setCreatedAt($dateTime);
                            $user->setUpdatedAt($dateTime);
                            $userManager->updateUser($user, true);
                        }
                        $em->flush();
                    }
                }


            }catch ( \Exception $e ) {
                $output->writeln([": Error occured >>> Purchase(".$purchase_id.") >>> ".$e."\n"]);
                $errors ++;
            }
            $index ++;
        }
    }

    private function getSubscription($sid){
        try {
            $container = $this->getContainer();
            Stripe::setApiKey($container->getParameter('restomods.stripe.secret_key'));
            $subscription = Subscription::retrieve($sid);
            return ['customer_id' => $subscription ? $subscription->customer : null, 'start_date' => $subscription ? date_create(date("Y-m-d H:i:s", $subscription->start)) : null];

        }catch ( \Exception $e ) {
            return ['customer_id' => null, 'start_date' => null];
        }
    }

    private function fillFromFunnelSaleYearData(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(["start of fillFromFunnelSaleYearData >>>>>>>>>>>>>>>>>>>>"]);
        $container = $this->getContainer();
        $file = $container->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'funnel_sales_year.csv';
        $csv = file_get_contents($file);
        $results = array_map("str_getcsv", explode("\n", $csv));
        $output->writeln([count($results)]);
        $em = $container->get('doctrine')->getManager();

        $index = 0;
        $funnel_ids = array();
        $last_date = null;

        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        foreach($results as $row){
            $output->write(["."]);
            if ($index == 0) {
                $index ++;
                continue;
            }
            try
            {

                if (count($row) < 26) {
                    continue;
                }

                $funnel_purchase_id = $row[19];
                $stripe_charge_id = $row[20];
                $stripe_subscription_id = $row[26];
                $funnel_ids[] = $funnel_purchase_id;
                $date = \DateTime::createFromFormat("Y-m-d H:i:s T",$row[21]);
                if ($last_date == null || $last_date->getTimestamp() < $date->getTimestamp()) {
                    $last_date = $date;
                }

                // $sweepstakes_array = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findBy(array('funnelPurchaseId' => $funnel_purchase_id));
                // $dbchanges = false;
                // foreach ($sweepstakes_array as $entries) {
                //     $hasChanges = false;
                //     if (abs($date->getTimestamp() - $entries->getCreatedAt()->getTimestamp()) > 120) {
                //         $hasChanges = true;
                //         $entries->setCreatedAt(date_create(date("Y-m-d H:i:s", $date->getTimestamp())));
                //     }
                //     if ($entries->getStripeChargeId() != $stripe_charge_id) {
                //         $hasChanges = true;
                //         $entries->setStripeChargeId($stripe_charge_id);
                //     }
                //     if ($entries->getStripeSubscriptionId() != $stripe_subscription_id) {
                //         $hasChanges = true;
                //         $entries->setStripeSubscriptionId($stripe_subscription_id);
                //     }
                //     if ($hasChanges) {
                //         $dbchanges = true;
                //         $em->persist($entries);
                //     }
                // }
                // unset($sweepstakes_array);
                // if ($dbchanges && $index % 50 == 0) {
                //     $em->flush();
                // }
            }
            catch (Exception $e)
            {
                $output->writeln([$e->getMessage(), $index]);
            }

            $index ++;
        }
        $output->writeln(['migrated date']);
        $output->writeln(['missing ids:',json_encode($funnel_ids)]);

        $query = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->createQueryBuilder('e')
                    ->where('e.funnelPurchaseId IS NOT NULL')
                    ->andWhere('e.sweepstakes = :sweepstakes')
                    ->andWhere("e.funnelPurchaseId NOT IN(:funnelIds)")
                    ->andWhere("e.createdAt < :date")
                    ->andWhere("e.active = true")
                    ->setParameter('sweepstakes', $sweepstakes)
                    ->setParameter('funnelIds', $funnel_ids)
                    ->setParameter('date', date_create(date("Y-m-d H:i:s", $last_date->getTimestamp())))
                    ->getQuery();

        $entries_array = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        $output->writeln([count($entries_array)]);
        foreach($entries_array as $entry) {
            $entry->setActive(false);
            $em->persist($entry);
        }
        $em->flush();
        $output->writeln(['inactivated entries']);
        $output->writeln(["<<<<<<<<<<<<<<<<< end of fillFromFunnelSaleYearData"]);
    }

    private function fillFromStripeLog(InputInterface $input, OutputInterface $output) {
        $output->writeln(["start of fillFromStripeLog >>>>>>>>>>>>>>>>>>>>"]);
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));

        $query = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->createQueryBuilder('e')
                    ->where('e.sweepstakes = :sweepstakes')
                    ->andWhere("e.description LIKE 'Membership Renewed'")
                    ->andWhere("e.stripeChargeId = ''")
                    ->andWhere("e.orderId = ''")
                    ->andWhere("e.active = true")
                    ->setParameter('sweepstakes', $sweepstakes)
                    ->getQuery();
        $entries_array = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        $index = 0;
        Stripe::setApiKey($container->getParameter('restomods.stripe.secret_key'));

        $numberOfChanges = 0;

        foreach($entries_array as $entries) {
            $timestamp = $entries->getCreatedAt()->getTimestamp();
            $log_path = $container->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'web/logs/stripe_'.date('Y-m-d', $timestamp).'.txt';
            $log = file_get_contents($log_path);if (!$log) { continue; }
            $keyword = null;

            $output->write(".");
            // $output->writeln([$index."/".count($entries_array).':'.$entries->getId().':'.$timestamp]);
            if (strpos($log, date('Y-m-d H:i:s', $timestamp).": Added > ") !== false) {
                $keyword = date('Y-m-d H:i:s', $timestamp).": Added > ";
            } else if (strpos($log, date('Y-m-d H:i:s', $timestamp + 1).": Added > ") !== false) {
                $keyword = date('Y-m-d H:i:s', $timestamp + 1).": Added > ";
            } else if (strpos($log, date('Y-m-d H:i:s', $timestamp - 1).": Added > ") !== false) {
                $keyword = date('Y-m-d H:i:s', $timestamp - 1).": Added > ";
            }

            if ($keyword != null) {
                // $output->writeln([$keyword]);
                $sub_log = substr($log, strpos($log, $keyword) + strlen($keyword));
                preg_match('/\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}:\s\w+\s>/', $sub_log, $matches, PREG_OFFSET_CAPTURE);
                $json_str = $sub_log;
                if ($matches && count($matches) == 1) {
                    $json_str = substr($sub_log, 0, $matches[0][1]);
                }
                $json = json_decode($json_str);
                $invoice = $json->data->object;
                if ($invoice->object == "invoice") {
                    $entries->setStripeInvoiceId($invoice->id);
                    $entries->setStripeChargeId($invoice->charge);
                    $entries->setStripeSubscriptionId($invoice->subscription);
                    if ($invoice->paid && $invoice->charge != null) {
                        // $charge = Charge::retrieve($invoice->charge);
                        // if ($charge->refunded) {
                        //     if ($charge->refunds->total_count > 0) {
                        //         $entries->setStripeRefundId($charge->refunds->data[0]->id);
                        //     }
                        //     $entries->setActive(false);
                        // } else if ($charge->status == 'failed') {
                        //     $entries->setActive(false);
                        // }
                    } else {
                        $entries->setActive(false);
                    }
                    $em->persist($entries);
                    $numberOfChanges ++;
                    if ($numberOfChanges > 500) {
                        $em->flush();
                        $numberOfChanges = 0;
                    }
                    // $this->logFixedEntries($entries, null, $output);
                } else {
                    $output->writeln(['[It is not invoice] (entries: )'.$entries->getId().') (date: '.$entries->getCreatedAt()->getTimestamp().')']);
                }

                unset($invoice);
                unset($json);
                unset($json_str);
                unset($sub_log);
            } else {
                $output->writeln(['[Not found] (entries: )'. $entries->getId().') (date: '.$entries->getCreatedAt()->getTimestamp().')']);
            }

            $index ++;

            unset($log);
        }
        $em->flush();
        unset($entries_array);
        $output->writeln(["<<<<<<<<<<<<<<<<< end of fillFromStripeLog"]);
    }

    private function fillMissingChargeIdsButSubscriptionId(InputInterface $input, OutputInterface $output) {
        $output->writeln(["start of fillMissingChargeIdsButSubscriptionId >>>>>>>>>>>>>>>>>>>>"]);
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        Stripe::setApiKey($container->getParameter('restomods.stripe.secret_key'));

        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        $startAt = $sweepstakes->getStartDate()->getTimestamp();
        $index = 0;

        $query = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->createQueryBuilder('e')
                    ->where('e.sweepstakes = :sweepstakes')
                    ->andWhere("e.stripeSubscriptionId != ''")
                    ->andWhere("e.stripeChargeId = ''")
                    ->andWhere("e.orderId = ''")
                    ->andWhere("e.active = true")
                    ->andWhere("e.verifiedAt < :date")
                    ->setParameter('sweepstakes', $sweepstakes)
                    ->setParameter('date', date_create(date("Y-m-d H:i:s", 1547652897)))
                    ->getQuery();
        $entries_array = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        $index = 0;
        foreach($entries_array as $entries) {

            $output->writeln([$index."/".count($entries_array)]);
            $entries_timestamp = $entries->getCreatedAt()->getTimestamp();
            $entries_subscription_id = $entries->getStripeSubscriptionId();
            $entries_invoice_id = $entries->getStripeInvoiceId();

            $invoice_found = null;
            if ($entries_invoice_id == '') {
                try {
                    $invoices = Invoice::all( array( 'subscription' => $entries_subscription_id, 'date' => array('gte' => $startAt) ) )->data;

                    $elapsed_cur = 86400;
                    foreach($invoices as $invoice) {
                        $elapsed = abs($invoice->date - $entries_timestamp);
                        if ($invoice->webhooks_delivered_at) {
                            $elapsed = min($elapsed, abs($invoice->webhooks_delivered_at - $entries_timestamp));
                        }

                        if ($elapsed > 86400 / 2) { continue; }
                        if ($elapsed % 3600 > 120) { continue; }

                        if ($elapsed < $elapsed_cur) {
                            $elapsed_cur = $elapsed;
                            $invoice_found = $invoice;
                        }
                    }
                } catch (Exception $e) {
                    $output->writeln([$e->getMessage()]);
                }

            } else {
                try {
                    $invoice_found = Invoice::retrieve($entries_invoice_id);
                } catch (Exception $e) {
                    $output->writeln([$e->getMessage()]);
                }

            }

            if ($invoice_found != null) {
                $entries->setStripeInvoiceId($invoice_found->id);
                $entries->setStripeChargeId($invoice_found->charge);
                if ($invoice_found->charge == null && $invoice_found->closed) {
                    $entries->setActive(false);
                }
                $em->persist($entries);
                $em->flush();
                $this->logFixedEntries($entries, null, $output);
            } else {
                $this->logMismatchEntries($entries, null, 'Invoice not found', $output);
            }

            unset($invoices);
            $index ++;
        }

        $output->writeln(["<<<<<<<<<<<<<<<<< end of fillMissingChargeIdsButSubscriptionId"]);
    }

    private function verifyAgainstStripe(InputInterface $input, OutputInterface $output) {
        $output->writeln(["start of verifyAgainstStripe >>>>>>>>>>>>>>>>>>>>"]);
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        Stripe::setApiKey($container->getParameter('restomods.stripe.secret_key'));

        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        $startAt = $sweepstakes->getStartDate()->getTimestamp();
        $index = 0;

        $query = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->createQueryBuilder('e')
                    ->where('e.sweepstakes = :sweepstakes')
                    ->andWhere("e.stripeChargeId != ''")
                    ->andWhere("e.stripeDisputeId = ''")
                    ->andWhere("e.orderId = ''")
                    ->andWhere("e.active = true")
                    ->andWhere("e.verifiedAt < :date")
                    ->setParameter('sweepstakes', $sweepstakes)
                    ->setParameter('date', date_create(date("Y-m-d H:i:s", 1547652897)))
                    ->getQuery();
        $entries_array = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        $index = 0;
        $dbChanges = 0;

        $output->writeln([count($entries_array)]);
        foreach($entries_array as $entries) {
            $entries_charge_id = $entries->getStripeChargeId();
            $output->write(["."]);
            try {
                $charge = Charge::retrieve($entries_charge_id);
                if ($charge != null) {
                    if ($charge->refunded) {
                        if ($charge->refunds->total_count > 0) {
                            $entries->setStripeRefundId($charge->refunds->data[0]->id);
                        }
                        $entries->setVerifiedAt(date_create(date("Y-m-d H:i:s")));
                        $entries->setActive(false);
                        // $this->logFixedEntries($entries, null, $output);
                    } else if ($charge->status == 'failed') {
                        $entries->setActive(false);
                        $entries->setVerifiedAt(date_create(date("Y-m-d H:i:s")));
                        // $this->logFixedEntries($entries, null, $output);
                    } else if ($charge->dispute != null) {
                        $entries->setStripeDisputeId($charge->dispute);
                    } else {
                        $entries->setVerifiedAt(date_create(date("Y-m-d H:i:s")));
                    }
                    $dbChanges ++;
                    $em->persist($entries);

                } else {
                    $this->logMismatchEntries($entries, null, 'Invalid charge id for '. $entries->getId(), $output);
                }
            } catch (InvalidRequest $e) {
                // Code to do something with the $e exception object when an error occurs
                $output->writeln(["exception: ".$e->getMessage()]);
            } catch (Exception $e) {
                $output->writeln(["exception: ".$e->getMessage()]);
            }

            if ($dbChanges > 100) {
                $dbChanges = 0;
                $em->flush();
            }
        }
        $em->flush();

        $output->writeln(["<<<<<<<<<<<<<<<<< end of verifyAgainstStripe"]);
    }

    private function checkDisputes(InputInterface $input, OutputInterface $output) {
        $output->writeln(["start of checkDisputes >>>>>>>>>>>>>>>>>>>>"]);
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        Stripe::setApiKey($container->getParameter('restomods.stripe.secret_key'));

        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        $startAt = $sweepstakes->getStartDate()->getTimestamp();
        $index = 0;

        $query = $em->getRepository( 'RestomodsListingBundle:SweepstakesUserEntries' )->createQueryBuilder('e')
                    ->where('e.sweepstakes = :sweepstakes')
                    ->andWhere("e.stripeDisputeId != ''")
                    ->setParameter('sweepstakes', $sweepstakes)
                    ->getQuery();
        $entries_array = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        $index = 0;
        $dbChanges = 0;

        $output->writeln([count($entries_array)]);
        foreach($entries_array as $entries) {
            $entries_dispute_id = $entries->getStripeDisputeId();
            try {
                $dispute = Dispute::retrieve($entries_dispute_id);
                $output->writeln([$entries->getId().":Dispute(".$entries_dispute_id."), "."reason(".$dispute->reason."), status(".$dispute->status.")"]);
            } catch (InvalidRequest $e) {
                // Code to do something with the $e exception object when an error occurs
                $output->writeln(["exception: ".$e->getMessage()]);
            } catch (Exception $e) {
                $output->writeln(["exception: ".$e->getMessage()]);
            }
        }

        $output->writeln(["<<<<<<<<<<<<<<<<< end of checkDisputes"]);
    }

    private function fillMissingChargeIds(InputInterface $input, OutputInterface $output) {
        $output->writeln(["start of fillMissingChargeIds >>>>>>>>>>>>>>>>>>>>"]);
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        Stripe::setApiKey($container->getParameter('restomods.stripe.secret_key'));

        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        $startAt = $sweepstakes->getStartDate()->getTimestamp();
        foreach($sweepstakes->getUsers() as $user) {
            if ( $user->getStripeCustomerId() != null ) {
                $charges = Charge::all( array( 'customer' => $user->getStripeCustomerId(), 'created' => array('gte' => $startAt) ) )->data;
                $invoices = Invoice::all( array( 'customer' => $user->getStripeCustomerId(), 'date' => array('gte' => $startAt) ) )->data;
                $entries_array = $em->getRepository('RestomodsListingBundle:SweepstakesUserEntries')->findBy(array('user' => $user, 'sweepstakes' => $sweepstakes));
                foreach($entries_array as $entries) {
                    if ($entries->getStripeChargeId() == '' && $entries->getOrderId() == '' && $entries->getActive()) {

                        $timestamp = $entries->getCreatedAt()->getTimestamp();
                        if ($entries->getDescription() == 'join' && $timestamp > self::TIMESTAMP_4_27 && $timestamp < self::TIMESTAMP_4_27_2) {
                            $this->logMismatchEntries($entries, $user, 'skipping', $output);
                            continue;
                        }

                        // 1. we have stripe invoice id. easy to find :)
                        if ($entries->getStripeInvoiceId() != '') {
                            // find invoice, set
                            $invoice = null;
                            $charge = null;
                            $invoice_id = $entries->getStripeInvoiceId();
                            foreach ($invoices as $in) {
                                if ($in->id == $invoice_id) {
                                    $invoice = $in;
                                    break;
                                }
                            }

                            if ($invoice == null) {
                                $invoice = Invoice::retrieve($invoice_id);
                                if ($invoice != null && $invoice->charge != null)
                                    $charge = Charge::retrieve($invoice->charge);
                            }

                            if ($invoice == null) {
                                $this->logMismatchEntries($entries, $user, 'Missing Invoice: '. $invoice_id, $output);
                            } else {
                                if ($invoice->paid) {
                                    if ($charge == null) {
                                        foreach ($charges as $ch) {
                                            if ($ch->invoice == $invoice->id) {
                                                $charge = $ch;
                                                break;
                                            }
                                        }
                                    }

                                    if ($charge != null) {
                                        $entries->setStripeChargeId($charge->id);
                                        if ($charge->refunded) {
                                            if ($charge->refunds->total_count > 0) {
                                                $entries->setStripeRefundId($charge->refunds->data[0]->id);
                                            }
                                            $entries->setActive(false);
                                        } else if ($charge->status == 'failed') {
                                            $entries->setActive(false);
                                        }
                                        $em->persist($entries);
                                        $this->logFixedEntries($entries, $user, $output);
                                    } else {
                                        $this->logMismatchEntries($entries, $user, 'Missing Charge for invoice: '. $invoice_id, $output);
                                    }
                                } else if ($invoice->closed) {
                                    $entries->setActive(false);
                                    $em->persist($entries);
                                    $this->logFixedEntries($entries, $user, $output);
                                } else {
                                    $this->logMismatchEntries($entries, $user, 'Pending invoice: '. $invoice_id, $output);
                                }
                            }
                            continue;
                        }

                        // 2. we don't have stripe invoice id, find charge first.
                        $charges_found = $this->getMatchedChargesForSweepstaksEntries($charges, $invoices, $entries, $user, $output);
                        if (count($charges_found) == 1) {
                            // if find appropriate, fix entries
                            $this->fixEntriesWithCharge($entries, $charges_found[0], $em, $user, $output);
                            continue;
                        }

                        // 3. did not find charge, tries to find invoice
                        $desc = $entries->getDescription();
                        if (stripos($desc, 'join') === false && stripos($desc, 'membership') === false && stripos($desc, 'vip') === false) {
                            // no possibility, skip this entries
                            continue;
                        }

                        $invoice = null;
                        $charge = null;
                        $invoices_found = $this->getMatchedInvoicesForSweepstaksEntries($invoices, $entries, $user);
                        if (count($invoices_found) == 1) {
                            $invoice = $invoices_found[0];
                        } else if (count($invoices_found) == 0) {
                            $invoice = $this->getMatchedInvoicesForSweepstaksEntriesWithoutCustomerId($entries_array, $entries, $user, $sweepstakes, $output);
                        }

                        if ($invoice != null)  {
                            // if invoice found, fix entries with that
                            $this->fixEntriesWithInvoice($entries, $invoice, $charges, $em, $user, $output);
                        } else {
                            $this->logMismatchEntries($entries, $user, 'Invoices count:'.count($invoices_found), $output);
                        }
                    }
                } //foreach $entries_array
                $em->flush();

                unset($charges);
                unset($entries);
                unset($invoices);
            }
        }
        $output->writeln(["<<<<<<<<<<<<<<<<< end of fillMissingChargeIds"]);
    }

    private function getMatchedInvoicesForSweepstaksEntriesWithoutCustomerId($entries_array, $entries, $user, $sweepstakes, $output) {
        $stripe_subscription_id = $user->getStripeSubscriptionId();
        if ($stripe_subscription_id == null) {

            $timestamp = $entries->getCreatedAt()->getTimestamp();
            $candidates = array();
            $last_t = 0;
            foreach ($entries_array as $en) {
                $t = $en->getCreatedAt()->getTimestamp();
                if ((stripos($en->getDescription(), 'Membership') !== false || stripos($en->getDescription(), 'join') !== false) && $en->getStripeSubscriptionId() != '' && date("d", $timestamp) == date("d", $t)) {
                    if ($stripe_subscription_id != null && abs($last_t - $t) > 120) {
                        return null;
                    }
                    $stripe_subscription_id = $en->getStripeSubscriptionId();
                    $last_t = $t;
                }
            }
        }

        if ($stripe_subscription_id != null) {
            $startAt = $sweepstakes->getStartDate()->getTimestamp();
            $invoices = Invoice::all( array( 'subscription' => $stripe_subscription_id, 'date' => array('gte' => $startAt) ) )->data;
            $invoices_found = $this->getMatchedInvoicesForSweepstaksEntries($invoices, $entries, $user);

            if (count($invoices_found) == 0) {
                $invoices_found = $this->getMatchedInvoicesForSweepstaksEntries($invoices, $entries, $user, null, 3600 * 5);
            }
            if (count($invoices_found) == 1) {
                return $invoices_found[0];
            }
        }
        return null;
    }

    private function getMatchedChargesForSweepstaksEntries($charges, $invoices, $entries, $user, $output) {
        $entries_timestamp = $entries->getCreatedAt()->getTimestamp();
        $near_charges = array();
        $entries_desc = $entries->getDescription();
        foreach ($charges as $charge) {
            $elapsed = abs($charge->created - $entries_timestamp);
            if ($elapsed > 86400 / 2) { continue; }
            if ($elapsed % 3600 > 120) { continue; }

            if (stripos($entries_desc, "Gold") !== false) {
                if ($charge->amount == 2200) $near_charges[] = $charge;
            } else if (stripos($entries_desc, "Platinum") !== false) {
                if ($charge->amount == 4800) $near_charges[] = $charge;
            } else if (stripos($entries_desc, "Silver") !== false) {
                if ($charge->amount == 1100) $near_charges[] = $charge;
            } else if (stripos($entries_desc, "Vip") !== false) {
                if ($entries->getStripeSubscriptionId() != '') {
                    $invoices = $this->getMatchedInvoicesForSweepstaksEntries($invoices, $entries, $user);
                    if (count($invoices) == 1 && $invoices[0]->charge == $charge->id) {
                        $near_charges[] = $charge;
                    }
                } else if ($charge->amount == 1895 || $charge->amount == 1900) {
                    $near_charges[] = $charge;
                } else if ($charge->amount == 1000 || $charge->amount == 300) {
                    $near_charges[] = $charge;
                }
            }
        }

        if (count($near_charges) != 1) {
            $this->logMismatchEntries($entries, $user, 'Charges  count:'.count($near_charges), $output);
        }
        return $near_charges;
    }

    private function getMatchedInvoicesForSweepstaksEntries($invoices, $entries, $user, $output = null, $elapse = 120) {
        $entries_timestamp = $entries->getCreatedAt()->getTimestamp();
        $near_invoices = array();
        $entries_desc = $entries->getDescription();
        $entries_subscription_id = $entries->getStripeSubscriptionId();
        foreach ($invoices as $invoice) {
            $elapsed = abs($invoice->date - $entries_timestamp);
            if ($invoice->webhooks_delivered_at) {
                $elapsed = min($elapsed, abs($invoice->webhooks_delivered_at - $entries_timestamp));
            }

            if ($elapsed > 86400 / 2) { continue; }
            if ($elapse > 3600) {
                if ($elapsed > $elapse) {continue;}
            } else if ($elapsed % 3600 > $elapse) { continue; }


            if ($entries_subscription_id != '' && $invoice->subscription != $entries_subscription_id) {
                continue;
            }

            $near_invoices[] = $invoice;
        }

        if (count($near_invoices) == 0 && $entries_subscription_id != '') {

        }

        if (count($near_invoices) != 1) {
            if ($output != null)
                $this->logMismatchEntries($entries, $user, 'Invoices count:'.count($near_invoices), $output);
            // $output->writeln([json_encode($invoices)]);
            // $output->writeln([$entries_timestamp]);
        }

        return $near_invoices;
    }

    private function fixEntriesWithCharge($entries, $charge, $em, $user, $output) {
        $charge = $charges_found[0];
        $entries->setStripeChargeId($charge->id);
        $entries->setStripeInvoiceId($charge->invoice);
        if ($charge->refunded) {
            if ($charge->refunds->total_count > 0) {
                $entries->setStripeRefundId($charge->refunds->data[0]->id);
            }
            $entries->setActive(false);
        } else if ($charge->status == 'failed') {
            $entries->setActive(false);
        }
        $this->logFixedEntries($entries, $user, $output);
        $em->persist($entries);
    }

    private function fixEntriesWithInvoice($entries, $invoice, $charges_of_customer, $em, $user, $output) {
        $entries->setStripeInvoiceId($invoice->id);
        $entries->setStripeSubscriptionId($invoice->subscription);
        if ($invoice->paid) {
            $charge = null;
            foreach ($charges_of_customer as $ch) {
                if ($ch->invoice == $invoice->id) {
                    $charge = $ch;
                    break;
                }
            }

            if ($charge == null && $invoice->charge != null) {
                $charge = Charge::retrieve($invoice->charge);
            }

            if ($charge != null) {
                if ($charge->refunded) {
                    if ($charge->refunds->total_count > 0) {
                        $entries->setStripeRefundId($charge->refunds->data[0]->id);
                    }
                    $entries->setActive(false);
                } else if ($charge->status == 'failed') {
                    $entries->setActive(false);
                }
                $this->logFixedEntries($entries, $user, $output);
            } else {
                $this->logMismatchEntries($entries, $user, 'Filled, but missing Charge for invoice: '. $invoice_id, $output);
            }
        } else if ($invoice->closed) {
            $entries->setActive(false);
            $this->logFixedEntries($entries, $user, $output);
        } else {
            $this->logMismatchEntries($entries, $user, 'Filled, but pending invoice: '. $invoice_id, $output);
        }
        $em->persist($entries);
    }

    private function logMismatchEntries($entries, $user, $mismatch, $output) {
        if ($user != null) {
            $output->writeln(["[Mismatch(".$mismatch.")] user: ".$user->getEmail()." entries: ".$entries->getId()." desc: ".$entries->getDescription()]);
        } else {
            $output->writeln(["[Mismatch(".$mismatch.")] entries: ".$entries->getId()." desc: ".$entries->getDescription()]);
        }
    }

    private function logFixedEntries($entries, $user, $output) {
        if ($user != null) {
            $output->writeln(["[Fixed] user: ".$user->getEmail()." entries: ".$entries->getId()." desc: ".$entries->getDescription()]);
        } else {
            $output->writeln(["[Fixed] entries: ".$entries->getId()." desc: ".$entries->getDescription()]);
        }

    }
}

<?php

namespace Restomods\ListingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Restomods\ListingBundle\Entity\SweepstakesUserEntries;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Dispute;
use Stripe\Invoice;
use Stripe\Subscription;
use Stripe\Customer;
use Stripe\Error\InvalidRequest;
class StripeMigrationCommand extends ContainerAwareCommand
{


    protected function configure()
    {
        $this
            ->setName('restomods:stripe:migrate')
            ->addOption( 'type',
				null,
				InputOption::VALUE_REQUIRED,
				'Migration command type',
				'type' )
            ->setDescription('Migrate sweepstakes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption( 'type' );
        if ($type == 'subscription') {
            $this->migrateSubscription($input, $output);
        } else if ($type == 'compare') {
            $this->compareSubscription($input, $output);
        }
    }

    private function migrateSubscription(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $userManager = $container->get('fos_user.user_manager');

        $query = $em->getRepository( 'ApplicationSonataUserBundle:User' )->createQueryBuilder('u')
                    ->where("u.roles like '%ROLE_SUBSCRIBER_USER%'")
                    ->andWhere('u.limeLightCustomerId is null')
                    ->andWhere('u.stripeCustomerId is not null')
                    ->andWhere("u.subscribedAt < '2018-07-16 00:00:00'")
                    ->getQuery();
        $users =  $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        $output->writeln([count($users)]);
        Stripe::setApiKey($container->getParameter('restomods.stripe.secret_key'));

        $inactive_members_log_path = $container->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'web/logs/inactive_members.txt';
        $inactive_members = '';
        if (file_exists($inactive_members_log_path)) {
            $inactive_members = file_get_contents($inactive_members_log_path);
        }

        foreach($users as $user) {
            $output->write(['.']);
            $email = $user->getEmail();

            if (stripos($inactive_members, $email) !== false) {
                continue;
            }

            $customers = Customer::all(array("email" => $email));

            $hasActiveSubscription = false;
            $subscribed_at = 0;
            $customer_id = '';
            $subscription_id = '';
            foreach($customers->data as $customer) {
                foreach($customer->subscriptions->data as $subscription) {
                    if ($subscription->status == 'active') {
                        $hasActiveSubscription = true;
                        if ($subscribed_at < $subscription->current_period_start) {
                            $subscribed_at = $subscription->current_period_start;
                            $subscription_id = $subscription->id;
                            $customer_id = $subscription->customer;
                        }
                    }
                }
            }

            if ($hasActiveSubscription) {
                if ($subscribed_at > 0) {
                    $date = date_create(date("Y-m-d H:i:s", $subscribed_at));
                    $user->setSubscribedAt($date);
                    $user->setStripeSubscriptionId($subscription_id);
                    $user->setStripeCustomerId($customer_id);
                    $userManager->updateUser($user, true);
                }
            } else {
                file_put_contents($inactive_members_log_path,$email."\n", FILE_APPEND);
            }
        }
    }

    private function compareSubscription(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();
        $llfp = fopen('remaining_ll.csv', 'w');
        $stfp = fopen('remaining_st.csv', 'w');

        $file = $container->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'customers_ll.csv';
        $csv = file_get_contents($file);
        $customers_ll = array_map("str_getcsv", explode("\n", $csv));

        $file = $container->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'customers_stripe.csv';
        $csv = file_get_contents($file);
        $customers_stripe = array_map("str_getcsv", explode("\n", $csv));

        $output->writeln(['LimeLight customers: '. count($customers_ll)]);
        $output->writeln(['Stripe customers: '. count($customers_stripe)]);

        $i = 1;
        while ($i < count($customers_ll)) {
            $customer_ll = $customers_ll[$i];
            if (count($customer_ll) < 4) {
                break;
            }

            $email_ll = $customer_ll[4];

            $found_in_stripe = false;
            $j = 1;
            while ($j < count($customers_stripe)) {
                $customer_stripe = $customers_stripe[$j];
                if (count($customer_stripe) < 9) {
                    break;
                }
                $email_stripe = $customer_stripe[9];
                if (strcasecmp($email_ll, $email_stripe) === 0) {
                    array_splice($customers_stripe, $j, 1);
                    $found_in_stripe = true;
                    continue;
                }
                $j ++;
            }

            if ($found_in_stripe) {
                array_splice($customers_ll, $i, 1);
                continue;
            }
            $i ++;
        }

        $output->writeln(['Missing LimeLight customers: '. count($customers_ll)]);
        $output->writeln(['Missing Stripe customers: '. count($customers_stripe)]);


        foreach ($customers_ll as $row) {
            fputcsv($llfp, $row);
        }

        foreach ($customers_stripe as $row) {
            fputcsv($stfp, $row);
        }
    }
}

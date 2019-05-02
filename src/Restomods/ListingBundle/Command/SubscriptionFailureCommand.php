<?php

namespace Restomods\ListingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DateTime;
use DateInterval;
class SubscriptionFailureCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('restomods:subscription:failure')
            ->setDescription('Handle subscription failures')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $is_debug = false;
        if (in_array($container->get('kernel')->getEnvironment(), array('dev', 'test'), true)) {
            $is_debug = true;
        }

        $em = $container->get('doctrine.orm.entity_manager');
        $failures = $em->getRepository('RestomodsListingBundle:SubscriptionFailure')->getTodayFailures();
        $limelight = $container->get('restomods.limelight');
        foreach($failures as $failure) {
            $order_id = $failure->getOrderId();
            $response = $limelight->reprocessOrder($order_id);
            if (!$response['success']) {
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
                    $limelight = $container->get( 'restomods.limelight' );
                    $limelight->cancelSubcriptionWithOrderId( $order_id );
                    $em->remove($failure);
                    $user = $failure->getUser();
                    if ($user) {
                        $userManager = $container->get('fos_user.user_manager');
                        $user->setSubscriptionOrderId( null );
                        $user->removeRole('ROLE_FREE_USER')->removeRole('ROLE_SUBSCRIBER_USER')->addRole('ROLE_FREE_USER');
                        $userManager->updateUser($user, true);
                    }
                }
                $em->flush();
            }
            $output->writeln([json_encode($response)]);
        }
    }
}

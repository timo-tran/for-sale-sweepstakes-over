<?php

namespace Restomods\ListingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Restomods\ListingBundle\Helper\SendgridHelper;
class AbandonCartEmailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('restomods:abandon:cart:email')
            ->setDescription('Send Email to abandon cart users.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $context = $container->get('router')->getContext();
        $context->setHost($container->getParameter('restomods.host'));
        $context->setScheme('http');
        $context->setBaseUrl($container->getParameter('restomods.base_url'));
        $em = $container->get('doctrine.orm.entity_manager');

        /* Send Email to abandon cart users */
        $sweepstakes = $em->getRepository('RestomodsListingBundle:Sweepstakes')->findOneBy(array('active' => 1));
        $imagineCacheManager = $container->get('liip_imagine.cache.manager');
        if($sweepstakes){
            $prospects = $em->getRepository('RestomodsListingBundle:Sweepstakes')->getProspectUsers();
            $sendgridReceipts = array();
            $output->writeln('Total: '.count($prospects). ' users found');
            if(count($prospects)) {
                $carName = $sweepstakes->getCarName();
                $featuredImage1 = $sweepstakes->getFeaturedImage1();
                $carImage = $imagineCacheManager->getBrowserPath($featuredImage1, 'medium');
                $carImage = str_replace('http://', 'https://', $carImage);
                $expireDate = $sweepstakes->getEndDate();
                date_sub($expireDate,date_interval_create_from_date_string("5 hours"));
                $expireDateStr = date_format($expireDate, 'F j, Y');
                $template = $container->get('twig')->render('RestomodsListingBundle:Emails:abandon_cart2.html.twig', array('sweepstakes' => $sweepstakes->getName()));
                $templatePlain = $container->get('twig')->render('RestomodsListingBundle:Emails:abandon_cart2.txt.twig', array('sweepstakes' => $sweepstakes->getName()));
                foreach ($prospects as $prospect) {
                    $subject = "{$prospect->getFirstname()} you are NOT entered to win the ".$sweepstakes->getName()." - YET";
                    $tokenUrl = str_replace('http:', $container->getParameter('restomods.url.scheme'), $container->get('router')->generate('restomods_sweepstakes_token_login', array('token' => $prospect->getConfirmationToken()), true));
                    $sweepstakesUrl = str_replace('http:', $container->getParameter('restomods.url.scheme'), $container->get('router')->generate('restomods_sweepstakes', array('utm_source' => $prospect->getEmail(), 'utm_medium'=>'email', 'utm_campaign'=>'abandonedcart'), true));
                    $body = str_replace('TOKEN_URL', $tokenUrl, $template);
                    $body = str_replace('SWEEPSTAKES_URL', $sweepstakesUrl, $body);
                    $body = str_replace('CAR_NAME', $carName, $body);
                    $body = str_replace('CAR_IMAGE', $carImage, $body);
                    $body = str_replace('EXPIRE_DATE', $expireDateStr, $body);
                    $bodyPlain = str_replace('TOKEN_URL', $tokenUrl, $templatePlain);
                    $bodyPlain = str_replace('SWEEPSTAKES_URL', $sweepstakesUrl, $bodyPlain);
                    $bodyPlain = str_replace('CAR_NAME', $carName, $bodyPlain);
                    $bodyPlain = str_replace('EXPIRE_DATE', $expireDateStr, $bodyPlain);
                    $container->get('restomods.mailer')->sendMail($prospect->getEmail(), $subject, $body, $bodyPlain, false);
                    $prospect->setAbandonCartEmail(true);
                    $em->persist($prospect);
                    $output->writeln($prospect->getEmail());

                    $sendgridReceipts[] = array(
                        SendgridHelper::FIELD_EMAIL => $prospect->getEmail(),
                        SendgridHelper::FIELD_FIRST_NAME => $prospect->getFirstname(),
                        SendgridHelper::FIELD_LAST_NAME => $prospect->getLastname(),
                        SendgridHelper::FIELD_ABANDONER => 1
                    );
                }
                $em->flush();
            }

            if (!empty($sendgridReceipts)) {
                $container->get('restomods.sendgrid.api')->updateReceipts($sendgridReceipts);
            }
        }
    }
}

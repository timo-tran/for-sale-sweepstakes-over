<?php

namespace Restomods\ListingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
class MonthlySubscriberReportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('restomods:monthlysubscriberreport:email')
            ->setDescription('Send Email to abandon cart users.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = strtotime('first day of next month');
        $date_str = date("Y-m-d", $date);

        $output->writeln('Generating report for '. $date_str);

        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $stmt = $em->getConnection()->prepare("call MonthlySubscriberRevenue('".$date_str."')");
        $stmt->execute();
        $result = $stmt->fetchAll();

        $output->writeln('Exporting to CSV');

        $file = $container->get('kernel')->getRootDir().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'monthly_subscribers_revenue.csv';
        $csvh = fopen($file, 'w');
        $keys = array_keys(reset($result));
        fputcsv($csvh, $keys);
        foreach ($result as $record) {
            fputcsv($csvh, $record);
        }
        fclose($csvh);

        $output->writeln('Sending to admin..');
        $template = $container->get('twig')->render('RestomodsListingBundle:Emails:monthly_subscriber_report.html.twig', array());
        $templatePlain = $container->get('twig')->render('RestomodsListingBundle:Emails:monthly_subscriber_report.txt.twig', array());
        $subject = "Report for Monthly Subscribers Revenue";
        $container->get('restomods.mailer')->sendMail("daniel@restomods.com", $subject, $template, $templatePlain, false, null, "fastitteam@gmail.com", null, null, array($file));
    }
}

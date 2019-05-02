<?php

namespace Restomods\ListingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
class SweepstakesActivateCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('restomods:sweepstakes:activate')
            ->setDescription('Activate sweepstakes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $cDate = date('Y-m-d H:i:s');
        $query = $em->getRepository( 'RestomodsListingBundle:Sweepstakes' )->createQueryBuilder('e')
                    ->where("e.active = :active")
                    ->andWhere("e.startDate <= :date")
                    ->andWhere("e.endDate >= :date")
                    ->setParameter('date', $cDate)
                    ->setParameter('active',false)
                    ->orderBy("e.id", "DESC")
                    ->getQuery();
        $sweepstakes_array = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        if (!empty($sweepstakes_array)) {
            $output->writeln(['found next sweepstakes']);
            $next_sweepstakes = $sweepstakes_array[0];

            //
            // mark old sweepstakes 'Inactive'
            //
            $query = $em->getRepository( 'RestomodsListingBundle:Sweepstakes' )->createQueryBuilder('e')
                        ->where("e.active = :active")
                        ->setParameter('active',true)
                        ->getQuery();
            $sweepstakes_array = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
            foreach($sweepstakes_array as $sweepstakes) {
                $sweepstakes->setActive(false);
            }

            // mark the next sweepstakes 'Active'
            $next_sweepstakes->setActive(true);
            $em->flush();

            //
            // let prev customers to join to the new sweepstakes
            //
            $query = $em->getRepository( 'RestomodsListingBundle:Sweepstakes' )->createQueryBuilder('e')
                        ->where("e.id < :curId")
                        ->setParameter('curId', $next_sweepstakes->getId())
                        ->orderBy("e.id", "DESC")
                        ->getQuery();
            $sweepstakes_array = $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
            if (empty($sweepstakes_array)) { return; }
            $lastSweepstakes = $sweepstakes_array[0];
            $sweepstakesActive = $next_sweepstakes;

            $updated = 0;
            foreach ($lastSweepstakes->getUsers() as $member){
                if(!$container->get('restomods.rawsqlhelper')->isUserInSweepstakes($member, $sweepstakesActive) && in_array('ROLE_SUBSCRIBER_USER', $member->getRoles())) {
                    $sweepstakesActive->addUser($member);
                    $updated ++;
                }
                $output->write(["."]);
            }
            $em->flush();
        }
    }
}

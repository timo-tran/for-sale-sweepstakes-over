<?php

namespace Restomods\ListingBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Restomods\ListingBundle\Entity\Sweepstakes;

/**
 * UserReferrerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SweepstakesRepository extends EntityRepository
{
    public function getDateRange(){
        $cDate = date('Y-m-d H:m:s');
        return $this->createQueryBuilder('s')
            ->select('s')
            ->where('s.active = :active')
            ->andWhere('s.startDate <= :date')
            ->andWhere('s.endDate >= :date')
            ->setParameter('date',$cDate)
            ->setParameter('active',true)
            ->getQuery()
            ->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_OBJECT)
        ;
    }

    public function getProspectUsers(){
        $em = $this->getEntityManager();
        $query = $em->getRepository( 'ApplicationSonataUserBundle:User' )->createQueryBuilder('u')
                    ->where('u.sweepstakesPaymentCompleted = 0')
                    ->andWhere('u.fromSweepstakes = 1')
                    ->andWhere('u.abandonCartEmail <> 1')
                    ->andWhere('u.createdAt > :start')
                    ->andWhere('u.createdAt <= :end')
                    ->setParameter('start', date('Y-m-d H:i', strtotime('-90 minutes')))
                    ->setParameter('end', date('Y-m-d H:i', strtotime('-45 minutes')))
                    //->setMaxResults(2)
                    ->getQuery();
        return $query->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
    }
}

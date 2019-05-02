<?php

namespace Restomods\ListingBundle\Repository;

/**
 * SubscriptionFailureRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SubscriptionFailureRepository extends \Doctrine\ORM\EntityRepository
{
    const DEBUGGING = false;
    public function getTodayFailures(){

        $date = date('Y-m-d H:i', strtotime('tomorrow'));
        if (self::DEBUGGING) {
            $date = date('Y-m-d H:i', strtotime('+10 day'));
        }
        $query    = $this->createQueryBuilder( 'l' )
                         ->where( 'l.nextTryAt < :nextTryAt' )
                         ->setParameter( 'nextTryAt', $date )
                         ->getQuery();

        return $query->getResult();
    }
}
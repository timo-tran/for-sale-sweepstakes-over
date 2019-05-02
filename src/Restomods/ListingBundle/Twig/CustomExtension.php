<?php

namespace Restomods\ListingBundle\Twig;

class CustomExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('time_ago', array($this, 'timeAgo')),
            new \Twig_SimpleFilter('phone', array($this, 'phone')),
        );
    }

    public function timeAgo($datetime)
    {
        $time = time() - $datetime->getTimestamp();
        $units = array(
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        );

        foreach ($units as $unit => $val) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return ($val == 'second') ? 'a few seconds ago' :
                (($numberOfUnits > 1) ? $numberOfUnits : 'a')
                . ' ' . $val . (($numberOfUnits > 1) ? 's' : '') . ' ago';
        }
    }

    public function phone($phone)
    {
        $string = $phone."";
        $res = preg_replace('/[^\d]+/', "", $string);
        if (strlen($res) == 11 && substr($res, 0, 1) == "1") {
            $res = substr($res, 1);
        }
        if (strlen($res) == 10) {
            $str = $res;
            $res = '('.substr($res, 0, 3).')'.substr($res, 3, 3).'-'.substr($res, 6);
        }
        return $res;
    }
}

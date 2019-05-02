<?php

namespace Restomods\ListingBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SweepstakesControllerTest extends WebTestCase
{
    public function testOrder()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/sweepstakes/order');
    }

    public function testUpsell()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/sweepstakes/upsell');
    }

    public function testDownsell()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/sweepstakes/downsell');
    }

    public function testConfirm()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/sweepstakes/confirm');
    }

}

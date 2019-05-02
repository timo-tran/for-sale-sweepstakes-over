<?php

namespace Restomods\ListingBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ListingControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
    }

    public function testView()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/listing/{id}');
    }

}

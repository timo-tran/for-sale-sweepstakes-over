<?php

namespace Restomods\ListingBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MakeControllerTest extends WebTestCase
{
    public function testGetmodels()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/getModels');
    }

}

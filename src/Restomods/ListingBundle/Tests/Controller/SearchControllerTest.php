<?php

namespace Restomods\ListingBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SearchControllerTest extends WebTestCase
{
    public function testSearch()
    {
        $client = static::createClient();

        $crawler = $client->request('POST', '/search');
    }

    public function testAutocomplete()
    {
        $client = static::createClient();

        $crawler = $client->request('POST', '/autocomplete');
    }

}

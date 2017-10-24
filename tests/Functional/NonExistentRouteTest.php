<?php

namespace App\Tests\Functional;

class NonExistentRouteTest extends WebTestCase
{
    public function testInvalidRequestReturns400(): void
    {
        $client = static::createClient();
        $client->request('GET', '/shouldNeverExist');
        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
    }
}

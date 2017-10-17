<?php

namespace App\Tests\Functional;

class ViewTrackingTest extends WebTestCase
{
    public function testInvalidRequestReturns400(): void
    {
        $content = [
            'event'       => null,
            'resource-id' => null,
        ];

        $client = static::createClient();
        $client->request('POST', '/', [], [], [], json_encode($content));

        $response = $client->getResponse();

        $this->assertEquals('Bad response', $response->getContent());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function test(): void
    {
        $content = [
            'event'       => 'view-user',
            'resource-id' => 1234,
            'user-id' => 111,
        ];

        $client = static::createClient();
        $client->request('POST', '/', [], [], [], json_encode($content));

        $response = $client->getResponse();

        $this->assertEquals('Success', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }
}

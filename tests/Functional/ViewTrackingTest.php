<?php

namespace App\Tests\Functional;

use App\Service\Encryptor;
use Firebase\JWT\JWT;
use Symfony\Component\BrowserKit\Cookie as BKCookie;
use Symfony\Component\HttpFoundation\Cookie;

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

    public function testUserIdDecryption(): void
    {
        $content = [
            'event'       => 'view-user',
            'resource-id' => 1234,
        ];

        $encryptor = new Encryptor(getenv('APP_KEY'));
        $userId    = $encryptor->encrypt('123');

        $auth = JWT::encode(['did' => $userId], base64_decode(getenv('PRIVATE_KEY')), 'RS256');

        $client = static::createClient();
        $client->request('POST', '/', [], [], ['HTTP_AUTHORIZATION' => "Bearer {$auth}"], json_encode($content));

        $response = $client->getResponse();

        $this->assertEquals('Success', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUserUuidCookieIsSetWhenCookieDoesNotExist(): void
    {
        $content = [
            'event'       => 'view-user',
            'resource-id' => 1234,
        ];

        $client = static::createClient();
        $client->request('POST', '/', [], [], [], json_encode($content));

        /** @var Cookie $cookie */
        $cookie = $client->getResponse()->headers->getCookies()[0];

        $this->assertEquals('userUuid', $cookie->getName());
        $this->assertNotEmpty($cookie->getValue());
        $this->assertEquals(getenv('DOMAIN'), $cookie->getDomain());
        $this->assertEquals('/', $cookie->getPath());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
    }

    public function testSameUserUuidCookieIsReturned(): void
    {
        $content = [
            'event'       => 'view-user',
            'resource-id' => 1234,
        ];

        $encryptor = new Encryptor(getenv('APP_KEY'));

        $userUuid = $encryptor->encrypt('test');

        $client = static::createClient();
        $client->getCookieJar()->set(
            new BKCookie('userUuid', $userUuid, strtotime('+30 minutes'), '/', getenv('DOMAIN'), false)//TODO: false for the test, find a fix
        );
        $client->request('POST', '/', [], [], [], json_encode($content));

        /** @var Cookie $cookie */
        $cookie = $client->getResponse()->headers->getCookies()[0];

        $this->assertEquals('test', $encryptor->decrypt($cookie->getValue()));
    }
}

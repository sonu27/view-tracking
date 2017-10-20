<?php

namespace App\Tests\Functional;

use App\Service\Encryptor;
use App\Service\Jwt;
use Aws\DynamoDb\DynamoDbClient;
use Prophecy\Argument;
use Symfony\Component\BrowserKit\Cookie as BKCookie;
use Symfony\Component\HttpFoundation\Cookie;

class ViewTrackingTest extends WebTestCase
{
    private $dynamoDbClientMock;
    private $encryptor;
    private $jwtService;

    protected function setUp()
    {
        parent::setUp();

        $this->dynamoDbClientMock = $this->prophesize(DynamoDbClient::class);
        $this->dynamoDbClientMock->query(Argument::any())->willReturn(['Count' => 0]);
        $this->dynamoDbClientMock->updateItem(Argument::any())->willReturn();
        $this->dynamoDbClientMock->putItem(Argument::any())->willReturn();

        $this->encryptor = $this->prophesize(Encryptor::class);
        $this->encryptor->encrypt(Argument::any())->willReturn('test');
        $this->encryptor->decrypt(Argument::any())->willReturn('test');

        $this->jwtService = $this->prophesize(Jwt::class);
        $this->jwtService->decode(Argument::any())->willReturn((object)['pid' => 'userId']);
    }

    public function testInvalidRequestReturns400(): void
    {
        $content = [
            'event'       => null,
            'resource-id' => null,
        ];

        $client = static::createClient();
        $this->setMocks();

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

        $client = static::createClient();
        $this->setMocks();

        $client->request('POST', '/', [], [], ['HTTP_AUTHORIZATION' => "Bearer test"], json_encode($content));
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
        $this->setMocks();

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

        $userUuid = 'test';

        $client = static::createClient();
        $this->setMocks();

        $client->getCookieJar()->set(
            new BKCookie('userUuid', $userUuid, strtotime('+30 minutes'), '/', getenv('DOMAIN'), false)//TODO: false for the test, find a fix
        );
        $client->request('POST', '/', [], [], [], json_encode($content));

        /** @var Cookie $cookie */
        $cookie = $client->getResponse()->headers->getCookies()[0];

        $this->assertEquals($userUuid, $cookie->getValue());
    }

    private function setMocks()
    {
        self::$kernel->getContainer()->set(DynamoDbClient::class, $this->dynamoDbClientMock->reveal());
        self::$kernel->getContainer()->set(Encryptor::class, $this->encryptor->reveal());
        self::$kernel->getContainer()->set(Jwt::class, $this->jwtService->reveal());
    }
}

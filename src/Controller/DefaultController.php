<?php

namespace App\Controller;

use App\Model\View;
use App\Repository\ViewCountRepository;
use App\Repository\ViewRepository;
use App\Service\Encryptor;
use Aws\DynamoDb\Exception\DynamoDbException;
use Defuse\Crypto\Crypto;
use Firebase\JWT\JWT;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        $time = round(microtime(true) * 1000);
        $data = json_decode($request->getContent(), true);

        if (!isset($data['event'], $data['resource-id'])
            || !in_array($data['event'], View::EVENTS, true)) {

            return new Response('Bad response', 400);
        }

        $response = Response::create();

        /** @var Encryptor $encryptor */
        $encryptor = $this->get(Encryptor::class);

        if ($request->cookies->has('userUuid')) {
            $userUuid = $encryptor->decrypt($request->cookies->get('userUuid'));
            $response->headers->setCookie($this->getCookie('userUuid', $request->cookies->get('userUuid')));
        } else {
            $userUuid = $encryptor->encrypt((string)Uuid::uuid4());
            $response->headers->setCookie($this->getCookie('userUuid', $userUuid));
        }

        $userId = null;
        if ($request->headers->has('Authorization')) {
            $auth = str_replace('Bearer ', '', $request->headers->get('Authorization'));

            JWT::$leeway = 60;
            $payload     = JWT::decode($auth, base64_decode(getenv('PUBLIC_KEY')), ['RS256']);

            $userId = Crypto::decryptWithPassword($payload->did, getenv('APP_KEY'));
        }

        $resourceType = (string)explode('-', $data['event'])[1];
        $resourceId   = (int)$data['resource-id'];

        $view = new View(
            $data['event'],
            $resourceType,
            $resourceId,
            $userUuid,
            $userId
        );

        $recordData = $view->toArray();

        /** @var ViewRepository $viewRepo */
        $viewRepo = $this->get(ViewRepository::class);

        /** @var ViewCountRepository $viewCountRepo */
        $viewCountRepo = $this->get(ViewCountRepository::class);

        // find any items within the last session time
        $result['Count'] = 0;

        if ($result['Count'] == 0) {
            try {
                $viewCountRepo->incrementCount($resourceId, $resourceType);

            } catch (DynamoDbException $e) {
                return new Response('Unable to add item', 400);
            }

            $viewRepo->addView($recordData);
        }

        $response->setContent('Success');

        return $response;
    }

    private function getCookie(string $key, string $value): Cookie
    {
        return new Cookie($key, $value, strtotime('+30 minutes'), '/', getenv('DOMAIN'), true);
    }
}

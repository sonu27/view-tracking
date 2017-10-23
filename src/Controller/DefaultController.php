<?php

namespace App\Controller;

use App\Model\View;
use App\Repository\ViewCountRepository;
use App\Repository\ViewRepository;
use App\Service\Encryptor;
use App\Service\Jwt;
use Aws\DynamoDb\Exception\DynamoDbException;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function homeAction()
    {
        return new Response('View tracking');
    }

    public function indexAction(Request $request)
    {
        $response = Response::create();
        $data     = json_decode($request->getContent(), true);
        $time     = round(microtime(true) * 1000);

        if (!isset($data['event'], $data['resource-id'])
            || !in_array($data['event'], View::EVENTS, true)) {

            return new Response('Bad response', 400);
        }

        $userUuid     = $this->getUserUuidFromRequestOrCreateNewOne($request);
        $userId       = $this->getUserIdFromRequest($request);
        $resourceId   = (int)$data['resource-id'];
        $view         = new View($data['event'], $resourceId, $userUuid, $userId);
        $resourceType = $view->getResourceType();

        /** @var Encryptor $encryptor */
        $encryptor = $this->get(Encryptor::class);

        /** @var ViewRepository $viewRepo */
        $viewRepo = $this->get(ViewRepository::class);

        /** @var ViewCountRepository $viewCountRepo */
        $viewCountRepo = $this->get(ViewCountRepository::class);

        // find any items within the last session time
        try {
            $result = $viewRepo->findRecentByUser($resourceType, $resourceId, $userUuid, $time);
        } catch (DynamoDbException $e) {
            var_dump($e->getMessage());//todo: remove
        }

        if (isset($result['Count']) && $result['Count'] == 0) {
            try {
                $viewCountRepo->incrementCount($resourceId, $resourceType);

            } catch (DynamoDbException $e) {
                return new Response('Unable to add item', 400);
            }

            $viewRepo->addView($view);
        }

        $response->headers->setCookie($this->getCookie('userUuid', $encryptor->encrypt($userUuid)));
        $response->setContent('Success');

        return $response;
    }

    private function getCookie(string $key, string $value): Cookie
    {
        return new Cookie($key, $value, strtotime('+30 minutes'), '/', getenv('DOMAIN'), true);
    }

    private function getUserIdFromRequest(Request $request)
    {
        $userId = null;
        if ($request->headers->has('Authorization')) {
            $jwt = str_replace('Bearer ', '', $request->headers->get('Authorization'));

            $jwtService = $this->get(Jwt::class);
            $payload    = $jwtService->decode($jwt);

            if (isset($payload->did)) {
                $encryptor = $this->get(Encryptor::class);
                $userId    = $encryptor->decrypt($payload->did);
            }
        }

        return $userId;
    }

    private function getUserUuidFromRequestOrCreateNewOne(Request $request): string
    {
        if ($request->cookies->has('userUuid')) {
            $encryptor = $this->get(Encryptor::class);
            $userUuid  = $encryptor->decrypt($request->cookies->get('userUuid'));
        } else {
            $userUuid = (string)Uuid::uuid4();
        }

        return $userUuid;
    }
}

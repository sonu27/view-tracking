<?php

namespace App\Controller;

use App\Model\View;
use App\Repository\ViewCountRepository;
use App\Repository\ViewRepository;
use App\Service\Encryptor;
use App\Service\Jwt;
use Ramsey\Uuid\Uuid;
use Rollbar\Payload\Level;
use Rollbar\Rollbar;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    const USER_UUID_KEY = 'user_uuid';

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

        try {
            // find any items within the last session time
            $result = $viewRepo->findRecentByUser($resourceType, $resourceId, $userUuid, $time);

            if (isset($result['Count']) && $result['Count'] == 0) {
                $viewCountRepo->incrementCount($resourceId, $resourceType);
            }

            $viewRepo->addView($view);

        } catch (\Exception $e) {
            Rollbar::log(Level::ERROR, $e);

            return new Response('Unable to add item', 400);
        }

        $response->headers->setCookie($this->getCookie(self::USER_UUID_KEY, $encryptor->encrypt($userUuid)));
        $response->setContent('Success');

        return $response;
    }

    private function getCookie(string $key, string $value): Cookie
    {
        return new Cookie($key, $value, strtotime('+1 year'), '/', getenv('DOMAIN'), true);
    }

    private function getUserIdFromRequest(Request $request)
    {
        $userId = null;
        if ($request->headers->has('Authorization')) {
            $jwt = str_replace('Bearer ', '', $request->headers->get('Authorization'));

            $jwtService = $this->get(Jwt::class);
            $payload    = $jwtService->decode($jwt);

            if (isset($payload->sub)) {
                $userId = $payload->sub;
            }
        }

        return $userId;
    }

    private function getUserUuidFromRequestOrCreateNewOne(Request $request): string
    {
        if ($request->cookies->has(self::USER_UUID_KEY)) {
            $encryptor = $this->get(Encryptor::class);
            $userUuid  = $encryptor->decrypt($request->cookies->get(self::USER_UUID_KEY));
        } else {
            $userUuid = (string)Uuid::uuid4();
        }

        return $userUuid;
    }
}

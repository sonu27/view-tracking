<?php

namespace App\Controller;

use App\Model\View;
use App\Repository\ViewCountRepository;
use App\Repository\ViewRepository;
use Aws\DynamoDb\Exception\DynamoDbException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        $time = round(microtime(true) * 1000);
        $data = json_decode($request->getContent(), true);

        $events = [
            'view-user',
            'view-page',
            'view-project',
            'view-job',
        ];

        if (!isset($data['event'], $data['resource-id']) || !in_array($data['event'], $events, true)) {
            return new Response('Bad response', 400);
        }

        $resourceType = (string)explode('-', $data['event'])[1];
        $resourceId = (int)$data['resource-id'];

        $view = new View(
            $data['event'],
            $resourceType,
            $resourceId,
            !empty($data['user-id']) ? (int)$data['user-id'] : null
        );

        $recordData = $view->toArray();

        /** @var ViewRepository $viewRepo */
        $viewRepo = $this->get(ViewRepository::class);

        /** @var ViewCountRepository $viewCountRepo */
        $viewCountRepo = $this->get(ViewCountRepository::class);

        // find any items within the last session time
        $result = $viewRepo->findRecent($resourceType, $resourceId, $time);

        if ($result['Count'] == 0) {
            try {
                $viewCountRepo->incrementCount($resourceId, $resourceType);

            } catch (DynamoDbException $e) {
                return new Response('Unable to add item', 400);
            }

            $viewRepo->addView($recordData);
        }

        return new Response('Success');
    }
}

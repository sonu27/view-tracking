<?php

namespace App\Repository;


use App\Model\View;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

class ViewRepository
{
    const TABLE = 'Views';
    const SESSION_TIME_IN_MILLISECONDS = 180000;

    /**
     * @var DynamoDbClient
     */
    private $client;

    /**
     * @var Marshaler
     */
    private $marshaler;

    public function __construct(DynamoDbClient $client, Marshaler $marshaler)
    {
        $this->client    = $client;
        $this->marshaler = $marshaler;
    }

    public function addView(View $view)
    {
        $data   = $this->convertView($view);
        $params = [
            'TableName' => self::TABLE,
            'Item'      => $this->marshaler->marshalItem($data),
        ];

        return $this->client->putItem($params);
    }

    public function findRecentByUser(string $resourceType, int $resourceId, string $userUuid, int $currentTime)
    {
        $resource = $resourceType.'-'.$resourceId;

        return $this->client->query([
            'TableName'                 => self::TABLE,
            'KeyConditionExpression'    => 'ID = :resource',
            'FilterExpression'          => 'UserUUID = :userUuid and UnixTime > :t',
            'ExpressionAttributeValues' => [
                ':resource' => ['S' => $resource],
                ':userUuid' => ['S' => $userUuid],
                ':t'        => ['N' => (string)($currentTime - self::SESSION_TIME_IN_MILLISECONDS)],
            ],
        ]);
    }

    private function convertView(View $view)
    {
        $viewData = $view->toArray();

        return [
            'ID'           => $viewData['resourceType'].'-'.$viewData['resourceId'],
            'UUID'         => $viewData['id'],
            'event'        => $viewData['event'],
            'ResourceType' => $viewData['resourceType'],
            'ResourceId'   => $viewData['resourceId'],
            'UserUUID'     => $viewData['userUuid'],
            'UnixTime'     => $viewData['unixTime'],
            'UserId'       => $viewData['userId'],
        ];
    }
}

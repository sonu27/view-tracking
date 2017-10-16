<?php

namespace App\Repository;


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
        $this->client = $client;
        $this->marshaler = $marshaler;
    }

    public function addView(array $data)
    {
        $params = [
            'TableName' => self::TABLE,
            'Item'      => $this->marshaler->marshalItem($data),
        ];

        return $this->client->putItem($params);
    }

    public function findRecent(string $resourceType, int $resourceId, int $currentTime)
    {
        return $this->client->query([
            'TableName'                 => self::TABLE,
            'KeyConditionExpression'    => 'resource = :r and resourceId = :resourceId',
            'FilterExpression'          => 'unixTime > :t',
            'ExpressionAttributeValues' => [
                ':r'          => $resourceType,
                ':resourceId' => $resourceId,
                ':t'          => $currentTime - self::SESSION_TIME_IN_MILLISECONDS,
            ],
        ]);
    }
}

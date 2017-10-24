<?php

namespace App\Repository;

use Aws\DynamoDb\DynamoDbClient;

class ViewCountRepository
{
    const TABLE = 'ViewCounts';

    /**
     * @var DynamoDbClient
     */
    private $client;

    public function __construct(DynamoDbClient $client)
    {
        $this->client = $client;
    }

    public function incrementCount(int $resourceId, string $resourceType)
    {
        return $this->client->updateItem([
            'TableName'                 => self::TABLE,
            'Key'                       => [
                'ID' => ['S' => $this->getId($resourceType, $resourceId)],
            ],
            'UpdateExpression'          => 'Add ViewCount :incr SET ResourceType = :resourceType',
            'ExpressionAttributeValues' => [
                ':incr'         => ['N' => '1'],
                ':resourceType' => ['S' => $resourceType],
            ],
        ]);
    }

    private function getId(string $resourceType, int $resourceId): string
    {
        return $resourceType.'-'.$resourceId;
    }
}

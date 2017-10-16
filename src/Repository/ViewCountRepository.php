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
                'resourceId' => ['N' => $resourceId],
                'resource'   => ['S' => $resourceType],
            ],
            'UpdateExpression'          => 'Add num :incr',
            'ExpressionAttributeValues' => [
                ':incr' => ['N' => '1'],
            ],
        ]);
    }
}

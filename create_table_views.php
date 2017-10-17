<?php

use Aws\DynamoDb\Exception\DynamoDbException;
use Symfony\Component\Dotenv\Dotenv;

require 'vendor/autoload.php';

// The check is to ensure we don't use .env in production
if (!getenv('APP_ENV')) {
    (new Dotenv())->load(__DIR__.'/.env');
}

$sdk = new \Aws\Sdk([
    'version'     => 'latest',
    'region'      => 'eu-west-1',
    'credentials' => [
        'key'    => getenv('AWS_KEY'),
        'secret' => getenv('AWS_SECRET'),
    ],
]);

$dbClient = $sdk->createDynamoDb();

$params = [
    'TableName'             => 'Views',
    'KeySchema'             => [
        [
            'AttributeName' => 'id',
            'KeyType'       => 'HASH'  //Partition key
        ],
        [
            'AttributeName' => 'resourceId',
            'KeyType'       => 'RANGE'  //Sort key
        ],
    ],
    'AttributeDefinitions'  => [
        [
            'AttributeName' => 'id',
            'AttributeType' => 'S',
        ],
        [
            'AttributeName' => 'resourceId',
            'AttributeType' => 'N',
        ],
        [
            'AttributeName' => 'resourceType',
            'AttributeType' => 'S',
        ],

    ],
    'LocalSecondaryIndexes' => [
        [
            'IndexName' => 'ResourceIndex',
            'KeySchema' => [
                [
                    'AttributeName' => 'id',
                    'KeyType' => 'HASH',
                ],
                [
                    'AttributeName' => 'resourceType',
                    'KeyType' => 'RANGE',
                ],
            ],
            'Projection' => [
                'ProjectionType' => 'ALL',
            ],
        ],
    ],
    'ProvisionedThroughput' => [
        'ReadCapacityUnits'  => 10,
        'WriteCapacityUnits' => 10,
    ],
];

try {
    $result = $dbClient->createTable($params);
    echo 'Created table.  Status: '.
        $result['TableDescription']['TableStatus']."\n";

} catch (DynamoDbException $e) {
    echo "Unable to create table:\n";
    echo $e->getMessage()."\n";
}

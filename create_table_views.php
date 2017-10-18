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
            'AttributeName' => 'ID',
            'KeyType'       => 'HASH'  //Partition key
        ],
        [
            'AttributeName' => 'UUID',
            'KeyType'       => 'RANGE'  //Sort key
        ],
    ],
    'AttributeDefinitions'  => [
        [
            'AttributeName' => 'ID',
            'AttributeType' => 'S',
        ],
        [
            'AttributeName' => 'UUID',
            'AttributeType' => 'S',
        ],
        [
            'AttributeName' => 'UserUUID',
            'AttributeType' => 'S',
        ],
        [
            'AttributeName' => 'UnixTime',
            'AttributeType' => 'N',
        ],
        [
            'AttributeName' => 'ResourceType',
            'AttributeType' => 'S',
        ],

    ],
    'LocalSecondaryIndexes' => [
        [
            'IndexName'  => 'UserUuidIndex',
            'KeySchema'  => [
                [
                    'AttributeName' => 'ID',
                    'KeyType'       => 'HASH',
                ],
                [
                    'AttributeName' => 'UserUUID',
                    'KeyType'       => 'RANGE',
                ],
            ],
            'Projection' => [
                'ProjectionType' => 'KEYS_ONLY',
            ],
        ],
        [
            'IndexName'  => 'UnixTimeIndex',
            'KeySchema'  => [
                [
                    'AttributeName' => 'ID',
                    'KeyType'       => 'HASH',
                ],
                [
                    'AttributeName' => 'UnixTime',
                    'KeyType'       => 'RANGE',
                ],
            ],
            'Projection' => [
                'ProjectionType' => 'KEYS_ONLY',
            ],
        ],
        [
            'IndexName'  => 'ResourceTypeIndex',
            'KeySchema'  => [
                [
                    'AttributeName' => 'ID',
                    'KeyType'       => 'HASH',
                ],
                [
                    'AttributeName' => 'ResourceType',
                    'KeyType'       => 'RANGE',
                ],
            ],
            'Projection' => [
                'ProjectionType' => 'KEYS_ONLY',
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

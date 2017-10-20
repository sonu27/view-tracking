<?php

namespace App\Service;

use Firebase\JWT\JWT as FirebaseJWT;

class Jwt
{
    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var string
     */
    private $privateKey;

    /**
     * @var string
     */
    private $algorithm;

    public function __construct(string $publicKey, string $privateKey)
    {
        $this->publicKey  = base64_decode($publicKey);
        $this->privateKey = base64_decode($privateKey);
        $this->algorithm  = 'RS256';

        FirebaseJWT::$leeway = 60;
    }

    public function encode(array $payload): string
    {
        return FirebaseJWT::encode($payload, $this->privateKey, $this->algorithm);
    }

    public function decode(string $jwt): \stdClass
    {
        return FirebaseJWT::decode($jwt, $this->publicKey, [$this->algorithm]);
    }
}

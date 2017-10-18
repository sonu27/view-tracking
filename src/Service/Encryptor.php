<?php

namespace App\Service;

use Defuse\Crypto\Crypto;

class Encryptor
{
    /**
     * @var string
     */
    private $key;

    public function __construct(string $key)
    {

        $this->key = $key;
    }

    public function encrypt(string $text)
    {
        return Crypto::encryptWithPassword($text, $this->key);
    }

    public function decrypt(string $text)
    {
        return Crypto::decryptWithPassword($text, $this->key);
    }
}

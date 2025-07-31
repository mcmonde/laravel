<?php

namespace App\Services;

use App\Contracts\CryptoServiceInterface;
use Illuminate\Support\Facades\Log;

class RSAService implements CryptoServiceInterface
{
    protected string $privateKey;

    public function __construct()
    {
        $this->privateKey = file_get_contents(storage_path('keys/private.pem'));
    }

    public function decrypt(string $encrypted): ?string
    {
        $result = null;

        if (openssl_private_decrypt(base64_decode($encrypted), $result, $this->privateKey)) {
            return $result;
        }

        Log::error('RSA decryption failed.');
        return null;
    }
}

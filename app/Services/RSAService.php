<?php

namespace App\Services;

use App\Contracts\CryptoServiceInterface;
use Illuminate\Support\Facades\Log;

class RSAService implements CryptoServiceInterface
{
    protected string $privateKey;

    public function __construct()
    {
        $this->privateKey = file_get_contents(storage_path('app/keys/private.pem'));
    }

    public function decrypt(string $encrypted): ?string
    {
        $decrypted = null;

        if (openssl_private_decrypt(base64_decode($encrypted), $decrypted, $this->privateKey)) {
            return $decrypted;
        }

        Log::error('RSA decryption failed.');
        return null;
    }
}

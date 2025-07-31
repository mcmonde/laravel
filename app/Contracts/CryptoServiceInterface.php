<?php

namespace App\Contracts;

interface CryptoServiceInterface
{
    public function decrypt(string $encrypted): ?string;
}

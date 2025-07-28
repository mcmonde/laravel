<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateRSAKeys extends Command
{
    protected $signature = 'rsa:generate';
    protected $description = 'Generate RSA public/private key pair and store in storage/app/keys';

    public function handle()
    {
        $keyPath = storage_path('app/keys');

        if (!is_dir($keyPath)) {
            mkdir($keyPath, 0700, true);
        }

        $privateKeyPath = "$keyPath/private.pem";
        $publicKeyPath = "$keyPath/public.pem";

        // Generate private key
        $res = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        // Extract private key
        openssl_pkey_export($res, $privateKey);

        // Extract public key
        $publicKeyDetails = openssl_pkey_get_details($res);
        $publicKey = $publicKeyDetails['key'];

        // Save private key
        file_put_contents($privateKeyPath, $privateKey);
        chmod($privateKeyPath, 0600); // read/write owner only

        // Save public key
        file_put_contents($publicKeyPath, $publicKey);
        chmod($publicKeyPath, 0644); // readable publicly (optional)

        $this->info("RSA key pair generated:");
        $this->line("  🔐 Private key: $privateKeyPath");
        $this->line("  🔓 Public key:  $publicKeyPath");

        return 0;
    }
}

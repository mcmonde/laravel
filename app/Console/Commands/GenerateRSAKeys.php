<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateRSAKeys extends Command
{
    protected $signature = 'rsa:generate';
    protected $description = 'Generate RSA public/private key pair and store in storage/app/keys';

    public function handle()
    {
        $keyPath = storage_path('keys');

        if (!File::exists($keyPath)) {
            File::makeDirectory($keyPath, 0755, true);
        }

        $privateKeyPath = $keyPath . '/private.pem';
        $publicKeyPath = $keyPath . '/public.pem';

        // If keys already exist, confirm before overwriting
        if (File::exists($privateKeyPath) || File::exists($publicKeyPath)) {
            if (!$this->confirm('Keys already exist. Overwrite?', false)) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        $this->info('Generating RSA key pair...');

        $config = [
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);

        if (!$res) {
            $this->error('Failed to generate RSA key pair.');
            return self::FAILURE;
        }

        // Extract private key
        openssl_pkey_export($res, $privateKey);

        // Extract public key
        $keyDetails = openssl_pkey_get_details($res);
        $publicKey = $keyDetails['key'];

        // Save private key
        file_put_contents($privateKeyPath, $privateKey);
        chmod($privateKeyPath, 0600); // read/write owner only

        // Save public key
        file_put_contents($publicKeyPath, $publicKey);
        chmod($publicKeyPath, 0644); // readable publicly (optional)

        $this->info("RSA key pair generated:");
        $this->line("  🔐 Private key: $privateKeyPath");
        $this->line("  🔓 Public key:  $publicKeyPath");

        return self::SUCCESS;
    }
}

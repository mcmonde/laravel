<?php

namespace App\Providers;

use App\Services\RSAService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use League\Flysystem\Filesystem;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RSAService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Storage::extend('azure', function ($app, $config) {
            $endpoint = sprintf(
                'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s',
                $config['name'],
                $config['key']
            );

            $client = BlobRestProxy::createBlobService($endpoint);

            $adapter = new AzureBlobStorageAdapter(
                $client,
                $config['container']
            );

            return new Filesystem($adapter);
        });

        Storage::extend('azure_sas', function ($app, $config) {
            $endpoint = sprintf(
                'BlobEndpoint=https://%s.blob.core.windows.net/;SharedAccessSignature=%s',
                $config['name'],
                $config['sas_token']
            );

            $client = BlobRestProxy::createBlobService($endpoint);

            $adapter = new AzureBlobStorageAdapter(
                $client,
                $config['container']
            );

            return new Filesystem($adapter);
        });
    }
}

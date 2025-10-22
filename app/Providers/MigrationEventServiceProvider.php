<?php

namespace App\Providers;

use App\Services\DatabaseSchemaCacheService;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class MigrationEventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // This event is fired after all migrations are done
        Event::listen(MigrationsEnded::class, function () {
            try {
                app(DatabaseSchemaCacheService::class)->refresh();

                if (app()->runningInConsole()) {
                    echo "✅ Database schema cache refreshed successfully.\n";
                }

                Log::info('✅ Database schema cache refreshed after migrations.');
            } catch (\Throwable $e) {
                Log::error('❌ Failed to refresh schema cache: ' . $e->getMessage());
            }
        });
    }
}

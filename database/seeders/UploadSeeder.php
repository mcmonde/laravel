<?php

namespace Database\Seeders;

use App\Models\StorageType;
use Illuminate\Database\Seeder;
use App\Models\UploadCategory;

class UploadSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUploadDisks();
        $this->seedUploadCategories();
    }

    protected function seedUploadDisks(): void
    {
        $uploadDisks = [
            ['name' => 'local', 'driver' => 'local'],
            ['name' => 'public', 'driver' => 'local'],
            ['name' => 's3', 'driver' => 's3'],
            ['name' => 'digitalocean', 'driver' => 's3'],
            ['name' => 'azure', 'driver' => 'azure'],
            ['name' => 'azure_sas', 'driver' => 'azure_sas'],
        ];

        foreach ($uploadDisks as $disk) {
            $record = StorageType::updateOrCreate(
                ['name' => $disk['name']],
                ['driver' => $disk['driver']]
            );

            $this->echoColored(
                ($record->wasRecentlyCreated ? 'Created' : 'Updated') . " upload disk: {$disk['name']}",
                $record->wasRecentlyCreated ? 'green' : 'yellow'
            );
        }
    }

    protected function seedUploadCategories(): void
    {
        $uploadCategories = [
            ['name' => 'avatar', 'description' => 'User profile photos.'],
            ['name' => 'document', 'description' => 'System file documents.'],
            ['name' => 'spreadsheet', 'description' => 'System file spreadsheets.'],
            ['name' => 'pdf', 'description' => 'System Portable Format File.'],
        ];

        foreach ($uploadCategories as $category) {
            $record = UploadCategory::updateOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );

            $this->echoColored(
                ($record->wasRecentlyCreated ? 'Created' : 'Updated') . " upload category: {$category['name']}",
                $record->wasRecentlyCreated ? 'green' : 'yellow'
            );
        }
    }

    protected function echoColored(string $text, string $color): void
    {
        $colors = [
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'reset' => "\033[0m",
        ];

        echo $colors[$color] . $text . $colors['reset'] . PHP_EOL;
    }
}

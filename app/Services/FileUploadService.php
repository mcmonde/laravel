<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    // NOTE: if you use DIGITAL OCEAN, please install the composer package first.
    // composer: composer require league/flysystem-aws-s3-v3
    // link for details: https://packagist.org/packages/league/flysystem-aws-s3-v3

    public function upload($file, $table_name = null, $root_folder = 'uploads'): string
    {
        // Generate a unique local filename
        $fileName = $this->generateFileName($file);
        // Determine the upload path based on the table name
        $uploadPath = $this->generateUploadPath($root_folder, $table_name);
        // Store the file

        try {
            if(env('FILESYSTEM_DISK') == 'digitalocean') {
                $filePath = Storage::disk('digitalocean')->putFile($uploadPath, $file, 'public');

                if (empty($filePath)) {
                    throw new \Exception('Failed to upload file to DigitalOcean Spaces');
                }
            } else {
                $filePath = $file->storeAs($uploadPath, $fileName, 'public');
                if (empty($filePath)) {
                    throw new \Exception('Failed to upload file to server.');
                }
            }

            return $filePath;
        } catch (\Exception $exception) {
            if (env('FILESYSTEM_DISK') == 'digitalocean') {
                logger()->error('Error uploading file to DigitalOcean Spaces: ' . $exception->getMessage());
            } else {
                logger()->error('Error uploading file in your server: ' . $exception->getMessage());
            }

            return '';
        }
    }

    public function generateFileName($file): string
    {
        $extension = $file->getClientOriginalExtension();
        $randomName = Str::random(36);
        $date = now()->format('YmdHis');

        return "{$date}-{$randomName}.{$extension}";
    }

    public function generateUploadPath($folder, $tableName): string
    {
        // Use the table name if provided, otherwise use a default folder
        if ($tableName) {
            return "{$folder}/{$tableName}";
        } else {
            return "{$folder}/default";
        }
    }
}

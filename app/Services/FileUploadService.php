<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Exception;

class FileUploadService
{
    // NOTE: if you use DIGITAL OCEAN, please install the composer package first.
    // composer: composer require league/flysystem-aws-s3-v3
    // link for details: https://packagist.org/packages/league/flysystem-aws-s3-v3

    public function upload(UploadedFile $file, ?string $table_name): string
    {
        if (empty($table_name)) {
            throw new Exception('The table name parameter is required.');
        }

        $root_folder = config('filesystems.disks.digitalocean.root_path', 'sample-directory/uploads');
        $fileName = $this->generateFileName($file);
        $uploadPath = $this->generateUploadPath($root_folder, $table_name);

        try {
            if (config('filesystems.default') === 'digitalocean') {
                $filePath = Storage::disk('digitalocean')->putFileAs($uploadPath, $file, $fileName, 'public');
            } else {
                $filePath = $file->storeAs($uploadPath, $fileName, 'public');
            }

            if (!$filePath) {
                throw new Exception('Failed to upload file.');
            }

            return $filePath;
        } catch (Exception $exception) {
            logger()->error("File upload error: {$exception->getMessage()}", [
                'file_name' => $fileName,
                'path' => $uploadPath
            ]);

            throw new Exception('File upload failed.');
        }
    }

    private function generateFileName(UploadedFile $file): string
    {
        // datetime-string(36).extension
        return now()->format('YmdHis') . '-' . Str::random(36) . '.' . $file->getClientOriginalExtension();
    }

    private function generateUploadPath(string $folder, string $tableName): string
    {
        return "{$folder}/{$tableName}";
    }
}

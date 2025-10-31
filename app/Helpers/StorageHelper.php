<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    /**
     * Get the public URL for a file stored in S3/Laravel Cloud Object Storage
     */
    public static function getS3Url(string $path): string
    {
        return config('filesystems.disks.s3.url') . '/' . ltrim($path, '/');
    }

    /**
     * Get the URL for a file, handling both old (local) and new (S3) storage
     */
    public static function getFileUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        // Handle old storage format (local public disk)
        if (str_starts_with($path, '/storage/') || str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        // Handle new storage format (S3/Laravel Cloud Object Storage)
        return self::getS3Url($path);
    }
}
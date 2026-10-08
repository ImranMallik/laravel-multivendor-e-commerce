<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageUploadService
{
    private const DISK = 'public';

    /**
     * Store the image under a random filename and return its path.
     */
    public function upload(UploadedFile $file, string $folder): string
    {
        $name = Str::uuid().'.'.$file->guessExtension();

        $path = $file->storeAs(trim($folder, '/'), $name, self::DISK);

        if ($path === false) {
            throw new RuntimeException('The image could not be stored.');
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if (filled($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}

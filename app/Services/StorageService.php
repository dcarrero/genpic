<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    /**
     * Returns the correct disk name based on the current environment.
     */
    public function disk(): string
    {
        return app()->environment(['local', 'testing']) ? 'local' : 'wasabi';
    }

    /**
     * Stores $content on the correct disk using the canonical path pattern:
     * {type}/{session_uuid}/{timestamp}_{hash}.{ext}
     *
     * @param  string|\Stringable  $content  Raw binary content of the file
     * @param  string              $type     One of: originals, generations, watermarked, exports
     * @param  string              $sessionUuid
     * @param  string              $ext      File extension without leading dot (e.g. 'jpg', 'png')
     * @return string              The stored path
     */
    public function store(string|\Stringable $content, string $type, string $sessionUuid, string $ext = 'jpg'): string
    {
        $path = sprintf(
            '%s/%s/%s_%s.%s',
            $type,
            $sessionUuid,
            now()->timestamp,
            Str::random(8),
            $ext,
        );

        Storage::disk($this->disk())->put($path, (string) $content);

        return $path;
    }

    /**
     * Returns a temporary (signed) URL for the given path.
     * On Wasabi (production) it generates a proper signed URL.
     * On local/testing it falls back to a plain storage URL.
     *
     * @param  string             $path
     * @param  \DateTimeInterface $expiration
     * @return string
     */
    public function temporaryUrl(string $path, \DateTimeInterface $expiration): string
    {
        $disk = $this->disk();

        if ($disk === 'wasabi') {
            return Storage::disk($disk)->temporaryUrl($path, $expiration);
        }

        return Storage::disk($disk)->url($path);
    }

    /**
     * Deletes a file from the correct disk.
     *
     * @param  string  $path
     * @return bool
     */
    public function delete(string $path): bool
    {
        return Storage::disk($this->disk())->delete($path);
    }

    /**
     * Checks whether a file exists on the correct disk.
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk())->exists($path);
    }

    /**
     * Returns the size in bytes of a file on the correct disk.
     */
    public function size(string $path): int
    {
        return Storage::disk($this->disk())->size($path);
    }
}

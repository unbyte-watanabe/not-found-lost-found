<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Handles secure image file upload and deletion via Laravel's Storage facade.
 *
 * MIME validation and size enforcement are the primary responsibility of the
 * Form Request layer, but this service performs a second-pass check to ensure
 * no non-image file slips through programmatic calls.
 */
final class ImageUploadService
{
    private const MAX_SIZE_BYTES   = 10 * 1024 * 1024; // 10 MB
    private const ALLOWED_MIMES    = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const UPLOAD_DIRECTORY = 'found-item-images';

    /**
     * The filesystem disk name, resolved from config.
     */
    private readonly string $disk;

    public function __construct()
    {
        $this->disk = (string) config('filesystems.upload_disk', 'local');
    }

    /**
     * Upload an image file and return its publicly accessible URL.
     *
     * @param UploadedFile $file The validated uploaded file.
     * @return string The public URL of the stored file.
     *
     * @throws \InvalidArgumentException If the file fails MIME or size checks.
     * @throws \RuntimeException         If the file cannot be stored.
     */
    public function upload(UploadedFile $file): string
    {
        $this->validateFile($file);

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $filename  = Str::uuid()->toString() . '.' . $extension;
        $path      = self::UPLOAD_DIRECTORY . '/' . $filename;

        $stored = Storage::disk($this->disk)->put($path, file_get_contents($file->getRealPath()));

        if ($stored === false) {
            throw new \RuntimeException('ファイルの保存に失敗しました。');
        }

        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Delete a previously uploaded file by its storage path or public URL.
     *
     * The method attempts to strip any disk URL prefix so that both raw paths
     * and full URLs are accepted gracefully.
     *
     * @param string $path Storage path (e.g. "found-item-images/uuid.jpg") or full URL.
     * @return bool True if the file was deleted, false if it did not exist.
     */
    public function delete(string $path): bool
    {
        // Strip URL prefix if a full URL was supplied
        try {
            $baseUrl = Storage::disk($this->disk)->url('');
            if (str_starts_with($path, $baseUrl)) {
                $path = ltrim(substr($path, strlen($baseUrl)), '/');
            }
        } catch (\Throwable) {
            // url() may throw on some drivers; proceed with original path
        }

        if (!Storage::disk($this->disk)->exists($path)) {
            return false;
        }

        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * Validate MIME type and file size.
     *
     * @throws \InvalidArgumentException On validation failure.
     */
    private function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            throw new \InvalidArgumentException(
                'ファイルサイズが上限（10MB）を超えています。',
            );
        }

        $mime = $file->getMimeType() ?? '';
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException(
                sprintf('許可されていないファイル形式です（%s）。画像ファイルのみアップロード可能です。', $mime),
            );
        }
    }
}

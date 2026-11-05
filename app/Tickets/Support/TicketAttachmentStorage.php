<?php

namespace App\Tickets\Support;

use App\Tickets\Exceptions\TicketException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class TicketAttachmentStorage
{
    private const ROOT_DIR = 'ticket-attachments';

    private const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public static function diskName(): string
    {
        return (string) config('tickets.attachments_disk', 'public');
    }

    public static function storeMany(int $ticketId, array $files): array
    {
        $stored = [];
        $disk = Storage::disk(self::diskName());

        foreach (array_slice($files, 0, 3) as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $realPath = $file->getRealPath();

            if (! $realPath || ! is_file($realPath)) {
                throw TicketException::because('فایل تصویر بارگذاری‌شده معتبر نیست.');
            }

            $imageInfo = @getimagesize($realPath);
            $mime = is_array($imageInfo) ? strtolower((string) ($imageInfo['mime'] ?? '')) : '';

            if (! isset(self::ALLOWED_MIMES[$mime])) {
                throw TicketException::because('فرمت تصویر مجاز نیست. فقط تصاویر JPG، PNG، WebP و GIF قابل ارسال هستند.');
            }

            $ext = self::ALLOWED_MIMES[$mime];
            $path = self::ROOT_DIR . '/' . $ticketId . '/' . Str::ulid() . '.' . $ext;

            $stream = fopen($realPath, 'rb');

            if ($stream === false) {
                throw TicketException::because('خطا در پردازش فایل تصویر.');
            }

            $disk->put($path, $stream, 'public');

            if (is_resource($stream)) {
                fclose($stream);
            }

            $stored[] = $path;
        }

        return $stored;
    }

    public static function url(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '' || str_contains($path, '..') || ! str_starts_with($path, self::ROOT_DIR . '/')) {
            return null;
        }

        $disk = Storage::disk(self::diskName());

        if (! $disk->exists($path)) {
            return null;
        }

        return $disk->url($path);
    }

    public static function deleteForTicket(int $ticketId): void
    {
        if ($ticketId <= 0) {
            return;
        }

        Storage::disk(self::diskName())->deleteDirectory(self::ROOT_DIR . '/' . $ticketId);
    }

    public static function purgeAll(): void
    {
        Storage::disk(self::diskName())->deleteDirectory(self::ROOT_DIR);
    }
}

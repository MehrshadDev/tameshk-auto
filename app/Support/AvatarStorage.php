<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class AvatarStorage
{
    public static function downloadAndStore(?string $remoteUrl, ?string $existingPath = null): ?string
    {
        $remoteUrl = trim((string) $remoteUrl);

        if ($remoteUrl === '' || ! filter_var($remoteUrl, FILTER_VALIDATE_URL)) {
            return $existingPath;
        }

        $disk = Storage::disk(config('services.google.avatar_disk', 'public'));

        if ($existingPath && ! str_starts_with($existingPath, 'http') && $disk->exists($existingPath)) {
            return $existingPath;
        }

        $downloadUrl = self::rewriteAvatarUrl($remoteUrl);

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Accept' => 'image/*'])
                ->get($downloadUrl);

            if ($response->failed()) {
                return $existingPath;
            }

            $body = $response->body();

            if ($body === '' || strlen($body) > 5 * 1024 * 1024) {
                return $existingPath;
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            $extension = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif') => 'gif',
                default => 'jpg',
            };

            $filename = 'avatars/' . Str::ulid() . '.' . $extension;

            $disk->put($filename, $body, 'public');

            if ($existingPath && ! str_starts_with($existingPath, 'http') && $disk->exists($existingPath)) {
                $disk->delete($existingPath);
            }

            return $filename;
        } catch (Throwable) {
            return $existingPath;
        }
    }

    public static function url(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }

        $disk = Storage::disk(config('services.google.avatar_disk', 'public'));

        if (! $disk->exists($path)) {
            return null;
        }

        return $disk->url($path);
    }

    private static function rewriteAvatarUrl(string $url): string
    {
        $configuredBase = rtrim((string) config('services.google.avatar_base_url', ''), '/');

        if ($configuredBase === '') {
            return $url;
        }

        $parsed = parse_url($url);

        if (! $parsed || empty($parsed['path'])) {
            return $url;
        }

        $path = '/' . ltrim($parsed['path'], '/');
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        return $configuredBase . $path . $query;
    }
}

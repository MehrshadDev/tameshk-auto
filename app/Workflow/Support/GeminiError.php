<?php

namespace App\Workflow\Support;

use Illuminate\Http\Client\Response;

final class GeminiError
{
    public static function describe(Response $response): string
    {
        $status = $response->status();
        $error = (array) $response->json('error', []);
        $googleMessage = trim(strip_tags((string) ($error['message'] ?? '')));
        $reason = collect($error['details'] ?? [])->pluck('reason')->filter()->first();

        $hint = match (true) {
            $reason === 'API_KEY_INVALID' => 'کلید API نامعتبر است. کلید را در اعتبارنامه بررسی کنید یا کلید جدید بسازید.',
            $status === 429 => 'سقف درخواست یا سهمیه‌ی این کلید پر شده است. کمی بعد دوباره تلاش کنید.',
            $status === 404 => 'مدل انتخاب‌شده پیدا نشد. مدل دیگری انتخاب کنید.',
            in_array($status, [401, 403], true) => 'این کلید اجازه‌ی دسترسی به این مدل یا سرویس را ندارد.',
            $status >= 500 => 'سرویس Gemini موقتاً در دسترس نیست. کمی بعد دوباره تلاش کنید.',
            default => 'درخواست توسط Gemini پذیرفته نشد.',
        };

        $detail = $googleMessage !== '' ? $googleMessage : strip_tags($response->body());

        return "خطای Gemini (HTTP {$status}): {$hint}\n" . mb_substr($detail, 0, 500);
    }
}

<?php

namespace App\Workflow\Support;

use App\Models\Credential;
use App\Workflow\Exceptions\NodeExecutionException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

final class GoogleTokenProvider
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function __construct(private readonly Credential $credential)
    {
    }

    public function getValidAccessToken(): string
    {
        $data = $this->credential->data ?? [];
        $accessToken = $data['access_token'] ?? null;
        $expiresAt = $data['expires_at'] ?? null;
        $refreshToken = $data['refresh_token'] ?? null;

        if (! empty($accessToken) && ! empty($expiresAt) && Carbon::parse($expiresAt)->isFuture()) {
            return (string) $accessToken;
        }

        if (empty($refreshToken)) {
            throw NodeExecutionException::because('حساب گوگل متصل نشده است. ابتدا روی دکمه «اتصال به حساب گوگل» در بخش اعتبارنامه کلیک کنید.');
        }

        $clientId = $data['client_id'] ?? null;
        $clientSecret = $data['client_secret'] ?? null;

        if (empty($clientId) || empty($clientSecret)) {
            throw NodeExecutionException::because('اطلاعات Client ID یا Client Secret در اعتبارنامه موجود نیست.');
        }

        try {
            $response = Http::asForm()->timeout(20)->post(self::TOKEN_URL, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);
        } catch (Throwable $e) {
            throw NodeExecutionException::because('خطا در برقراری ارتباط با سرور احراز هویت گوگل: ' . $e->getMessage());
        }

        if ($response->failed()) {
            $errorDesc = data_get($response->json(), 'error_description', $response->body());
            throw NodeExecutionException::because('تمدید نشست گوگل ناموفق بود: ' . $errorDesc);
        }

        $tokenPayload = $response->json();
        $newAccessToken = data_get($tokenPayload, 'access_token');
        $expiresIn = (int) data_get($tokenPayload, 'expires_in', 3500);

        $data['access_token'] = $newAccessToken;
        $data['expires_at'] = now()->addSeconds($expiresIn - 60)->toIso8601String();

        $this->credential->update(['data' => $data]);

        return (string) $newAccessToken;
    }
}

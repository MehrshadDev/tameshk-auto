<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Workflow\Support\GmailClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Throwable;

class GoogleOAuthController extends Controller
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function redirect(Request $request): RedirectResponse
    {
        $credentialId = (int) $request->query('credential_id');
        $credential = Credential::query()
            ->where('id', $credentialId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $clientId = $credential->get('client_id');

        if (empty($clientId)) {
            abort(400, 'فیلد Client ID ذخیره نشده است.');
        }

        $state = Crypt::encryptString(json_encode([
            'credential_id' => $credential->id,
            'user_id' => $request->user()->id,
        ]));

        $redirectUri = $this->getRedirectUri();

        $scopes = [
            'https://mail.google.com/',
            'https://www.googleapis.com/auth/userinfo.email',
        ];

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return redirect()->away(self::AUTH_URL . '?' . $params);
    }

    public function callback(Request $request): Response
    {
        $code = (string) $request->query('code');
        $state = (string) $request->query('state');

        if (empty($code) || empty($state)) {
            return response(
                $this->popupResponse(false, 'کد اعتبارسنجی گوگل دریافت نشد یا لغو شد.')
            );
        }

        try {
            $payload = json_decode(Crypt::decryptString($state), true);
            $credentialId = (int) ($payload['credential_id'] ?? 0);
            $userId = (int) ($payload['user_id'] ?? 0);
        } catch (Throwable) {
            return response(
                $this->popupResponse(false, 'توکن امنیتی State نامعتبر است.')
            );
        }

        $credential = Credential::query()
            ->where('id', $credentialId)
            ->where('user_id', $userId)
            ->first();

        if (! $credential) {
            return response(
                $this->popupResponse(false, 'اعتبارنامه مربوطه یافت نشد.')
            );
        }

        $clientId = $credential->get('client_id');
        $clientSecret = $credential->get('client_secret');

        try {
            $tokenRes = Http::asForm()->timeout(20)->post(self::TOKEN_URL, [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $this->getRedirectUri(),
                'grant_type' => 'authorization_code',
            ]);
        } catch (Throwable $e) {
            return response(
                $this->popupResponse(false, 'خطا در ارتباط با سرور گوگل: ' . $e->getMessage())
            );
        }

        if ($tokenRes->failed()) {
            $desc = data_get($tokenRes->json(), 'error_description', $tokenRes->body());
            return response(
                $this->popupResponse(false, 'دریافت توکن گوگل با خطا مواجه شد: ' . $desc)
            );
        }

        $tokenData = $tokenRes->json();
        $accessToken = data_get($tokenData, 'access_token');
        $refreshToken = data_get($tokenData, 'refresh_token', $credential->get('refresh_token'));
        $expiresIn = (int) data_get($tokenData, 'expires_in', 3600);

        $credentialData = $credential->data ?? [];
        $credentialData['access_token'] = $accessToken;
        if (! empty($refreshToken)) {
            $credentialData['refresh_token'] = $refreshToken;
        }
        $credentialData['expires_at'] = now()->addSeconds($expiresIn - 60)->toIso8601String();

        $credential->update(['data' => $credentialData]);

        try {
            $client = new GmailClient($credential);
            $profile = $client->getProfile();
            $email = data_get($profile, 'emailAddress', '');
            if ($email !== '') {
                $credentialData['connected_email'] = $email;
                $credential->update(['data' => $credentialData]);
            }
        } catch (Throwable) {
        }

        return response(
            $this->popupResponse(true, 'حساب جیمیل با موفقیت متصل شد!')
        );
    }

    private function getRedirectUri(): string
    {
        if ($configured = config('services.google.redirect_uri')) {
            return rtrim($configured, '/');
        }

        $base = rtrim((string) (config('app.url') ?: url('/')), '/');

        return $base . route('oauth.google.callback', [], false);
    }

    private function popupResponse(bool $success, string $message): string
    {
        $statusStr = $success ? 'success' : 'error';

        return <<<HTML
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>احراز هویت گوگل</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #0a0a0a; color: #fff; text-align: center; }
        .box { padding: 2rem; border-radius: 1rem; border: 1px solid #2e2e2e; background: #141414; max-width: 400px; }
        .msg { font-size: 0.95rem; line-height: 1.6; margin-top: 1rem; }
    </style>
</head>
<body>
<div class="box">
    <h3>اتصال حساب جیمیل</h3>
    <p class="msg">{$message}</p>
    <p style="font-size: 0.8rem; opacity: 0.6; margin-top: 1.5rem;">این پنجره به‌صورت خودکار بسته می‌شود...</p>
</div>
<script>
    if (window.opener) {
        window.opener.postMessage({ type: 'GMAIL_OAUTH_RESULT', status: '{$statusStr}', message: '{$message}' }, '*');
        setTimeout(() => window.close(), 1500);
    }
</script>
</body>
</html>
HTML;
    }
}

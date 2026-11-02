<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $authBase = rtrim(config('services.google.auth_base_url', 'https://accounts.google.com'), '/');

        if (empty($clientId)) {
            abort(500, 'اطلاعات کلاینت گوگل تنظیم نشده است.');
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect()->away($authBase . '/o/oauth2/v2/auth?' . $params);
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = (string) $request->query('code');
        $state = (string) $request->query('state');
        $savedState = (string) $request->session()->pull('google_oauth_state', '');

        if (empty($code) || empty($state) || empty($savedState) || ! hash_equals($savedState, $state)) {
            return redirect()->route('login')->with('auth_error', 'توکن امنیتی نشست گوگل منقضی شده یا نامعتبر است.');
        }

        $oauthBase = rtrim(config('services.google.oauth_base_url', 'https://oauth2.googleapis.com'), '/');
        $apiBase = rtrim(config('services.google.api_base_url', 'https://www.googleapis.com'), '/');

        try {
            $tokenRes = Http::asForm()->timeout(20)->post($oauthBase . '/token', [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => $this->getRedirectUri(),
                'grant_type' => 'authorization_code',
            ]);

            if ($tokenRes->failed()) {
                return redirect()->route('login')->with('auth_error', 'دریافت تاییدیه از گوگل با خطا مواجه شد.');
            }

            $accessToken = data_get($tokenRes->json(), 'access_token');

            if (empty($accessToken)) {
                return redirect()->route('login')->with('auth_error', 'توکن دسترسی گوگل معتبر نیست.');
            }

            $userRes = Http::withToken($accessToken)->timeout(15)->get($apiBase . '/oauth2/v3/userinfo');

            if ($userRes->failed()) {
                return redirect()->route('login')->with('auth_error', 'دریافت مشخصات کاربری از گوگل با خطا مواجه شد.');
            }

            $googleUser = $userRes->json();

            $isVerified = filter_var(data_get($googleUser, 'email_verified'), FILTER_VALIDATE_BOOLEAN);

            if (! $isVerified) {
                return redirect()->route('login')->with('auth_error', 'ایمیل این حساب گوگل تایید نشده است.');
            }

            $user = $this->findOrCreateUser($googleUser);

            Auth::login($user, remember: true);

            $request->session()->regenerate();
            $request->session()->regenerateToken();

            return redirect()->intended(route('app.workflows.index'));
        } catch (Throwable) {
            return redirect()->route('login')->with('auth_error', 'خطا در ارتباط با سرور گوگل. لطفاً دوباره تلاش کنید.');
        }
    }

    public function oneTap(Request $request): JsonResponse
    {
        $credential = (string) $request->input('credential');
        $expectedClientId = (string) config('services.google.client_id');

        if (empty($credential) || empty($expectedClientId)) {
            return response()->json(['message' => 'اطلاعات ورود دریافت نشد یا تنظیمات گوگل ناقص است.'], 400);
        }

        $oauthBase = rtrim(config('services.google.oauth_base_url', 'https://oauth2.googleapis.com'), '/');

        try {
            $response = Http::timeout(15)->get($oauthBase . '/tokeninfo', [
                'id_token' => $credential,
            ]);

            if ($response->failed()) {
                return response()->json(['message' => 'توکن هویتی گوگل معتبر نیست.'], 401);
            }

            $payload = (array) $response->json();

            if (data_get($payload, 'aud') !== $expectedClientId) {
                return response()->json(['message' => 'مخاطب توکن گوگل همخوانی ندارد.'], 403);
            }

            $isVerified = filter_var(data_get($payload, 'email_verified'), FILTER_VALIDATE_BOOLEAN);

            if (! $isVerified) {
                return response()->json(['message' => 'ایمیل این حساب گوگل تایید نشده است.'], 403);
            }

            $user = $this->findOrCreateUser([
                'sub' => data_get($payload, 'sub'),
                'email' => data_get($payload, 'email'),
                'name' => data_get($payload, 'name'),
                'picture' => data_get($payload, 'picture'),
            ]);

            Auth::login($user, remember: true);

            $request->session()->regenerate();
            $request->session()->regenerateToken();

            return response()->json([
                'status' => 'success',
                'redirect' => route('app.workflows.index'),
            ]);
        } catch (Throwable) {
            return response()->json(['message' => 'خطا در تایید هویت با سرور گوگل.'], 500);
        }
    }

    private function findOrCreateUser(array $data): User
    {
        $googleId = trim((string) data_get($data, 'sub'));
        $rawEmail = trim((string) data_get($data, 'email'));
        $name = trim(strip_tags((string) data_get($data, 'name', '')));
        $avatar = data_get($data, 'picture');

        if (empty($googleId) || empty($rawEmail) || ! filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
            abort(400, 'اطلاعات دریافت شده از گوگل نامعتبر است.');
        }

        $email = $this->normalizeEmail($rawEmail);

        if ($name === '') {
            $name = explode('@', $rawEmail)[0] ?: 'کاربر';
        }

        $user = User::query()->where('google_id', $googleId)->first();

        if ($user) {
            if ($avatar && empty($user->avatar)) {
                $user->update(['avatar' => $avatar]);
            }
            return $user;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $updateData = ['google_id' => $googleId];

            if ($avatar && empty($user->avatar)) {
                $updateData['avatar'] = $avatar;
            }

            if (! $user->email_verified_at) {
                $updateData['email_verified_at'] = now();
            }

            $user->update($updateData);

            return $user;
        }

        return User::create([
            'name' => mb_substr($name, 0, 50),
            'email' => $email,
            'google_id' => $googleId,
            'avatar' => $avatar,
            'password' => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
        ]);
    }

    private function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));

        if (! str_contains($email, '@')) {
            return $email;
        }

        [$username, $domain] = explode('@', $email, 2);

        if ($domain === 'gmail.com') {
            $username = explode('+', $username)[0];
            $username = str_replace('.', '', $username);
        }

        return $username . '@' . $domain;
    }

    private function getRedirectUri(): string
    {
        if ($configured = config('services.google.redirect_uri')) {
            return rtrim($configured, '/');
        }

        return route('auth.google.callback');
    }
}

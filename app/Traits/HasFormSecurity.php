<?php

namespace App\Traits;

use Illuminate\Support\Facades\RateLimiter;

trait HasFormSecurity
{
    public int $form_load_time;
    public string $honeypot = '';

    protected function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));

        if (!str_contains($email, '@')) {
            return $email;
        }

        [$username, $domain] = explode('@', $email, 2);

        if (in_array($domain, ['gmail.com'])) {
            $username = explode('+', $username)[0];
            $username = str_replace('.', '', $username);
        }

        return $username . '@' . $domain;
    }

    protected function securityChecks(string $actionKey = 'auth', int $maxAttempts = 5): bool
    {
        $ipKey = $actionKey . ':ip:' . request()->ip();

        if (RateLimiter::tooManyAttempts($ipKey, $maxAttempts)) {
            $this->handleRateLimitError($ipKey);
            return false;
        }

        if (!empty($this->email)) {
            $email = $this->normalizeEmail($this->email);
            $emailKey = $actionKey . ':email:' . $email;

            if (RateLimiter::tooManyAttempts($emailKey, $maxAttempts)) {
                $this->handleRateLimitError($emailKey);
                return false;
            }
        }

        if (!empty($this->honeypot)) {
            $this->addError('email', 'درخواست نامعتبر شناسایی شد. 🛡️');
            return false;
        }

        $duration = time() - $this->form_load_time;
        if ($duration < 2) {
            $this->addError('email', 'کمی آهسته‌تر! 🐢');
            return false;
        }

        return true;
    }

    protected function hitRateLimiter(string $actionKey = 'auth', int $decaySeconds = 60): void
    {
        RateLimiter::hit($actionKey . ':ip:' . request()->ip(), $decaySeconds);

        if (!empty($this->email)) {
            $email = $this->normalizeEmail($this->email);
            RateLimiter::hit($actionKey . ':email:' . $email, $decaySeconds);
        }
    }

    protected function clearRateLimiter(string $actionKey = 'auth'): void
    {
        RateLimiter::clear($actionKey . ':ip:' . request()->ip());

        if (!empty($this->email)) {
            RateLimiter::clear($actionKey . ':email:' . $this->email);
            RateLimiter::clear($actionKey . ':email:' . $this->normalizeEmail($this->email));
        }
    }

    private function handleRateLimitError(string $key): void
    {
        $seconds = RateLimiter::availableIn($key);
        $minutes = ceil($seconds / 60);
        $this->addError('email', "بیش از حد تلاش کردید. لطفاً $minutes دقیقه دیگر دوباره امتحان کنید. ⏳");
    }
}

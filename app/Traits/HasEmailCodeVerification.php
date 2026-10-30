<?php

namespace App\Traits;

use App\Mail\VerifyEmailCode;
use App\Models\EmailVerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

trait HasEmailCodeVerification
{
    public int $step = 1;

    public string $email = '';
    public string $verificationCode = '';
    public ?string $codeExpiresAt = null;
    public ?string $resendAvailableAt = null;
    public ?string $resendStatus = null;

    abstract protected function resendMessage(): string;

    protected function extraReset(): void
    {
    }

    protected function issueCode(string $email, string $field): bool
    {
        $limiter = "resend-code:$email";

        if (RateLimiter::tooManyAttempts($limiter, 5)) {
            $seconds = RateLimiter::availableIn($limiter);
            $this->addError($field, "تعداد درخواست‌های ارسال کد بیش از حد مجاز است. لطفاً {$seconds} ثانیه دیگر تلاش کنید.");
            return false;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(5);

        EmailVerificationCode::where('email', $email)->delete();

        EmailVerificationCode::create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
        ]);

        try {
            Mail::to($email)->send(new VerifyEmailCode($code, 5));
        } catch (Throwable $e) {
            report($e);
            EmailVerificationCode::where('email', $email)->delete();
            $this->addError($field, 'خطا در ارسال ایمیل. لطفاً دوباره تلاش کنید.');
            return false;
        }

        RateLimiter::hit($limiter, 600);

        $this->codeExpiresAt = $expiresAt->toIso8601String();
        $this->resendAvailableAt = now()->addSeconds(60)->toIso8601String();

        return true;
    }

    protected function startVerification(string $email): void
    {
        $this->email = $email;
        $this->resendStatus = null;
        $this->step = 2;
        $this->verificationCode = '';
        $this->form_load_time = time();
    }

    protected function checkCode(string $limiter, string $expiredMessage): ?EmailVerificationCode
    {
        if (! $this->securityChecks($limiter, 10)) {
            return null;
        }

        $record = EmailVerificationCode::where('email', $this->email)->first();

        if (! $record || $record->isExpired()) {
            $this->addError('verificationCode', $expiredMessage);
            return null;
        }

        if ($record->attempts >= 5) {
            $this->addError('verificationCode', 'تعداد تلاش‌های شما بیش از حد مجاز است. لطفاً کد جدید دریافت کنید.');
            return null;
        }

        if (! Hash::check($this->verificationCode, $record->code_hash)) {
            $record->increment('attempts');
            $this->hitRateLimiter($limiter, 60);
            $this->addError('verificationCode', 'کد تایید اشتباه است.');
            return null;
        }

        return $record;
    }

    protected function finishVerification(string $limiter, EmailVerificationCode $record): void
    {
        $record->delete();

        RateLimiter::clear("$limiter:ip:" . request()->ip());
        RateLimiter::clear("$limiter:email:{$this->email}");
        RateLimiter::clear("resend-code:{$this->email}");
    }

    protected function resetFlow(): void
    {
        $this->step = 1;
        $this->verificationCode = '';
        $this->codeExpiresAt = null;
        $this->resendAvailableAt = null;
        $this->resendStatus = null;
        $this->extraReset();
        $this->resetErrorBag();
        $this->form_load_time = time();
    }

    public function resendCode(): void
    {
        $this->resetErrorBag();
        $this->resendStatus = null;

        $record = EmailVerificationCode::where('email', $this->email)->first();

        if ($record && $record->created_at->diffInSeconds(now()) < 60) {
            $wait = (int) (60 - $record->created_at->diffInSeconds(now()));
            $this->addError('verificationCode', "لطفاً {$wait} ثانیه دیگر برای ارسال مجدد صبر کنید.");
            return;
        }

        if (! $this->issueCode($this->email, 'verificationCode')) {
            return;
        }

        $this->verificationCode = '';
        $this->resendStatus = $this->resendMessage();
    }

    public function editEmail(): void
    {
        $this->resetFlow();
    }

    public function handleExpiry(): void
    {
        $this->resetFlow();
        $this->addError('email', 'زمان وارد کردن کد به پایان رسید. لطفاً دوباره تلاش کنید.');
    }
}

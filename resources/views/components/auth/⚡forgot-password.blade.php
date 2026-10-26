<?php

use App\Mail\VerifyEmailCode;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Traits\HasFormSecurity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Component;

new class extends Component
{
    use HasFormSecurity;

    public int $step = 1;

    public string $email = '';
    public string $verificationCode = '';
    public ?string $codeExpiresAt = null;
    public ?string $resendAvailableAt = null;
    public ?string $resendStatus = null;

    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->form_load_time = time();
    }

    protected function rules(): array
    {
        if ($this->step === 1) {
            return [
                'email' => ['required', 'string', 'email', 'max:255', 'exists:users,email'],
            ];
        }

        if ($this->step === 2) {
            return [
                'verificationCode' => ['required', 'string', 'digits:6'],
            ];
        }

        return [
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required' => 'وارد کردن ایمیل الزامی است.',
            'email.email' => 'فرمت ایمیل وارد شده صحیح نیست.',
            'email.exists' => 'کاربری با این ایمیل یافت نشد.',
            'email.max' => 'ایمیل نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد.',
            'verificationCode.required' => 'وارد کردن کد تایید الزامی است.',
            'verificationCode.digits' => 'کد تایید باید ۶ رقم باشد.',
            'password.required' => 'وارد کردن رمز عبور جدید الزامی است.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.letters' => 'رمز عبور باید شامل حروف باشد.',
            'password.numbers' => 'رمز عبور باید شامل اعداد باشد.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'email' => 'ایمیل',
            'verificationCode' => 'کد تایید',
            'password' => 'رمز عبور',
        ];
    }

    public function sendCode(): void
    {
        $this->validate();

        if (! $this->securityChecks('forgot-password', 5)) {
            return;
        }

        $normalizedEmail = $this->normalizeEmail($this->email);

        if (RateLimiter::tooManyAttempts('resend-code:' . $normalizedEmail, 5)) {
            $seconds = RateLimiter::availableIn('resend-code:' . $normalizedEmail);
            $this->addError('email', "تعداد درخواست‌های ارسال کد بیش از حد مجاز است. لطفاً {$seconds} ثانیه دیگر تلاش کنید.");
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(5);

        EmailVerificationCode::where('email', $normalizedEmail)->delete();

        EmailVerificationCode::create([
            'email' => $normalizedEmail,
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
        ]);

        try {
            Mail::to($normalizedEmail)->send(new VerifyEmailCode($code, 5));
        } catch (\Throwable $e) {
            report($e);
            EmailVerificationCode::where('email', $normalizedEmail)->delete();
            $this->addError('email', 'خطا در ارسال ایمیل. لطفاً دوباره تلاش کنید.');
            return;
        }

        RateLimiter::hit('resend-code:' . $normalizedEmail, 600);

        $this->hitRateLimiter('forgot-password', 300);
        $this->clearRateLimiter('forgot-password');

        $this->email = $normalizedEmail;
        $this->codeExpiresAt = $expiresAt->toIso8601String();
        $this->resendAvailableAt = now()->addSeconds(60)->toIso8601String();
        $this->resendStatus = null;
        $this->step = 2;
        $this->verificationCode = '';
        $this->form_load_time = time();
    }

    public function verifyCode(): void
    {
        $this->resendStatus = null;
        $this->validate();

        if (! $this->securityChecks('verify-reset-code', 10)) {
            return;
        }

        $record = EmailVerificationCode::where('email', $this->email)->first();

        if (! $record || $record->isExpired()) {
            $this->addError('verificationCode', 'کد تایید منقضی شده است. لطفاً دوباره درخواست دهید.');
            return;
        }

        if ($record->attempts >= 5) {
            $this->addError('verificationCode', 'تعداد تلاش‌های شما بیش از حد مجاز است. لطفاً کد جدید دریافت کنید.');
            return;
        }

        if (! Hash::check($this->verificationCode, $record->code_hash)) {
            $record->increment('attempts');
            $this->hitRateLimiter('verify-reset-code', 60);
            $this->addError('verificationCode', 'کد تایید اشتباه است.');
            return;
        }

        $user = User::where('email', $this->email)->first();

        if (! $user) {
            $this->addError('verificationCode', 'کاربری با این ایمیل یافت نشد.');
            return;
        }

        $record->delete();

        RateLimiter::clear('verify-reset-code:ip:' . request()->ip());
        RateLimiter::clear('verify-reset-code:email:' . $this->email);
        RateLimiter::clear('resend-code:' . $this->email);

        $token = Password::broker()->createToken($user);
        session(['reset_token_' . $this->email => $token]);

        $this->codeExpiresAt = null;
        $this->resendAvailableAt = null;
        $this->step = 3;
        $this->verificationCode = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetErrorBag();
        $this->form_load_time = time();
    }

    public function resetPassword(): void
    {
        $this->validate();

        if (! $this->securityChecks('reset-password', 5)) {
            return;
        }

        $token = session('reset_token_' . $this->email);

        if (! $token) {
            $this->addError('password', 'نشست شما منقضی شده است. لطفاً دوباره تلاش کنید.');
            $this->handleExpiry();
            return;
        }

        $status = Password::broker()->reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $token,
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->forget('reset_token_' . $this->email);

            $user = User::where('email', $this->email)->first();

            Auth::login($user, remember: true);

            session()->regenerate();
            session()->regenerateToken();

            $this->redirect(route('app.workflows.index'), navigate: true);
            return;
        }

        $this->addError('password', 'خطا در تغییر رمز عبور. لطفاً دوباره تلاش کنید.');
    }

    public function editEmail(): void
    {
        $this->step = 1;
        $this->verificationCode = '';
        $this->codeExpiresAt = null;
        $this->resendAvailableAt = null;
        $this->resendStatus = null;
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetErrorBag();
        $this->form_load_time = time();
    }

    public function resendCode(): void
    {
        $this->resetErrorBag();
        $this->resendStatus = null;

        if (RateLimiter::tooManyAttempts('resend-code:' . $this->email, 5)) {
            $seconds = RateLimiter::availableIn('resend-code:' . $this->email);
            $this->addError('verificationCode', "تعداد درخواست‌های ارسال مجدد بیش از حد مجاز است. لطفاً {$seconds} ثانیه دیگر تلاش کنید.");
            return;
        }

        $record = EmailVerificationCode::where('email', $this->email)->first();

        if ($record && $record->created_at->diffInSeconds(now()) < 60) {
            $wait = (int) (60 - $record->created_at->diffInSeconds(now()));
            $this->addError('verificationCode', "لطفاً {$wait} ثانیه دیگر برای ارسال مجدد صبر کنید.");
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(5);

        EmailVerificationCode::where('email', $this->email)->delete();

        EmailVerificationCode::create([
            'email' => $this->email,
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
        ]);

        try {
            Mail::to($this->email)->send(new VerifyEmailCode($code, 5));
        } catch (\Throwable $e) {
            report($e);
            EmailVerificationCode::where('email', $this->email)->delete();
            $this->addError('verificationCode', 'خطا در ارسال ایمیل. لطفاً دوباره تلاش کنید.');
            return;
        }

        RateLimiter::hit('resend-code:' . $this->email, 600);

        $this->codeExpiresAt = $expiresAt->toIso8601String();
        $this->resendAvailableAt = now()->addSeconds(60)->toIso8601String();
        $this->verificationCode = '';
        $this->resendStatus = 'کد بازیابی جدید با موفقیت ارسال شد.';
    }

    public function handleExpiry(): void
    {
        $this->step = 1;
        $this->verificationCode = '';
        $this->codeExpiresAt = null;
        $this->resendAvailableAt = null;
        $this->resendStatus = null;
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetErrorBag();
        $this->addError('email', 'زمان وارد کردن کد به پایان رسید. لطفاً دوباره تلاش کنید.');
        $this->form_load_time = time();
    }
};
?>

<div>
    @if ($step === 1)
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-content-main">فراموشی رمز عبور</h1>
            <p class="text-sm text-content-muted mt-1">ایمیل خود را وارد کنید تا کد بازیابی برایتان ارسال شود</p>
        </div>

        <form wire:submit="sendCode" class="space-y-4">
            <div class="sr-only" aria-hidden="true">
                <label for="website_url">وب‌سایت</label>
                <input type="text" id="website_url" wire:model="honeypot" tabindex="-1" autocomplete="off">
            </div>

            <x-ui.input
                name="email"
                type="email"
                wire:model="email"
                label="ایمیل"
                placeholder="example@gmail.com"
                autocomplete="email"
                autofocus
            />

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="sendCode"
                class="w-full py-2.5 rounded-xl bg-primary text-white font-medium
                       hover:opacity-90 active:scale-[0.99] cursor-pointer
                       transition disabled:opacity-60 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove wire:target="sendCode">ارسال کد بازیابی</span>
                <span wire:loading wire:target="sendCode">در حال ارسال...</span>
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-content-muted">
            رمز عبور خود را به یاد آوردید؟
            <a href="{{ route('login') }}" wire:navigate class="text-primary hover:underline font-medium cursor-pointer">
                وارد شوید
            </a>
        </p>

    @elseif ($step === 2)
        <div
            wire:key="verify-{{ md5(($codeExpiresAt ?? '') . '|' . ($resendAvailableAt ?? '')) }}"
            x-data="{
                remaining: 0,
                resendRemaining: 0,
                timerId: null,
                expired: false,
                init() {
                    const codeStr = '{{ $codeExpiresAt }}';
                    const resendStr = '{{ $resendAvailableAt }}';
                    const expiresAt = codeStr ? new Date(codeStr).getTime() : 0;
                    const resendAt = resendStr ? new Date(resendStr).getTime() : 0;

                    const tick = () => {
                        const now = Date.now();
                        this.remaining = Math.max(0, Math.floor((expiresAt - now) / 1000));
                        this.resendRemaining = Math.max(0, Math.floor((resendAt - now) / 1000));

                        if (expiresAt > 0 && this.remaining <= 0 && !this.expired) {
                            this.expired = true;
                            clearInterval(this.timerId);
                            $wire.handleExpiry();
                        }
                    };

                    tick();
                    this.timerId = setInterval(tick, 250);
                },
                destroy() {
                    if (this.timerId) clearInterval(this.timerId);
                },
                format() {
                    const m = String(Math.floor(this.remaining / 60)).padStart(2, '0');
                    const s = String(this.remaining % 60).padStart(2, '0');
                    return m + ':' + s;
                }
            }"
        >
            <div class="text-center mb-6">
                <h1 class="text-xl font-bold text-content-main">بازیابی رمز عبور 🔐</h1>
                <p class="text-sm text-content-muted mt-1">
                    کد ۶ رقمی ارسال شده به
                    <span class="font-medium text-content-main" dir="ltr">{{ $email }}</span>
                    را وارد کنید
                </p>
            </div>

            <div class="mb-6 flex flex-col items-center gap-2">
                <div class="text-3xl font-bold tabular-nums tracking-wider" dir="ltr">
                    <span :class="remaining <= 60 ? 'text-primary' : 'text-content-main'" x-text="format()"></span>
                </div>
                <p class="text-xs text-content-muted">زمان باقی‌مانده تا انقضای کد</p>
            </div>

            @if ($resendStatus)
                <div
                    wire:key="resend-toast-{{ md5($resendStatus . ($codeExpiresAt ?? '')) }}"
                    x-data="{
                        show: true,
                        init() {
                            setTimeout(() => {
                                this.show = false;
                                setTimeout(() => $wire.set('resendStatus', null), 350);
                            }, 5000);
                        }
                    }"
                    x-show="show"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="mb-3 p-3 rounded-xl text-sm text-center
                           backdrop-blur-sm
                           text-emerald-700 bg-emerald-50 border border-emerald-200
                           dark:text-emerald-300 dark:bg-emerald-500/10 dark:border-emerald-500/30
                           dark:shadow-[0_0_24px_-6px_rgba(16,185,129,0.35)]"
                >
                    {{ $resendStatus }}
                </div>
            @endif

            <form wire:submit="verifyCode" class="space-y-4">
                <x-ui.input
                    name="verificationCode"
                    wire:model="verificationCode"
                    label="کد تایید"
                    placeholder="۱۲۳۴۵۶"
                    :smart-dir="false"
                    dir="ltr"
                    inputmode="numeric"
                    maxlength="6"
                    autocomplete="one-time-code"
                    autofocus
                    class="py-3 text-center text-2xl font-bold tracking-[0.5em] placeholder:text-content-muted/40"
                />

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="verifyCode"
                    class="w-full py-2.5 rounded-xl bg-primary text-white font-medium
                           hover:opacity-90 active:scale-[0.99] cursor-pointer
                           transition disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove wire:target="verifyCode">تایید کد</span>
                    <span wire:loading wire:target="verifyCode">در حال بررسی...</span>
                </button>
            </form>

            <div class="mt-5 flex items-center justify-between text-sm">
                <button
                    type="button"
                    wire:click="editEmail"
                    class="text-content-muted hover:text-content-main transition cursor-pointer"
                >
                    ویرایش ایمیل
                </button>

                <button
                    type="button"
                    wire:click="resendCode"
                    wire:loading.attr="disabled"
                    wire:target="resendCode"
                    x-bind:disabled="resendRemaining > 0"
                    class="text-sm font-medium transition cursor-pointer disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove wire:target="resendCode">
                        <span x-show="resendRemaining > 0" x-cloak class="text-content-muted">
                            ارسال مجدد (<span x-text="resendRemaining"></span>)
                        </span>
                        <span x-show="resendRemaining <= 0" x-cloak class="text-primary hover:underline">
                            ارسال مجدد کد
                        </span>
                    </span>
                    <span wire:loading wire:target="resendCode" class="text-primary">
                        در حال ارسال...
                    </span>
                </button>
            </div>
        </div>

    @elseif ($step === 3)
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-content-main">تعیین رمز عبور جدید 🔑</h1>
            <p class="text-sm text-content-muted mt-1">یک رمز عبور جدید و امن برای حساب خود انتخاب کنید</p>
        </div>

        <form wire:submit="resetPassword" class="space-y-4">
            <x-ui.input
                name="password"
                type="password"
                wire:model="password"
                label="رمز عبور جدید"
                placeholder="حداقل ۸ کاراکتر شامل حروف و اعداد"
                autocomplete="new-password"
                autofocus
            />

            <x-ui.input
                name="password_confirmation"
                type="password"
                wire:model="password_confirmation"
                label="تکرار رمز عبور جدید"
                placeholder="رمز عبور را دوباره وارد کنید"
                autocomplete="new-password"
            />

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="resetPassword"
                class="w-full py-2.5 rounded-xl bg-primary text-white font-medium
                       hover:opacity-90 active:scale-[0.99] cursor-pointer
                       transition disabled:opacity-60 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove wire:target="resetPassword">تغییر رمز و ورود</span>
                <span wire:loading wire:target="resetPassword">در حال ذخیره...</span>
            </button>
        </form>
    @endif
</div>

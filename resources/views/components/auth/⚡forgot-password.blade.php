<?php

use App\Models\User;
use App\Traits\HasEmailCodeVerification;
use App\Traits\HasFormSecurity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Component;

new class extends Component
{
    use HasFormSecurity, HasEmailCodeVerification;

    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->form_load_time = time();
    }

    protected function rules(): array
    {
        return match ($this->step) {
            1 => ['email' => ['required', 'string', 'email', 'max:255', 'exists:users,email']],
            2 => ['verificationCode' => ['required', 'string', 'digits:6']],
            default => ['password' => ['required', 'string', 'confirmed', PasswordRule::min(8)->letters()->numbers()]],
        };
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

    protected function resendMessage(): string
    {
        return 'کد بازیابی جدید با موفقیت ارسال شد.';
    }

    protected function extraReset(): void
    {
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function sendCode(): void
    {
        $this->validate();

        if (! $this->securityChecks('forgot-password', 5)) {
            return;
        }

        $email = $this->normalizeEmail($this->email);

        if (! $this->issueCode($email, 'email')) {
            return;
        }

        $this->hitRateLimiter('forgot-password', 300);
        $this->clearRateLimiter('forgot-password');
        $this->startVerification($email);
    }

    public function verifyCode(): void
    {
        $this->resendStatus = null;
        $this->validate();

        $record = $this->checkCode('verify-reset-code', 'کد تایید منقضی شده است. لطفاً دوباره درخواست دهید.');

        if (! $record) {
            return;
        }

        $user = User::where('email', $this->email)->first();

        if (! $user) {
            $this->addError('verificationCode', 'کاربری با این ایمیل یافت نشد.');
            return;
        }

        $this->finishVerification('verify-reset-code', $record);

        session(['reset_token_' . $this->email => Password::broker()->createToken($user)]);

        $this->codeExpiresAt = null;
        $this->resendAvailableAt = null;
        $this->step = 3;
        $this->verificationCode = '';
        $this->extraReset();
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
            $this->handleExpiry();
            $this->resetErrorBag();
            $this->addError('email', 'نشست شما منقضی شده است. لطفاً دوباره تلاش کنید.');
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
                $user->forceFill(['password' => Hash::make($password)])->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('password', 'خطا در تغییر رمز عبور. لطفاً دوباره تلاش کنید.');
            return;
        }

        session()->forget('reset_token_' . $this->email);

        Auth::login(User::where('email', $this->email)->first(), remember: true);

        session()->regenerate();
        session()->regenerateToken();

        $this->redirect(route('app.workflows.index'), navigate: true);
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
            <x-auth.honeypot/>

            <x-ui.input name="email" type="email" wire:model="email" label="ایمیل" placeholder="example@gmail.com" autocomplete="email" autofocus/>

            <x-auth.submit target="sendCode" label="ارسال کد بازیابی" loading="در حال ارسال..."/>
        </form>

        <p class="mt-6 text-center text-sm text-content-muted">
            رمز عبور خود را به یاد آوردید؟
            <a href="{{ route('login') }}" wire:navigate class="text-primary hover:underline font-medium cursor-pointer">وارد شوید</a>
        </p>
    @elseif ($step === 2)
        <x-auth.verify-step
            title="بازیابی رمز عبور 🔐"
            submit="تایید کد"
            :email="$email"
            :expires-at="$codeExpiresAt"
            :resend-at="$resendAvailableAt"
            :status="$resendStatus"
        />
    @else
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-content-main">تعیین رمز عبور جدید 🔑</h1>
            <p class="text-sm text-content-muted mt-1">یک رمز عبور جدید و امن برای حساب خود انتخاب کنید</p>
        </div>

        <form wire:submit="resetPassword" class="space-y-4">
            <x-ui.input name="password" type="password" wire:model="password" label="رمز عبور جدید" placeholder="حداقل ۸ کاراکتر شامل حروف و اعداد" autocomplete="new-password" autofocus/>

            <x-ui.input name="password_confirmation" type="password" wire:model="password_confirmation" label="تکرار رمز عبور جدید" placeholder="رمز عبور را دوباره وارد کنید" autocomplete="new-password"/>

            <x-auth.submit target="resetPassword" label="تغییر رمز و ورود" loading="در حال ذخیره..."/>
        </form>
    @endif
</div>

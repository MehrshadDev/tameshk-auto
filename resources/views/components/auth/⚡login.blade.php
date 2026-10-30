<?php

use App\Traits\HasFormSecurity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component {
    use HasFormSecurity;

    public string $email = '';
    public string $password = '';

    public function mount(): void
    {
        $this->form_load_time = time();
    }

    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'max:250'],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required' => 'وارد کردن ایمیل الزامی است.',
            'email.string' => 'ایمیل باید یک متن معتبر باشد.',
            'email.email' => 'فرمت ایمیل وارد شده صحیح نیست.',
            'email.max' => 'ایمیل نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد.',
            'password.required' => 'وارد کردن رمز عبور الزامی است.',
            'password.string' => 'رمز عبور باید یک متن معتبر باشد.',
            'password.min' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
            'password.max' => 'رمز عبور نمی‌تواند بیشتر از ۲۵۰ کاراکتر باشد.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'email' => 'ایمیل',
            'password' => 'رمز عبور',
        ];
    }

    public function login(): void
    {
        $this->validate();

        if (!$this->securityChecks('login', 5)) {
            return;
        }

        $normalizedEmail = $this->normalizeEmail($this->email);

        if (!Auth::attempt([
            'email' => $normalizedEmail,
            'password' => $this->password,
        ], remember: true)) {

            $this->hitRateLimiter('login');

            throw ValidationException::withMessages([
                'email' => 'ایمیل یا رمز عبور اشتباه است.',
            ]);
        }

        RateLimiter::clear('login:ip:' . request()->ip());
        RateLimiter::clear('login:email:' . $this->email);
        RateLimiter::clear('login:email:' . $normalizedEmail);

        session()->regenerate();
        session()->regenerateToken();

        $this->redirect(route('app.workflows.index'), navigate: true);
    }
};
?>

<div>
    <div class="text-center mb-6">
        <h1 class="text-xl font-bold text-content-main">خوش آمدید 👋</h1>
        <p class="text-sm text-content-muted mt-1">برای ادامه وارد حساب خود شوید</p>
    </div>

    <form wire:submit="login" class="space-y-4">
        <x-auth.honeypot/>

        <x-ui.input name="email" type="email" wire:model="email" label="ایمیل" placeholder="example@gmail.com" autocomplete="email" autofocus/>

        <div>
            <x-ui.input name="password" type="password" wire:model="password" label="رمز عبور" placeholder="••••••••" autocomplete="current-password"/>

            <div class="mt-2 text-start">
                <a href="{{ route('password.request') }}" wire:navigate class="text-xs text-primary hover:underline font-medium cursor-pointer">
                    رمز عبور را فراموش کرده‌اید؟
                </a>
            </div>
        </div>

        <x-auth.submit target="login" label="ورود" loading="در حال ورود..."/>
    </form>

    <p class="mt-6 text-center text-sm text-content-muted">
        حساب ندارید؟
        <a href="{{ route('register') }}" wire:navigate class="text-primary hover:underline font-medium cursor-pointer">
            ثبت‌نام کنید
        </a>
    </p>
</div>

@props(['title', 'submit', 'email', 'expiresAt' => null, 'resendAt' => null, 'status' => null])

<div
    wire:key="verify-{{ md5(($expiresAt ?? '') . '|' . ($resendAt ?? '')) }}"
    x-data="{
        remaining: 0,
        resendRemaining: 0,
        timerId: null,
        expired: false,
        init() {
            const expiresAt = @js($expiresAt) ? new Date(@js($expiresAt)).getTime() : 0;
            const resendAt = @js($resendAt) ? new Date(@js($resendAt)).getTime() : 0;

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
        <h1 class="text-xl font-bold text-content-main">{{ $title }}</h1>
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

    @if ($status)
        <div
            wire:key="resend-toast-{{ md5($status . ($expiresAt ?? '')) }}"
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
            class="mb-3 p-3 rounded-xl text-sm text-center backdrop-blur-sm text-emerald-700 bg-emerald-50 border border-emerald-200 dark:text-emerald-300 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:shadow-[0_0_24px_-6px_rgba(16,185,129,0.35)]"
        >
            {{ $status }}
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

        <x-auth.submit target="verifyCode" :label="$submit" loading="در حال بررسی..."/>
    </form>

    <div class="mt-5 flex items-center justify-between text-sm">
        <button type="button" wire:click="editEmail" class="text-content-muted hover:text-content-main transition cursor-pointer">
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
            <span wire:loading wire:target="resendCode" class="text-primary">در حال ارسال...</span>
        </button>
    </div>
</div>

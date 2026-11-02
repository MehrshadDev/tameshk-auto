@php
    $celebration = session('subscription_celebration');
@endphp

@if (is_array($celebration) && ! empty($celebration['plan_name']))
    @php
        $isBusiness = ($celebration['plan_slug'] ?? '') === 'business';
    @endphp
    <div x-data="{ open: true }"
         x-show="open"
         x-cloak
         @keydown.escape.window="open = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">

        <div x-show="open"
             x-transition.opacity
             @click="open = false"
             class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

        <div x-show="open"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 translate-y-6 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 scale-95"
             class="relative w-full max-w-md overflow-hidden rounded-3xl border border-line-border bg-surface-raised p-6 text-center shadow-2xl sm:p-7">

            <div @class([
                'pointer-events-none absolute -top-24 left-1/2 size-56 -translate-x-1/2 rounded-full blur-3xl',
                'bg-amber-500/25' => $isBusiness,
                'bg-primary/25' => ! $isBusiness,
            ])></div>

            <div class="relative">
                <div @class([
                    'mx-auto flex size-16 items-center justify-center rounded-2xl shadow-lg',
                    'bg-linear-to-br from-amber-400 to-orange-500 text-white shadow-amber-500/30' => $isBusiness,
                    'bg-linear-to-br from-primary to-rose-500 text-white shadow-primary/30' => ! $isBusiness,
                ])>
                    <x-ui.icon name="crown" class="size-8"/>
                </div>

                <span @class([
                    'mt-4 inline-flex items-center gap-1 rounded-full px-3 py-1 text-[11px] font-extrabold',
                    'bg-amber-500/15 text-amber-500 ring-1 ring-amber-500/30' => $isBusiness,
                    'bg-primary/15 text-primary ring-1 ring-primary/30' => ! $isBusiness,
                ])>
                    <x-ui.icon name="sparkles" class="size-3.5"/>
                    <span>اشتراک {{ $celebration['billing_cycle'] }} فعال شد</span>
                </span>

                <h2 class="mt-3 text-xl font-extrabold text-content-main">
                    به خانواده «{{ $celebration['plan_name'] }}» خوش آمدید! 🎉
                </h2>

                <p class="mt-1.5 text-xs leading-6 text-content-muted">
                    پرداخت شما با موفقیت تایید شد و تمام ظرفیت‌های ویژه پلن
                    <strong class="text-content-main">{{ $celebration['plan_name'] }}</strong>
                    هم‌اکنون روی حساب شما فعال است.
                </p>

                <div class="mt-5 grid grid-cols-2 gap-2.5 text-start">
                    <div class="rounded-2xl border border-line-border/80 bg-surface-card p-3.5">
                        <p class="text-[11px] text-content-muted">سهمیه اجرای ماهانه</p>
                        <p class="mt-1 font-mono text-sm font-extrabold text-content-main">
                            {{ $celebration['max_runs'] }} اجرا
                        </p>
                    </div>

                    <div class="rounded-2xl border border-line-border/80 bg-surface-card p-3.5">
                        <p class="text-[11px] text-content-muted">ورک‌فلوهای فعال همزمان</p>
                        <p class="mt-1 font-mono text-sm font-extrabold text-content-main">
                            {{ $celebration['max_active'] }}
                        </p>
                    </div>
                </div>

                @if (! empty($celebration['ref_id']))
                    <div class="mt-3 flex items-center justify-between rounded-xl border border-line-border/60 bg-surface-base px-3.5 py-2 text-[11px]">
                        <span class="text-content-muted">کد رهگیری پرداخت:</span>
                        <span class="font-mono font-bold text-content-main select-all" dir="ltr">{{ $celebration['ref_id'] }}</span>
                    </div>
                @endif

                <button type="button"
                        @click="open = false"
                    @class([
                        'mt-6 flex w-full items-center justify-center gap-2 rounded-xl py-3 px-4 text-xs font-bold text-white shadow-md transition active:scale-[0.99] cursor-pointer',
                        'bg-linear-to-l from-amber-500 to-orange-500 hover:opacity-95 shadow-amber-500/20' => $isBusiness,
                        'bg-primary hover:bg-primary/90 shadow-primary/20' => ! $isBusiness,
                    ])>
                    <span>شروع ساخت اتوماسیون‌های حرفه‌ای</span>
                    <x-ui.icon name="bolt" class="size-4"/>
                </button>
            </div>
        </div>
    </div>
@endif

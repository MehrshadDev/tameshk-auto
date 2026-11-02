@php
    use App\Subscriptions\SubscriptionManager;

    $user = auth()->user();
    $subscriptions = app(SubscriptionManager::class);
    $summary = ($user && $subscriptions->isSaasEnabled()) ? $subscriptions->usageSummary($user) : null;
@endphp

@if ($summary && $summary['in_grace_period'])
    <div class="shrink-0 border-b border-amber-500/30 bg-amber-500/10 px-4 py-2.5 text-xs text-amber-600 dark:text-amber-400 sm:px-6">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2.5">
                <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-amber-500/20 text-amber-500">
                    <x-ui.icon name="alert-triangle" class="size-4"/>
                </span>
                <p class="font-medium leading-5">
                    دوره اشتراک <strong class="font-extrabold">«{{ $summary['plan']->name }}»</strong> شما به پایان رسیده و در
                    <strong class="font-extrabold">مهلت تنفس ({{ $summary['grace_remaining_days'] }} روز باقی‌مانده)</strong>
                    قرار دارید. برای جلوگیری از توقف خودکار ورک‌فلوها، اشتراک خود را تمدید کنید.
                </p>
            </div>

            <a href="{{ route('app.billing.index') }}"
               class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-amber-500 px-3.5 py-1.5 text-xs font-bold text-white shadow-2xs transition hover:bg-amber-600">
                <span>تمدید آنی اشتراک</span>
                <x-ui.icon name="chevron-left" class="size-3.5"/>
            </a>
        </div>
    </div>
@endif

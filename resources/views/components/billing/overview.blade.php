@props([
    'summary',
])

@php
    $plan = $summary['plan'];
    $subscription = $summary['subscription'];
    $inGracePeriod = $summary['in_grace_period'];
    $graceRemainingDays = $summary['grace_remaining_days'];
    $runsUsed = $summary['runs_used'];
    $maxRuns = $summary['max_runs'];
    $runsPercent = $summary['runs_percent'];
    $activeWorkflows = $summary['active_workflows'];
    $maxActiveWorkflows = $summary['max_active_workflows'];
    $activePercent = $summary['active_percent'];
    $remainingDays = $subscription?->remainingDays();
    $isBusiness = $plan->slug === 'business';
    $isPro = $plan->slug === 'pro';
@endphp

<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div @class([
        'flex flex-col justify-between rounded-2xl border p-5 shadow-2xs',
        'border-amber-500/35 bg-linear-to-b from-amber-500/8 to-surface-card' => $isBusiness,
        'border-primary/30 bg-linear-to-b from-primary/6 to-surface-card' => $isPro,
        'border-line-border bg-surface-card' => ! $isPro && ! $isBusiness,
    ])>
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <div @class([
                    'flex size-12 shrink-0 items-center justify-center rounded-2xl ring-1',
                    'bg-amber-500/15 text-amber-500 ring-amber-500/30' => $isBusiness,
                    'bg-primary/10 text-primary ring-primary/20' => ! $isBusiness,
                ])>
                    <x-ui.icon name="crown" class="size-6"/>
                </div>
                <div>
                    <p class="text-xs font-medium text-content-muted">پلن فعلی شما</p>
                    <h2 class="mt-0.5 text-lg font-extrabold text-content-main">پلن {{ $plan->name }}</h2>
                </div>
            </div>

            @if ($inGracePeriod)
                <x-ui.badge variant="warning" dot>مهلت تنفس</x-ui.badge>
            @elseif ($subscription && $subscription->isValid())
                <x-ui.badge variant="success" dot>
                    {{ $subscription->billing_cycle === 'yearly' ? 'سالانه' : 'ماهانه' }}
                </x-ui.badge>
            @else
                <x-ui.badge variant="neutral">پایه</x-ui.badge>
            @endif
        </div>

        <div class="mt-5 flex items-center justify-between border-t border-line-border/60 pt-3.5 text-xs">
            @if ($inGracePeriod)
                <span class="text-amber-500 font-medium">مهلت تمدید بدون توقف:</span>
                <span class="font-mono font-bold text-amber-500">{{ $graceRemainingDays }} روز</span>
            @elseif ($subscription && $remainingDays !== null)
                <span class="text-content-muted">اعتبار باقی‌مانده اشتراک:</span>
                <span class="font-mono font-bold text-content-main">{{ $remainingDays }} روز</span>
            @else
                <span class="text-content-muted">وضعیت حساب:</span>
                <span class="font-semibold text-content-main">رایگان دائمی</span>
            @endif
        </div>
    </div>

    <div class="flex flex-col justify-between rounded-2xl border border-line-border bg-surface-card p-5 shadow-2xs">
        <div>
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500">
                        <x-ui.icon name="player-play" class="size-4.5"/>
                    </span>
                    <span class="text-xs font-bold text-content-main">اجراهای ماهانه</span>
                </div>

                <span class="font-mono text-xs font-extrabold tabular-nums text-content-main" dir="ltr">
                    {{ number_format($runsUsed) }} / {{ $maxRuns !== null ? number_format($maxRuns) : '∞' }}
                </span>
            </div>

            <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-line-border/70">
                <div @class([
                        'h-full rounded-full transition-all duration-300',
                        'bg-primary' => $runsPercent >= 90,
                        'bg-amber-500' => $runsPercent >= 75 && $runsPercent < 90,
                        'bg-sky-500' => $runsPercent < 75,
                    ])
                     style="width: {{ $runsPercent }}%"></div>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between border-t border-line-border/60 pt-3.5 text-[11px] text-content-muted">
            <span>بازنشانی سهمیه ماهانه:</span>
            <span class="font-medium text-content-main">
                {{ $summary['runs_reset_at'] ? $summary['runs_reset_at']->diffForHumans() : '۳۰ روز دیگر' }}
            </span>
        </div>
    </div>

    <div class="flex flex-col justify-between rounded-2xl border border-line-border bg-surface-card p-5 shadow-2xs">
        <div>
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
                        <x-ui.icon name="bolt" class="size-4.5"/>
                    </span>
                    <span class="text-xs font-bold text-content-main">ورک‌فلوهای فعال همزمان</span>
                </div>

                <span class="font-mono text-xs font-extrabold tabular-nums text-content-main" dir="ltr">
                    @if ($maxActiveWorkflows !== null)
                        {{ $activeWorkflows }} / {{ $maxActiveWorkflows }}
                    @else
                        {{ $activeWorkflows }} / ∞
                    @endif
                </span>
            </div>

            <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-line-border/70">
                <div @class([
                        'h-full rounded-full transition-all duration-300',
                        'bg-primary' => $maxActiveWorkflows !== null && $activePercent >= 100,
                        'bg-amber-500' => $maxActiveWorkflows !== null && $activePercent >= 75 && $activePercent < 100,
                        'bg-emerald-500' => $maxActiveWorkflows === null || $activePercent < 75,
                    ])
                     style="width: {{ $activePercent }}%"></div>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between border-t border-line-border/60 pt-3.5 text-[11px] text-content-muted">
            <span>سقف مجاز پلن {{ $plan->name }}:</span>
            <span class="font-bold text-content-main">
                {{ $maxActiveWorkflows !== null ? $maxActiveWorkflows . ' ورک‌فلو فعال' : 'نامحدود' }}
            </span>
        </div>
    </div>
</div>

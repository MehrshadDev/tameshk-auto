@props([
    'saasEnabled' => false,
    'summary' => null,
    'isBusiness' => false,
    'isPro' => false,
    'totalWorkflows' => 0,
    'maxWorkflows' => null,
    'activeWorkflows' => 0,
    'maxActiveWorkflows' => null,
    'totalCredentials' => 0,
    'usagePercent' => 0,
])

@if ($saasEnabled && $summary)
    @php
        $maxRuns = $summary['max_runs'];
        $runsUsed = (int) $summary['runs_used'];
        $runsPercent = $summary['runs_percent'];
    @endphp

    <div :class="collapsed && 'lg:hidden'"
        @class([
            'rounded-2xl border p-3.5 shadow-2xs',
            'border-amber-500/35 bg-linear-to-b from-amber-500/10 to-surface-card' => $isBusiness,
            'border-primary/30 bg-linear-to-b from-primary/8 to-surface-card' => $isPro,
            'border-line-border/80 bg-surface-card' => ! $isPro && ! $isBusiness,
        ])>
        <div class="flex items-center justify-between text-xs">
            <span class="flex items-center gap-1.5 font-bold text-content-main">
                <x-ui.icon name="crown" @class([
                    'size-4',
                    'text-amber-500' => $isBusiness,
                    'text-primary' => ! $isBusiness,
                ])/>
                <span>پلن {{ $summary['plan']->name }}</span>
            </span>
            <a href="{{ route('app.billing.index') }}"
                @class([
                    'text-[11px] font-bold hover:underline',
                    'text-amber-500' => $isBusiness,
                    'text-primary' => ! $isBusiness,
                ])>
                {{ $isBusiness ? 'وضعیت اشتراک' : 'ارتقا پلن' }}
            </a>
        </div>

        <div class="mt-2.5 flex items-center justify-between text-[11px] text-content-muted">
            <span>مصرف ماهانه</span>
            <span class="font-mono font-bold tabular-nums text-content-main" dir="ltr">
                {{ number_format($runsUsed) }} / {{ $maxRuns !== null ? number_format($maxRuns) : '∞' }}
            </span>
        </div>

        @if ($maxRuns !== null)
            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-line-border/70">
                <div @class([
                        'h-full rounded-full transition-all duration-300',
                        'bg-primary' => $runsPercent >= 90,
                        'bg-amber-500' => $runsPercent >= 70 && $runsPercent < 90,
                        'bg-emerald-500' => $runsPercent < 70,
                    ])
                     style="width: {{ $runsPercent }}%"></div>
            </div>
        @endif

        <div class="mt-2 flex items-center justify-between text-[11px] text-content-muted">
            <span class="flex items-center gap-1.5">
                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                <span>ورک‌فلو فعال:</span>
            </span>
            @if ($maxActiveWorkflows !== null)
                <span class="font-mono font-bold tabular-nums text-content-main" dir="ltr">
                    {{ $activeWorkflows }} / {{ $maxActiveWorkflows }}
                </span>
            @else
                <span class="font-bold text-content-main">
                    <span class="font-mono tabular-nums">{{ $activeWorkflows }}</span> فعال
                </span>
            @endif
        </div>
    </div>
@else
    <div :class="collapsed && 'lg:hidden'"
         class="rounded-2xl border border-line-border/80 bg-surface-card p-3.5 shadow-2xs">
        <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-content-main">ظرفیت ورکفلوها</span>
            <span class="font-mono text-[11px] font-bold tabular-nums text-content-muted" dir="ltr">
                {{ $totalWorkflows }} / {{ $maxWorkflows ?? '∞' }}
            </span>
        </div>

        @if ($maxWorkflows !== null)
            <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-line-border/70">
                <div @class([
                        'h-full rounded-full transition-all duration-300',
                        'bg-primary' => $usagePercent >= 90,
                        'bg-amber-500' => $usagePercent >= 70 && $usagePercent < 90,
                        'bg-emerald-500' => $usagePercent < 70,
                    ])
                     style="width: {{ $usagePercent }}%"></div>
            </div>
        @endif

        <div class="mt-2.5 flex items-center justify-between text-[11px] text-content-muted">
            <span class="flex items-center gap-1.5">
                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                <span>{{ $activeWorkflows }} ورکفلو فعال</span>
            </span>
            <span>{{ $totalCredentials }} کلید متصل</span>
        </div>
    </div>
@endif

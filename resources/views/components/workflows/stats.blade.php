@props([
    'total' => 0,
    'max' => 10,
    'active' => 0,
    'runs' => 0,
    'successRate' => null,
])

<div class="grid grid-cols-2 gap-3 md:grid-cols-4 sm:gap-4">
    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <x-ui.icon name="hierarchy-2" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">کل ورکفلوها</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ $total }}</span>
                <span class="text-[11px] text-content-muted">از {{ $max }}</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
            <x-ui.icon name="bolt" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">منتشرشده و فعال</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ $active }}</span>
                <span class="text-[11px] text-content-muted">ورکفلو</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500">
            <x-ui.icon name="player-play" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">کل اجراها</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ number_format($runs) }}</span>
                <span class="text-[11px] text-content-muted">بار</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500">
            <x-ui.icon name="sparkles" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">نرخ موفقیت اجرا</p>
            <div class="mt-1 flex items-baseline gap-1">
                @if ($successRate !== null)
                    <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">%{{ $successRate }}</span>
                @else
                    <span class="text-xs font-semibold text-content-muted">بدون اجرا</span>
                @endif
            </div>
        </div>
    </div>
</div>

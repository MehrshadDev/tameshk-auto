@props([
    'total' => 0,
    'succeeded' => 0,
    'failed' => 0,
    'avgDurationSeconds' => null,
])

<div class="grid grid-cols-2 gap-3 md:grid-cols-4 sm:gap-4">
    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <x-ui.icon name="history" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">کل اجراها</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ number_format($total) }}</span>
                <span class="text-[11px] text-content-muted">بار</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
            <x-ui.icon name="check" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">اجراهای موفق</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ number_format($succeeded) }}</span>
                <span class="text-[11px] text-content-muted">مورد</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-rose-500/10 text-rose-500">
            <x-ui.icon name="alert-triangle" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">اجراهای ناموفق</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ number_format($failed) }}</span>
                <span class="text-[11px] text-content-muted">خطا</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500">
            <x-ui.icon name="clock" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">میانگین زمان اجرا</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                @if ($avgDurationSeconds !== null)
                    <span class="font-mono text-lg font-extrabold tabular-nums text-content-main" dir="ltr">{{ $avgDurationSeconds }}s</span>
                @else
                    <span class="text-xs font-semibold text-content-muted">بدون داده</span>
                @endif
            </div>
        </div>
    </div>
</div>

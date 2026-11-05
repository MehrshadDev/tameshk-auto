@props([
    'total' => 0,
    'pending' => 0,
    'answered' => 0,
    'closed' => 0,
    'isAdmin' => false,
])

<div class="grid grid-cols-2 gap-3 md:grid-cols-4 sm:gap-4">
    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <x-ui.icon name="ticket" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">
                {{ $isAdmin ? 'کل تیکت‌های سیستم' : 'کل تیکت‌های من' }}
            </p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ number_format($total) }}</span>
                <span class="text-[11px] text-content-muted">تیکت</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500">
            <x-ui.icon name="clock" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">در انتظار بررسی</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ number_format($pending) }}</span>
                <span class="text-[11px] text-content-muted">مورد</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
            <x-ui.icon name="check" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">پاسخ داده شده</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ number_format($answered) }}</span>
                <span class="text-[11px] text-content-muted">مورد</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500">
            <x-ui.icon name="lock" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">بسته شده</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ number_format($closed) }}</span>
                <span class="text-[11px] text-content-muted">تیکت</span>
            </div>
        </div>
    </div>
</div>

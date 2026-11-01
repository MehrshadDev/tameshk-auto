@props([
    'total' => 0,
    'aiCount' => 0,
    'oauthCount' => 0,
    'inUseCount' => 0,
])

<div class="grid grid-cols-2 gap-3 lg:grid-cols-4 sm:gap-4">
    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <x-ui.icon name="key" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">کل اعتبارنامه‌ها</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ $total }}</span>
                <span class="text-[11px] text-content-muted">حساب</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-500">
            <x-ui.icon name="sparkles" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">مدل‌های هوش مصنوعی</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ $aiCount }}</span>
                <span class="text-[11px] text-content-muted">کلید API</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
            <x-ui.icon name="link" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">حساب‌های OAuth متصل</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ $oauthCount }}</span>
                <span class="text-[11px] text-content-muted">حساب گوگل</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500">
            <x-ui.icon name="hierarchy-2" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">متصل به ورکفلوها</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ $inUseCount }}</span>
                <span class="text-[11px] text-content-muted">اعتبارنامه</span>
            </div>
        </div>
    </div>
</div>

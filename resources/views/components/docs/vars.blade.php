@props([
    'title' => 'متغیرهای خروجی قابل استفاده در نودهای بعدی',
    'items' => [],
])

<div class="my-5 overflow-hidden rounded-2xl border border-line-border bg-surface-base shadow-2xs">
    <div class="flex items-center justify-between border-b border-line-border/80 bg-surface-raised px-4 py-2.5">
        <span class="flex items-center gap-2 text-xs font-extrabold text-content-main">
            <x-ui.icon name="brackets" class="size-4 text-primary"/>
            <span>{{ $title }}</span>
        </span>
        <span class="text-[11px] text-content-muted">کلیک برای کپی متغیر</span>
    </div>

    <div class="divide-y divide-line-border/60">
        @foreach ($items as $item)
            @php
                $rawExpr = trim((string) ($item['expr'] ?? ''));
                $fullExpr = str_starts_with($rawExpr, '{') ? $rawExpr : '{' . '{ ' . $rawExpr . ' }' . '}';
            @endphp

            <div x-data="{ copied: false }"
                 class="flex flex-col justify-between gap-2 px-4 py-3 transition hover:bg-surface-card/60 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-content-main">{{ $item['label'] ?? '' }}</p>
                    @if (! empty($item['desc']))
                        <p class="mt-0.5 text-[11px] text-content-muted">{{ $item['desc'] }}</p>
                    @endif
                </div>

                <button type="button"
                        dir="ltr"
                        data-expr="{{ $fullExpr }}"
                        @click="navigator.clipboard.writeText($el.dataset.expr); copied = true; setTimeout(() => copied = false, 2000)"
                        class="inline-flex w-fit shrink-0 items-center gap-2 self-end rounded-xl border border-line-border bg-surface-card px-3 py-1.5 font-mono text-xs text-primary transition hover:border-primary/50 hover:bg-primary/5 sm:self-auto cursor-pointer">
                    <span>{{ $fullExpr }}</span>
                    <x-ui.icon name="copy" x-show="!copied" class="size-3.5 text-content-muted"/>
                    <x-ui.icon name="check" x-show="copied" x-cloak class="size-3.5 text-emerald-500"/>
                </button>
            </div>
        @endforeach
    </div>
</div>

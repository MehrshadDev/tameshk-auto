@props(['paginator'])

@if ($paginator->hasPages())
    @php
        $pageName = $paginator->getPageName();
    @endphp

    <nav x-data="{
             scrollUp() {
                 const scroller = this.$el.closest('main') || document.getElementById('main-content');
                 if (scroller) {
                     scroller.scrollTo({ top: 0, behavior: 'smooth' });
                 } else {
                     window.scrollTo({ top: 0, behavior: 'smooth' });
                 }
             }
         }"
         role="navigation"
         aria-label="صفحه‌بندی"
         class="flex items-center justify-center pt-2 select-none">
        <div class="inline-flex items-center gap-1.5 rounded-2xl border border-line-border bg-surface-card p-1.5 shadow-2xs">
            <button type="button"
                    wire:click="previousPage('{{ $pageName }}')"
                    @click="scrollUp()"
                    wire:loading.attr="disabled"
                    @disabled($paginator->onFirstPage())
                    aria-label="صفحه قبلی"
                    class="inline-flex h-8.5 items-center justify-center gap-1.5 rounded-xl px-3 text-xs font-semibold text-content-main transition hover:bg-surface-raised hover:text-primary cursor-pointer disabled:pointer-events-none disabled:opacity-40">
                <x-ui.icon name="chevron-right" class="size-4"/>
                <span>قبلی</span>
            </button>

            <div class="flex h-8.5 items-center justify-center gap-1.5 rounded-xl border border-line-border/70 bg-surface-base px-3.5 text-xs">
                <span class="text-content-muted">صفحه</span>
                <span class="font-mono font-bold tabular-nums text-primary">{{ $paginator->currentPage() }}</span>
                @if (method_exists($paginator, 'lastPage'))
                    <span class="text-content-muted">از</span>
                    <span class="font-mono font-bold tabular-nums text-content-main">{{ $paginator->lastPage() }}</span>
                @endif
            </div>

            <button type="button"
                    wire:click="nextPage('{{ $pageName }}')"
                    @click="scrollUp()"
                    wire:loading.attr="disabled"
                    @disabled(! $paginator->hasMorePages())
                    aria-label="صفحه بعدی"
                    class="inline-flex h-8.5 items-center justify-center gap-1.5 rounded-xl px-3 text-xs font-semibold text-content-main transition hover:bg-surface-raised hover:text-primary cursor-pointer disabled:pointer-events-none disabled:opacity-40">
                <span>بعدی</span>
                <x-ui.icon name="chevron-left" class="size-4"/>
            </button>
        </div>
    </nav>
@endif

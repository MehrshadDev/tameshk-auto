@props(['name', 'title' => null, 'icon' => null, 'maxWidth' => 'md'])

@php
    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
    ];
@endphp

<div data-modal-name="{{ $name }}"
     x-data="{ show: false }"
     x-on:open-modal.window="if (($event.detail?.name ?? $event.detail) === $el.dataset.modalName) show = true"
     x-on:close-modal.window="if (($event.detail?.name ?? $event.detail) === $el.dataset.modalName) show = false"
     x-on:keydown.escape.window="show = false"
     x-show="show"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
    <div x-show="show"
         x-transition.opacity
         @click="show = false"
         class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

    <div x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="relative flex max-h-[calc(100dvh-1.5rem)] w-full flex-col {{ $widths[$maxWidth] }} rounded-2xl border border-line-border bg-surface-raised shadow-2xl sm:max-h-[calc(100dvh-3rem)]">
        @if ($title)
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line-border px-5 py-3.5 sm:px-6 sm:py-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    @if ($icon)
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-xl border border-line-border/70 bg-surface-card text-primary">
                            <x-ui.icon :name="$icon" class="size-4.5"/>
                        </span>
                    @endif
                    <h2 class="truncate text-sm font-bold text-content-main sm:text-base">{{ $title }}</h2>
                </div>
                <button type="button" @click="show = false" aria-label="بستن"
                        class="flex items-center justify-center rounded-lg p-1.5 text-content-muted transition hover:bg-line-border/60 hover:text-content-main cursor-pointer">
                    <x-ui.icon name="x"/>
                </button>
            </div>
        @endif

        <div class="custom-scrollbar min-h-0 min-w-0 flex-1 overflow-y-auto wrap-break-word p-5 sm:p-6">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-line-border px-5 py-3.5 sm:px-6 sm:py-4">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>

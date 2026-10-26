@props(['name', 'title' => null, 'maxWidth' => 'md'])

@php
    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
    ];
@endphp

<div x-data="{ show: false }"
     x-on:open-modal.window="if (($event.detail?.name ?? $event.detail) === @js($name)) show = true"
     x-on:close-modal.window="if (($event.detail?.name ?? $event.detail) === @js($name)) show = false"
     x-on:keydown.escape.window="show = false"
     x-show="show"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4">
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
         class="relative w-full {{ $widths[$maxWidth] }} rounded-2xl border border-line-border bg-surface-raised shadow-2xl">
        @if ($title)
            <div class="flex items-center justify-between border-b border-line-border px-6 py-4">
                <h2 class="text-base font-bold text-content-main">{{ $title }}</h2>
                <button type="button" @click="show = false" aria-label="بستن"
                        class="rounded-lg p-1.5 text-content-muted transition hover:bg-line-border/60 hover:text-content-main flex justify-center items-center">
                    <x-ui.icon name="x"/>
                </button>
            </div>
        @endif

        <div class="min-w-0 wrap-break-word p-6">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex items-center justify-end gap-2 border-t border-line-border px-6 py-4">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>

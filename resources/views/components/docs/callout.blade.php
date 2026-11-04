@props([
    'type' => 'info',
    'title' => null,
])

@php
    $styles = match ($type) {
        'warning' => [
            'wrapper' => 'border-amber-500/30 bg-amber-500/8 text-amber-600 dark:text-amber-400',
            'iconBox' => 'bg-amber-500/15 text-amber-500',
            'icon'    => 'alert-triangle',
        ],
        'success' => [
            'wrapper' => 'border-emerald-500/30 bg-emerald-500/8 text-emerald-600 dark:text-emerald-400',
            'iconBox' => 'bg-emerald-500/15 text-emerald-500',
            'icon'    => 'check',
        ],
        'danger' => [
            'wrapper' => 'border-primary/30 bg-primary/8 text-primary',
            'iconBox' => 'bg-primary/15 text-primary',
            'icon'    => 'alert-triangle',
        ],
        default => [
            'wrapper' => 'border-sky-500/30 bg-sky-500/8 text-sky-600 dark:text-sky-400',
            'iconBox' => 'bg-sky-500/15 text-sky-500',
            'icon'    => 'sparkles',
        ],
    };
@endphp

<div class="my-5 flex items-start gap-3.5 rounded-2xl border p-4 {{ $styles['wrapper'] }}">
    <span class="flex size-8 shrink-0 items-center justify-center rounded-xl {{ $styles['iconBox'] }}">
        <x-ui.icon :name="$styles['icon']" class="size-4.5"/>
    </span>
    <div class="min-w-0 flex-1 space-y-1 text-xs leading-6">
        @if ($title)
            <p class="font-extrabold text-content-main">{{ $title }}</p>
        @endif
        <div class="text-content-main/90">
            {{ $slot }}
        </div>
    </div>
</div>

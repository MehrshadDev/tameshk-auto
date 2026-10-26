@props(['variant' => 'neutral', 'dot' => false])

@php
    $variants = [
        'neutral' => 'bg-line-border/60 text-content-muted',
        'success' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        'danger'  => 'bg-primary/10 text-primary',
        'warning' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        'info'    => 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    ];

    $dots = [
        'neutral' => 'bg-content-muted',
        'success' => 'bg-emerald-500',
        'danger'  => 'bg-primary',
        'warning' => 'bg-amber-500',
        'info'    => 'bg-sky-500',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium', $variants[$variant]]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full {{ $dots[$variant] }}"></span>
    @endif
    {{ $slot }}
</span>

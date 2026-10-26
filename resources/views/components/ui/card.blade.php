@props(['padding' => true])

<div {{ $attributes->class(['rounded-2xl border border-line-border bg-surface-card shadow-sm', 'p-5' => $padding]) }}>
    {{ $slot }}
</div>

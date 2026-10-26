@props(['href' => null, 'icon' => null, 'danger' => false])

@php
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }} {{ $attributes->merge(['href' => $href, 'type' => $href ? null : 'button'])->class([
    'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition',
    'text-content-muted hover:bg-line-border/60 hover:text-content-main' => ! $danger,
    'text-primary hover:bg-primary/10' => $danger,
]) }}>
@if ($icon)
    <x-ui.icon :name="$icon" class="size-[18px]"/>
@endif
{{ $slot }}
</{{ $tag }}>

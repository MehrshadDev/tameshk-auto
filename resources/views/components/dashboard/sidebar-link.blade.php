@props([
    'href' => '#',
    'icon',
    'active' => false,
    'disabled' => false,
    'badge' => null,
])

@if ($disabled)
    <span :title="collapsed ? @js(trim(strip_tags((string) $slot))) : null"
          :class="collapsed && 'lg:justify-center lg:px-0'"
          class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-content-muted/50">
        <x-ui.icon :name="$icon"/>
        <span class="flex-1" :class="collapsed && 'lg:hidden'">{{ $slot }}</span>
        @if ($badge)
            <span :class="collapsed && 'lg:hidden'">
                <x-ui.badge>{{ $badge }}</x-ui.badge>
            </span>
        @endif
    </span>
@else
    <a href="{{ $href }}"
       :title="collapsed ? @js(trim(strip_tags((string) $slot))) : null"
       :class="collapsed && 'lg:justify-center lg:px-0'"
        @class([
            'relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition',
            'bg-primary/10 font-medium text-primary' => $active,
            'text-content-muted hover:bg-line-border/50 hover:text-content-main' => ! $active,
        ])>
        @if ($active)
            <span class="absolute inset-y-2.5 right-0 w-1 rounded-l-full bg-primary"></span>
        @endif
        <x-ui.icon :name="$icon"/>
        <span class="flex-1" :class="collapsed && 'lg:hidden'">{{ $slot }}</span>
    </a>
@endif

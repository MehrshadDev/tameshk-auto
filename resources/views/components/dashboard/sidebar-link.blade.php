@props([
    'href' => '#',
    'icon',
    'active' => false,
    'disabled' => false,
    'badge' => null,
    'count' => null,
])

@php
    $plainLabel = trim(strip_tags((string) $slot));
@endphp

@if ($disabled)
    <span data-title="{{ $plainLabel }}"
          :title="collapsed ? $el.dataset.title : null"
          :class="collapsed ? 'lg:justify-center lg:px-0' : 'px-3'"
          class="flex cursor-not-allowed items-center gap-3 rounded-xl py-2.5 text-xs font-medium text-content-muted/45 select-none">
        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-line-border/30">
            <x-ui.icon :name="$icon" class="size-4.5"/>
        </span>
        <span class="flex-1 truncate" :class="collapsed && 'lg:hidden'">{{ $slot }}</span>
        @if ($badge)
            <span :class="collapsed && 'lg:hidden'" class="rounded-md border border-line-border/80 bg-surface-card px-1.5 py-0.5 text-[10px] font-semibold text-content-muted">
                {{ $badge }}
            </span>
        @endif
    </span>
@else
    <a href="{{ $href }}"
       data-title="{{ $plainLabel }}"
       :title="collapsed ? $el.dataset.title : null"
       :class="collapsed ? 'lg:justify-center lg:px-0' : 'px-3'"
        @class([
            'group relative flex items-center gap-3 rounded-xl py-2 text-xs transition-all duration-150 select-none',
            'bg-primary/10 font-bold text-primary shadow-2xs' => $active,
            'font-medium text-content-muted hover:bg-surface-card hover:text-content-main' => ! $active,
        ])>
        @if ($active)
            <span class="absolute inset-y-2 right-0 w-1 rounded-l-full bg-primary"></span>
        @endif

        <span @class([
            'flex size-8 shrink-0 items-center justify-center rounded-lg transition-colors',
            'bg-primary text-white shadow-xs shadow-primary/25' => $active,
            'bg-line-border/40 text-content-muted group-hover:bg-line-border/70 group-hover:text-content-main' => ! $active,
        ])>
            <x-ui.icon :name="$icon" class="size-4.5"/>
        </span>

        <span class="flex-1 truncate" :class="collapsed && 'lg:hidden'">{{ $slot }}</span>

        @if ($count !== null)
            <span :class="collapsed && 'lg:hidden'"
                @class([
                    'rounded-md px-1.5 py-0.5 font-mono text-[10px] font-bold tabular-nums',
                    'bg-primary/15 text-primary' => $active,
                    'bg-line-border/60 text-content-muted' => ! $active,
                ])>
                {{ $count }}
            </span>
        @elseif ($badge)
            <span :class="collapsed && 'lg:hidden'">
                <x-ui.badge>{{ $badge }}</x-ui.badge>
            </span>
        @endif
    </a>
@endif

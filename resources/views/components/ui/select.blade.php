@props([
    'options' => [],
    'selected' => '',
    'wireModel' => '',
    'placeholder' => 'انتخاب کنید',
    'searchable' => true,
    'width' => 'w-full sm:w-60',
])

@php
    $selectedOption = collect($options)->firstWhere('value', (string) $selected);
    $selectedLabel = $selectedOption['label'] ?? $placeholder;
    $selectedIcon = $selectedOption['icon'] ?? null;
@endphp

<div x-data="{ open: false, query: '' }"
     @keydown.escape.window="open = false"
     @click.outside="open = false"
     :class="open && 'z-50'"
     class="relative {{ $width }} select-none">

    <button type="button"
            @click="open = !open; if (open) { query = ''; $nextTick(() => $refs.searchInput?.focus()); }"
            :aria-expanded="open"
            :class="open ? 'border-primary bg-surface-card ring-2 ring-primary/20' : 'border-line-border bg-surface-base hover:border-content-muted/40'"
            class="flex w-full items-center justify-between gap-2.5 rounded-xl border px-3.5 py-2 text-xs font-medium text-content-main transition cursor-pointer">
        <span class="flex min-w-0 items-center gap-2">
            @if ($selectedIcon)
                <x-ui.icon :name="$selectedIcon" class="size-4 shrink-0 text-primary"/>
            @endif
            <span class="truncate font-semibold text-content-main">{{ $selectedLabel }}</span>
        </span>

        <x-ui.icon name="chevron-down"
                   x-bind:class="open && 'rotate-180 text-primary'"
                   class="size-4 shrink-0 text-content-muted transition-transform duration-200"/>
    </button>

    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
         class="absolute inset-s-0 z-50 mt-2 flex max-h-72 w-full min-w-56 flex-col overflow-hidden rounded-2xl border border-line-border bg-surface-raised p-1.5 shadow-2xl backdrop-blur-xl">

        @if ($searchable && count($options) > 5)
            <div class="mb-1 border-b border-line-border/60 pb-1.5">
                <div class="relative flex items-center">
                    <x-ui.icon name="search" class="pointer-events-none absolute inset-s-2.5 size-3.5 text-content-muted"/>
                    <input x-ref="searchInput"
                           type="text"
                           x-model="query"
                           @click.stop
                           placeholder="جستجو در سرویس‌ها..."
                           class="w-full rounded-xl border border-line-border bg-surface-card py-1.5 pe-2.5 ps-8 text-xs text-content-main placeholder:text-content-muted/50 focus:border-primary focus:outline-none"/>
                </div>
            </div>
        @endif

        <div class="custom-scrollbar flex-1 space-y-0.5 overflow-y-auto pe-0.5">
            @foreach ($options as $option)
                @php
                    $optValue = (string) ($option['value'] ?? '');
                    $optLabel = (string) ($option['label'] ?? $optValue);
                    $optIcon = $option['icon'] ?? null;
                    $isSelected = (string) $selected === $optValue;
                @endphp

                <button type="button"
                        data-search="{{ mb_strtolower($optLabel . ' ' . $optValue) }}"
                        x-show="!query.trim() || $el.dataset.search.includes(query.trim().toLowerCase())"
                        wire:click="$set('{{ $wireModel }}', '{{ $optValue }}')"
                        @click="open = false"
                    @class([
                        'flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2 text-start text-xs transition cursor-pointer',
                        'bg-primary/10 font-bold text-primary' => $isSelected,
                        'font-medium text-content-main hover:bg-line-border/50 hover:text-primary' => ! $isSelected,
                    ])>
                    <span class="flex min-w-0 flex-1 items-center gap-2.5">
                        @if ($optIcon)
                            <x-ui.icon :name="$optIcon" class="size-4 shrink-0"/>
                        @endif
                        <span dir="auto" class="truncate">{{ $optLabel }}</span>
                    </span>

                    @if ($isSelected)
                        <x-ui.icon name="check" class="size-3.5 shrink-0 text-primary"/>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
</div>

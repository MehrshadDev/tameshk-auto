@props(['align' => 'left', 'width' => 'w-56'])

<div x-data="{ open: false }"
     @keydown.escape.window="open = false"
     @click.outside="open = false"
     class="relative">
    <div @click="open = !open" :aria-expanded="open">
        {{ $trigger }}
    </div>

    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click="open = false"
         class="absolute z-50 mt-2 {{ $width }} {{ $align === 'left' ? 'left-0 origin-top-left' : 'right-0 origin-top-right' }} overflow-hidden rounded-xl border border-line-border bg-surface-raised p-1.5 shadow-xl">
        {{ $slot }}
    </div>
</div>

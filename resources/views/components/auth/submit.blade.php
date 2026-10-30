@props(['target', 'label', 'loading'])

<button
    type="submit"
    wire:loading.attr="disabled"
    wire:target="{{ $target }}"
    {{ $attributes->class('w-full py-2.5 rounded-xl bg-primary text-white font-medium hover:opacity-90 active:scale-[0.99] cursor-pointer transition disabled:opacity-60 disabled:cursor-not-allowed') }}
>
    <span wire:loading.remove wire:target="{{ $target }}">{{ $label }}</span>
    <span wire:loading wire:target="{{ $target }}">{{ $loading }}</span>
</button>

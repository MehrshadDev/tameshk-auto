@props(['checked' => false, 'label' => 'تغییر وضعیت'])

<button
    type="button"
        role="switch"
        aria-checked="{{ $checked ? 'true' : 'false' }}"
        aria-label="{{ $label }}"
    {{ $attributes->class([
        'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 disabled:cursor-wait disabled:opacity-60',
        'bg-primary' => $checked,
        'bg-content-muted/30' => ! $checked,
    ]) }}>
    <span @class([
        'ms-[3px] inline-block size-4.5 rounded-full bg-white shadow transition-transform',
        'translate-x-5 rtl:-translate-x-5' => $checked,
    ])></span>
</button>

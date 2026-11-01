@props(['name'])

@php
    $isBrand = str_starts_with($name, 'brand:');
    $brandName = $isBrand ? substr($name, 6) : null;
    $monoDarkBrands = ['chatgpt', 'wikipedia'];
    $invertClass = $isBrand && in_array($brandName, $monoDarkBrands, true) ? 'dark:invert' : '';
    $url = $isBrand
        ? asset('icons/brands/' . $brandName . '.svg')
        : asset('icons/tabler/' . $name . '.svg');
@endphp

@if ($isBrand)
    <img src="{{ $url }}"
         alt="{{ $brandName }}"
         loading="lazy"
        {{ $attributes->class(['inline-block size-5 shrink-0 object-contain pointer-events-none select-none', $invertClass]) }}>
@else
    <span aria-hidden="true"
          {{ $attributes->class(['inline-block size-5 shrink-0 bg-current']) }}
          style="-webkit-mask: url('{{ $url }}') center / contain no-repeat; mask: url('{{ $url }}') center / contain no-repeat;"></span>
@endif

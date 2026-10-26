@props(['name'])

@php
    $url = asset('icons/tabler/' . $name . '.svg');
@endphp

<span {{ $attributes->class(['inline-block size-5 shrink-0 bg-current']) }}
      style="-webkit-mask: url('{{ $url }}') center / contain no-repeat; mask: url('{{ $url }}') center / contain no-repeat;"></span>

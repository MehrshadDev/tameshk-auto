@props([
    'logoClasses' => "h-12 w-auto"
])

@php
    $logo = asset('images/logo/svg/logo-lockup.svg');
@endphp

<a {{ $attributes->merge([
    'href' => route('home'),
    'aria-label' => "لوگو " . config('app.site_name'),
]) }}>
    <img src="{{ $logo }}"
         alt="{{ "لوگو " . config('app.site_name') }}"
         class="{{ $logoClasses }}"
         fetchpriority="high"
    >
</a>

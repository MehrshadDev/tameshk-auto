@props([
    'title',
    'robots' => 'noindex, nofollow',
    'vite' => ['resources/css/app.css', 'resources/js/app.js'],
    'react' => false,
    'livewire' => true,
])

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="{{ $robots }}">

<title>{{ $title }}</title>

<link rel="icon" type="image/png" href="{{ asset('images/logo/favicon.png') }}">

{{ $slot }}

<x-layouts.theme/>

@if ($react)
    @viteReactRefresh
@endif
@vite($vite)
@if ($livewire)
    @livewireStyles
@endif

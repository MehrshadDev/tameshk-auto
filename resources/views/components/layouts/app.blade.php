@props([
    'title' => config('app.name') . ' - ' . config('app.site_tagline'),
    'description' => '',
    'robots' => "index, follow",
    'canonical' => null,
    'footer_visibility' => false,
    'header_visibility' => false,
])
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonical ?? request()->url() }}">


    <title>{{ $title }}</title>

    @if (!empty($description))
        <meta name="description" content="{{ $description }}">
    @endif

    <link rel="icon" type="image/png" href="{{ asset('images/logo/favicon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-surface-base">
<div id="main-content" class="flex-1 overflow-y-auto overflow-x-hidden custom-scrollbar bg-surface-base">
    @if($header_visibility)
        <x-layouts.app-header/>
    @endif
    <main class="flex-1 min-h-screen bg-surface-base">
        {{ $slot }}
    </main>
    @if($footer_visibility)
        <x-layouts.app-footer/>
    @endif
</div>
@livewireScripts
</body>
</html>

<script>
    const theme = localStorage.getItem('theme');

    if (
        theme === 'dark' ||
        (theme === null &&
            window.matchMedia('(prefers-color-scheme: dark)').matches)
    ) {
        document.documentElement.classList.add('dark');
    }
</script>

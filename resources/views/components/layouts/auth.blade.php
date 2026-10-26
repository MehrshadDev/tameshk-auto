@props([
    'title' => 'ورود | ' . config('app.name')
])
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title }}</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo/favicon.png') }}">

    <script data-navigate-once>
        (() => {
            const apply = () => {
                const theme = localStorage.getItem('theme');
                const dark = theme === 'dark' || (theme === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            };
            apply();
            document.addEventListener('livewire:navigated', apply);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-surface-base text-content-primary min-h-screen">

<div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
    <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-primary/20 blur-2xl"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full bg-primary/20 blur-2xl"></div>
</div>

<main class="min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="w-full max-w-md">

        <div class="flex justify-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2">
                <x-layouts.logo class="h-10 w-auto" />
            </a>
        </div>

        <div class="border border-line-border rounded-2xl shadow-sm p-6 sm:p-8">
            {{ $slot }}
        </div>

    </div>
</main>

@livewireScripts
</body>
</html>

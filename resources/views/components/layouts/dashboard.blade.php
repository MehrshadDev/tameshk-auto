@props([
    'title' => 'پنل | ' . config('app.name'),
    'heading' => null,
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
<body class="bg-surface-base font-sans text-content-main antialiased">
<div x-data="{
         sidebarOpen: false,
         collapsed: localStorage.getItem('sidebar') === 'collapsed',
         toggleSidebar() {
             this.collapsed = ! this.collapsed;
             localStorage.setItem('sidebar', this.collapsed ? 'collapsed' : 'open');
         }
     }"
     @keydown.escape.window="sidebarOpen = false"
     class="flex h-screen overflow-hidden">
    <x-dashboard.sidebar/>

    <div class="flex min-w-0 flex-1 flex-col">
        <x-dashboard.header :heading="$heading"/>

        <main class="custom-scrollbar flex-1 overflow-y-auto bg-surface-base">
            {{ $slot }}
        </main>
    </div>
</div>
<x-ui.toast/>
@livewireScripts
</body>
</html>

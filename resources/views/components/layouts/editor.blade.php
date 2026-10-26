@props([
    'title' => 'ادیتور - ' . config('app.name'),
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

    <script>
        (() => {
            const theme = localStorage.getItem('theme');
            const dark = theme === 'dark' || (theme === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/canvas/main.tsx'])
</head>
<body class="h-screen overflow-hidden bg-surface-base font-sans text-content-main antialiased">
{{ $slot }}
</body>
</html>

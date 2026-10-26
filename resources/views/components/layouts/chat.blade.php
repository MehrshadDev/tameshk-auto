@props(['title' => 'گفتگو'])
    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }}</title>
    <script>
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css'])
</head>
<body class="h-dvh overflow-hidden bg-surface-base font-sans text-content-main antialiased">
{{ $slot }}
</body>
</html>

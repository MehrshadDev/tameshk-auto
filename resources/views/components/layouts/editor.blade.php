@props([
    'title' => 'ادیتور - ' . config('app.name'),
])
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <x-layouts.head :title="$title" :vite="['resources/css/app.css', 'resources/js/canvas/main.tsx']" react :livewire="false"/>
</head>
<body class="h-screen overflow-hidden bg-surface-base font-sans text-content-main antialiased">
{{ $slot }}
</body>
</html>

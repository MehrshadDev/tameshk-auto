@props(['title' => 'گفتگو'])
    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <x-layouts.head :title="$title" :vite="['resources/css/app.css']" :livewire="false"/>
</head>
<body class="h-dvh overflow-hidden bg-surface-base font-sans text-content-main antialiased">
{{ $slot }}
</body>
</html>

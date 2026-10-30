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
    <x-layouts.head :title="$title" :robots="$robots">
        <link rel="canonical" href="{{ $canonical ?? request()->url() }}">
        @if (!empty($description))
            <meta name="description" content="{{ $description }}">
        @endif
    </x-layouts.head>
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

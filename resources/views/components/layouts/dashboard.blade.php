@props([
    'title' => 'پنل | ' . config('app.name'),
    'heading' => null,
])
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <x-layouts.head :title="$title"/>
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

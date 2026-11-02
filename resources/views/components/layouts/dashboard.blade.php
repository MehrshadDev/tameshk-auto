@props([
    'title' => 'پنل | ' . config('app.name'),
    'heading' => null,
    'subheading' => null,
])
    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <x-layouts.head :title="$title"/>
</head>
<body data-user-name="{{ auth()->user()?->name ?? '' }}"
      class="bg-surface-base font-sans text-content-main antialiased selection:bg-primary/20 selection:text-primary">
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('userProfile', {
            name: document.body.dataset.userName || '',
        });
    });
</script>
<div x-data="{
         sidebarOpen: false,
         collapsed: localStorage.getItem('sidebar') === 'collapsed',
         resizing: false,
         toggleSidebar() {
             this.collapsed = ! this.collapsed;
             localStorage.setItem('sidebar', this.collapsed ? 'collapsed' : 'open');
         },
         startResize(event) {
             if (window.innerWidth < 1024) return;
             this.resizing = true;
             let anchorX = event.clientX;
             document.body.style.cursor = 'col-resize';
             document.body.style.userSelect = 'none';

             const onMove = (e) => {
                 const deltaX = anchorX - e.clientX;

                 if (this.collapsed && deltaX > 48) {
                     this.collapsed = false;
                     localStorage.setItem('sidebar', 'open');
                     anchorX = e.clientX;
                 } else if (!this.collapsed && deltaX < -48) {
                     this.collapsed = true;
                     localStorage.setItem('sidebar', 'collapsed');
                     anchorX = e.clientX;
                 }
             };

             const onUp = () => {
                 this.resizing = false;
                 document.body.style.cursor = '';
                 document.body.style.userSelect = '';
                 window.removeEventListener('pointermove', onMove);
                 window.removeEventListener('pointerup', onUp);
             };

             window.addEventListener('pointermove', onMove);
             window.addEventListener('pointerup', onUp);
         }
     }"
     @keydown.escape.window="sidebarOpen = false"
     class="flex h-dvh overflow-hidden">
    <x-dashboard.sidebar/>

    <div class="flex min-w-0 flex-1 flex-col">
        <x-dashboard.header :heading="$heading" :subheading="$subheading"/>

        <main class="custom-scrollbar flex-1 overflow-y-auto bg-surface-base">
            {{ $slot }}
        </main>
    </div>
</div>
<x-dashboard.profile-modal/>
<x-dashboard.logout-modal/>
<x-ui.toast/>
@livewireScripts
</body>
</html>

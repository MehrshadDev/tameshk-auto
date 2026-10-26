<div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm lg:hidden"></div>

<aside x-cloak :class="[sidebarOpen ? 'translate-x-0' : 'translate-x-full', collapsed ? 'lg:w-18' : 'lg:w-64']"
       class="fixed inset-y-0 right-0 z-40 flex w-64 flex-col border-l border-line-border bg-surface-raised transition-[transform,width] duration-200 lg:static lg:translate-x-0">
    <div class="flex h-16 shrink-0 items-center justify-between px-5" :class="collapsed && 'lg:justify-center lg:px-0'">
        <x-layouts.logo logoClasses="h-12 w-auto" x-bind:class="collapsed && 'lg:hidden'"/>
        <img src="{{ asset('images/logo/favicon.png') }}" alt="" class="hidden w-8" :class="collapsed && 'lg:block'">

        <button @click="sidebarOpen = false" aria-label="بستن منو" class="rounded-lg p-1.5 text-content-muted hover:bg-line-border/60 lg:hidden">
            <x-ui.icon name="x"/>
        </button>
    </div>

    <nav class="custom-scrollbar flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-3 py-2">
        <p class="px-3 pb-2 pt-3 text-xs font-medium text-content-muted/70" :class="collapsed && 'lg:hidden'">اتوماسیون</p>
        <div class="mx-2 my-3 hidden border-t border-line-border" :class="collapsed && 'lg:block'"></div>

        <x-dashboard.sidebar-link icon="hierarchy-2" :href="route('app.workflows.index')" :active="request()->routeIs('app.workflows.*')">
            ورکفلوها
        </x-dashboard.sidebar-link>

        <x-dashboard.sidebar-link icon="history" disabled badge="به‌زودی">
            اجراها
        </x-dashboard.sidebar-link>

        <p class="px-3 pb-2 pt-5 text-xs font-medium text-content-muted/70" :class="collapsed && 'lg:hidden'">اتصالات</p>
        <div class="mx-2 my-3 hidden border-t border-line-border" :class="collapsed && 'lg:block'"></div>

        <x-dashboard.sidebar-link icon="key" disabled badge="به‌زودی">
            اعتبارنامه‌ها
        </x-dashboard.sidebar-link>
    </nav>
</aside>

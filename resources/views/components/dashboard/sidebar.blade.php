@php
    $user = auth()->user();
    $maxWorkflows = (int) config('workflow.max_per_user', 10);
    $totalWorkflows = $user ? $user->workflows()->count() : 0;
    $activeWorkflows = $user ? $user->workflows()->where('is_active', true)->count() : 0;
    $totalCredentials = $user ? $user->credentials()->count() : 0;
    $usagePercent = $maxWorkflows > 0 ? min(100, (int) round(($totalWorkflows / $maxWorkflows) * 100)) : 0;
@endphp

<div x-show="sidebarOpen"
     x-cloak
     x-transition.opacity
     @click="sidebarOpen = false"
     class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm lg:hidden"></div>

<aside x-cloak
       :class="[
           sidebarOpen ? 'translate-x-0' : 'translate-x-full',
           collapsed ? 'lg:w-20' : 'lg:w-68'
       ]"
       class="fixed inset-y-0 right-0 z-40 flex w-68 shrink-0 flex-col border-l border-line-border bg-surface-raised/95 backdrop-blur-xl transition-[transform,width] duration-200 lg:static lg:translate-x-0">

    <div @pointerdown.prevent="startResize($event)"
         @dblclick="toggleSidebar()"
         title="برای باز یا بسته کردن بکشید"
         class="group/resize absolute inset-y-0 -left-1.5 z-50 hidden w-3 cursor-col-resize touch-none lg:block">
        <span :class="resizing ? 'bg-primary' : 'bg-transparent group-hover/resize:bg-primary/50'"
              class="absolute inset-y-0 left-1/2 w-0.5 -translate-x-1/2 transition-colors duration-150"></span>
        <span :class="resizing ? 'bg-primary opacity-100' : 'bg-content-muted/40 opacity-0 group-hover/resize:opacity-100'"
              class="absolute left-1/2 top-1/2 h-9 w-1 -translate-x-1/2 -translate-y-1/2 rounded-full transition-all duration-150"></span>
    </div>

    <div class="flex h-16 shrink-0 items-center justify-between border-b border-line-border/70 px-4"
         :class="collapsed && 'lg:justify-center lg:px-0'">
        <x-layouts.logo :href="route('app.workflows.index')" logoClasses="h-10 w-auto" x-bind:class="collapsed && 'lg:hidden'"/>

        <a href="{{ route('app.workflows.index') }}"
           aria-label="{{ 'لوگو ' . config('app.site_name') }}"
           class="hidden items-center justify-center"
           :class="collapsed && 'lg:flex'">
            <img src="{{ asset('images/logo/favicon.png') }}"
                 alt="{{ config('app.name') }}"
                 class="size-9 rounded-xl object-contain">
        </a>

        <button type="button"
                @click="sidebarOpen = false"
                aria-label="بستن منو"
                class="flex size-8 items-center justify-center rounded-xl text-content-muted transition hover:bg-line-border/60 hover:text-content-main lg:hidden">
            <x-ui.icon name="x" class="size-4.5"/>
        </button>
    </div>

    <nav class="custom-scrollbar flex-1 space-y-1.5 overflow-y-auto overflow-x-hidden p-3">
        <div class="px-2 pb-1 pt-1.5" :class="collapsed && 'lg:hidden'">
            <span class="text-[11px] font-bold tracking-wide text-content-muted/70">اتوماسیون</span>
        </div>

        <x-dashboard.sidebar-link
            icon="hierarchy-2"
            :href="route('app.workflows.index')"
            :active="request()->routeIs('app.workflows.*')"
            :count="$totalWorkflows">
            ورکفلوها
        </x-dashboard.sidebar-link>

        <x-dashboard.sidebar-link
            icon="history"
            :href="Route::has('app.runs.index') ? route('app.runs.index') : '#'"
            :active="request()->routeIs('app.runs.*')"
            :disabled="! Route::has('app.runs.index')"
            :badge="Route::has('app.runs.index') ? null : 'به‌زودی'">
            تاریخچه اجراها
        </x-dashboard.sidebar-link>

        <div class="my-3 border-t border-line-border/60"></div>

        <div class="px-2 pb-1" :class="collapsed && 'lg:hidden'">
            <span class="text-[11px] font-bold tracking-wide text-content-muted/70">زیرساخت و اتصالات</span>
        </div>

        <x-dashboard.sidebar-link
            icon="key"
            :href="route('app.credentials.index')"
            :active="request()->routeIs('app.credentials.*')"
            :count="$totalCredentials">
            اعتبارنامه‌ها
        </x-dashboard.sidebar-link>
    </nav>

    <div class="shrink-0 space-y-3 border-t border-line-border/70 p-3">
        <div :class="collapsed && 'lg:hidden'"
             class="rounded-2xl border border-line-border/80 bg-surface-card p-3.5 shadow-2xs">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-content-main">ظرفیت ورکفلوها</span>
                <span class="font-mono text-[11px] font-bold tabular-nums text-content-muted" dir="ltr">
                    {{ $totalWorkflows }} / {{ $maxWorkflows }}
                </span>
            </div>

            <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-line-border/70">
                <div @class([
                        'h-full rounded-full transition-all duration-300',
                        'bg-primary' => $usagePercent >= 90,
                        'bg-amber-500' => $usagePercent >= 70 && $usagePercent < 90,
                        'bg-emerald-500' => $usagePercent < 70,
                    ])
                     style="width: {{ $usagePercent }}%"></div>
            </div>

            <div class="mt-2.5 flex items-center justify-between text-[11px] text-content-muted">
                <span class="flex items-center gap-1.5">
                    <span class="size-1.5 rounded-full {{ $activeWorkflows > 0 ? 'bg-emerald-500' : 'bg-content-muted/50' }}"></span>
                    <span>{{ $activeWorkflows }} ورکفلو فعال</span>
                </span>
                <span>{{ $totalCredentials }} کلید متصل</span>
            </div>
        </div>

        @if ($user)
            <div class="flex items-center justify-between gap-2.5 rounded-xl bg-surface-card/60 p-2"
                 :class="collapsed && 'lg:justify-center lg:bg-transparent lg:p-0'">
                <div class="flex min-w-0 flex-1 items-center gap-2.5" :class="collapsed && 'lg:flex-none'">
                    @if ($user->avatar)
                        <img src="{{ $user->avatar }}"
                             alt="{{ $user->name }}"
                             class="size-9 shrink-0 rounded-xl object-cover ring-1 ring-line-border">
                    @else
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/15 font-mono text-xs font-bold text-primary ring-1 ring-primary/20">
                            <span data-initial="{{ mb_substr($user->name, 0, 1) }}"
                                  x-text="$store.userProfile?.name ? $store.userProfile.name.slice(0, 1) : $el.dataset.initial">
                                {{ mb_substr($user->name, 0, 1) }}
                            </span>
                        </span>
                    @endif

                    <div class="min-w-0 flex-1" :class="collapsed && 'lg:hidden'">
                        <p dir="auto"
                           data-name="{{ $user->name }}"
                           class="truncate text-start text-xs font-bold text-content-main"
                           x-text="$store.userProfile?.name || $el.dataset.name">{{ $user->name }}</p>
                        <p class="truncate text-left font-mono text-[10px] text-content-muted" dir="ltr">{{ $user->email }}</p>
                    </div>
                </div>

                <button type="button"
                        :class="collapsed && 'lg:hidden'"
                        @click="$dispatch('open-modal', 'confirm-logout')"
                        title="خروج از حساب"
                        aria-label="خروج از حساب"
                        class="flex size-8 shrink-0 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary cursor-pointer">
                    <x-ui.icon name="logout" class="size-4"/>
                </button>
            </div>
        @endif
    </div>
</aside>

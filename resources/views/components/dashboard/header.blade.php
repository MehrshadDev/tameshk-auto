@props([
    'heading' => null,
    'subheading' => null,
])

@php
    use App\Subscriptions\SubscriptionManager;

    $user = auth()->user();
    $avatarUrl = $user?->avatarUrl();
    $firstName = $user ? explode(' ', trim($user->name))[0] : '';
    $activeCount = $user ? $user->workflows()->where('is_active', true)->count() : 0;
    $subscriptions = app(SubscriptionManager::class);
    $saasEnabled = $subscriptions->isSaasEnabled();
    $currentPlan = ($user && $saasEnabled) ? $subscriptions->resolvePlan($user) : null;
    $planSlug = $currentPlan?->slug ?? 'free';
    $isPro = $planSlug === 'pro';
    $isBusiness = $planSlug === 'business';
@endphp

<header class="relative z-30 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-line-border bg-surface-raised/90 px-4 backdrop-blur-xl sm:px-6">
    <div class="flex min-w-0 items-center gap-2.5">
        <button type="button"
                @click="sidebarOpen = true"
                aria-label="منو"
                class="flex size-9 items-center justify-center rounded-xl border border-line-border bg-surface-card text-content-muted transition hover:text-content-main lg:hidden">
            <x-ui.icon name="menu-2" class="size-5"/>
        </button>

        <button type="button"
                @click="toggleSidebar()"
                aria-label="جمع کردن یا باز کردن سایدبار"
                class="hidden size-9 items-center justify-center rounded-xl text-content-muted transition hover:bg-line-border/60 hover:text-content-main lg:flex cursor-pointer">
            <x-ui.icon name="layout-sidebar-right-collapse" x-show="!collapsed" class="size-5"/>
            <x-ui.icon name="layout-sidebar-right-expand" x-show="collapsed" x-cloak class="size-5"/>
        </button>

        <span class="hidden h-5 w-px bg-line-border/80 lg:block"></span>

        @if ($heading)
            <div class="min-w-0">
                <h1 class="truncate text-sm font-bold text-content-main sm:text-base">{{ $heading }}</h1>
                @if ($subheading)
                    <p class="hidden truncate text-[11px] text-content-muted sm:block">{{ $subheading }}</p>
                @endif
            </div>
        @endif
    </div>

    <div class="flex shrink-0 items-center gap-2">
        @if ($activeCount > 0)
            <div class="hidden items-center gap-2 rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-500 sm:flex">
                <span class="size-2 animate-pulse rounded-full bg-emerald-500"></span>
                <span>{{ $activeCount }} ورکفلو فعال</span>
            </div>
        @endif

        <button type="button"
                x-data="{ dark: document.documentElement.classList.contains('dark') }"
                @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('theme', dark ? 'dark' : 'light')"
                aria-label="تغییر تم"
                class="flex size-9 items-center justify-center rounded-xl border border-line-border bg-surface-card text-content-muted transition hover:border-content-muted/40 hover:text-content-main cursor-pointer">
            <x-ui.icon name="moon" x-show="!dark" class="size-4.5"/>
            <x-ui.icon name="sun" x-show="dark" x-cloak class="size-4.5"/>
        </button>

        @if ($user)
            <x-ui.dropdown align="left" width="w-64">
                <x-slot:trigger>
                    <button type="button"
                            dir="ltr"
                        @class([
                            'flex items-center gap-2 rounded-xl border p-1 pr-2.5 text-xs font-semibold text-content-main transition cursor-pointer',
                            'border-amber-500/40 bg-linear-to-r from-amber-500/10 to-surface-card hover:border-amber-500/60' => $isBusiness,
                            'border-primary/35 bg-linear-to-r from-primary/10 to-surface-card hover:border-primary/60' => $isPro,
                            'border-line-border bg-surface-card hover:border-content-muted/40' => ! $isPro && ! $isBusiness,
                        ])>
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}"
                                 alt="{{ $user->name }}"
                                @class([
                                    'size-7 shrink-0 rounded-lg object-cover',
                                    'ring-2 ring-amber-500' => $isBusiness,
                                    'ring-2 ring-primary' => $isPro,
                                ])>
                        @else
                            <span data-initial="{{ mb_substr($user->name, 0, 1) }}"
                                  x-text="$store.userProfile?.name ? $store.userProfile.name.trim().slice(0, 1).toUpperCase() : $el.dataset.initial"
                                @class([
                                    'flex size-7 shrink-0 items-center justify-center rounded-lg font-mono text-xs font-bold text-white',
                                    'bg-linear-to-br from-amber-400 to-orange-500' => $isBusiness,
                                    'bg-linear-to-br from-primary to-rose-500' => $isPro,
                                    'bg-primary' => ! $isPro && ! $isBusiness,
                                ])>
                                {{ mb_substr($user->name, 0, 1) }}
                            </span>
                        @endif

                        <span dir="auto"
                              data-name="{{ $user->name }}"
                              class="hidden max-w-28 truncate sm:block"
                              x-text="($store.userProfile?.name || $el.dataset.name).trim().split(/\s+/)[0]">{{ $firstName }}</span>

                        @if ($isBusiness || $isPro)
                            <span dir="rtl"
                                @class([
                                    'hidden items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold sm:inline-flex',
                                    'bg-amber-500/15 text-amber-500' => $isBusiness,
                                    'bg-primary/15 text-primary' => $isPro,
                                ])>
                                {{ $currentPlan->name }}
                            </span>
                        @endif

                        <x-ui.icon name="chevron-down" class="size-3.5 shrink-0 text-content-muted"/>
                    </button>
                </x-slot:trigger>

                <div dir="ltr" class="mb-1.5 flex items-center gap-2.5 rounded-xl bg-surface-card/80 p-2.5 text-left">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="size-10 shrink-0 rounded-xl object-cover ring-1 ring-line-border">
                    @else
                        <span data-initial="{{ mb_substr($user->name, 0, 1) }}"
                              x-text="$store.userProfile?.name ? $store.userProfile.name.trim().slice(0, 1).toUpperCase() : $el.dataset.initial"
                            @class([
                                'flex size-10 shrink-0 items-center justify-center rounded-xl font-mono text-xs font-bold ring-1',
                                'bg-amber-500/15 text-amber-500 ring-amber-500/30' => $isBusiness,
                                'bg-primary/15 text-primary ring-primary/20' => ! $isBusiness,
                            ])>
                            {{ mb_substr($user->name, 0, 1) }}
                        </span>
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <p dir="auto"
                               data-name="{{ $user->name }}"
                               class="truncate text-xs font-bold text-content-main"
                               x-text="$store.userProfile?.name || $el.dataset.name">{{ $user->name }}</p>
                            @if ($isBusiness || $isPro)
                                <span dir="rtl"
                                    @class([
                                        'shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-bold',
                                        'bg-amber-500/15 text-amber-500' => $isBusiness,
                                        'bg-primary/15 text-primary' => $isPro,
                                    ])>
                                    {{ $currentPlan->name }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-0.5 truncate font-mono text-[10px] text-content-muted">{{ $user->email }}</p>
                    </div>
                </div>

                <x-ui.dropdown-item icon="pencil" @click="$dispatch('open-modal', 'edit-profile-modal')">
                    ویرایش نام کاربری
                </x-ui.dropdown-item>

                <div class="my-1 border-t border-line-border/60"></div>

                <x-ui.dropdown-item :href="route('app.workflows.index')" icon="hierarchy-2">
                    ورکفلوهای من
                </x-ui.dropdown-item>

                <x-ui.dropdown-item :href="route('app.runs.index')" icon="history">
                    تاریخچه اجراها
                </x-ui.dropdown-item>

                <x-ui.dropdown-item :href="route('app.credentials.index')" icon="key">
                    اعتبارنامه‌ها
                </x-ui.dropdown-item>

                @if ($saasEnabled)
                    <x-ui.dropdown-item :href="route('app.billing.index')" icon="credit-card">
                        اشتراک و صورتحساب
                    </x-ui.dropdown-item>
                @endif

                <div class="my-1 border-t border-line-border/60"></div>

                <x-ui.dropdown-item icon="logout" danger @click="$dispatch('open-modal', 'confirm-logout')">
                    خروج از حساب کاربری
                </x-ui.dropdown-item>
            </x-ui.dropdown>
        @endif
    </div>
</header>

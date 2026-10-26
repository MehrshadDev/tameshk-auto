@props(['heading' => null])

@php
    $user = auth()->user();
@endphp

<header class="relative z-20 flex h-16 shrink-0 items-center gap-3 border-b border-line-border bg-surface-raised px-4 backdrop-blur-xl sm:px-6">
    <button @click="sidebarOpen = true" aria-label="منو" class="rounded-lg p-2 text-content-muted transition hover:bg-line-border/60 lg:hidden flex justify-center items-center">
        <x-ui.icon name="menu-2" class="size-6"/>
    </button>

    <button @click="toggleSidebar()" aria-label="جمع کردن یا باز کردن سایدبار" class="hidden rounded-lg p-2 text-content-muted transition hover:bg-line-border/60 hover:text-content-main lg:flex justify-center items-center">
        <x-ui.icon name="layout-sidebar-right-collapse" x-show="!collapsed"/>
        <x-ui.icon name="layout-sidebar-right-expand" x-show="collapsed" x-cloak/>
    </button>

    @if ($heading)
        <h1 class="text-base font-bold sm:text-lg">{{ $heading }}</h1>
    @endif

    <div class="mr-auto flex items-center gap-1">
        <button x-data="{ dark: document.documentElement.classList.contains('dark') }"
                @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('theme', dark ? 'dark' : 'light')"
                aria-label="تغییر تم"
                class="rounded-lg p-2 text-content-muted transition hover:bg-line-border/60 hover:text-content-main flex justify-center items-center">
            <x-ui.icon name="moon" x-show="!dark"/>
            <x-ui.icon name="sun" x-show="dark" x-cloak/>
        </button>

        <x-ui.dropdown align="left">
            <x-slot:trigger>
                <button class="flex items-center gap-2 rounded-xl p-1.5 transition hover:bg-line-border/60">
                    @if ($user->avatar)
                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="size-8 rounded-full object-cover">
                    @else
                        <span class="flex size-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">
                            {{ mb_substr($user->name, 0, 1) }}
                        </span>
                    @endif
                    <span class="hidden max-w-32 truncate text-sm sm:block">{{ $user->name }}</span>
                    <x-ui.icon name="chevron-down" class="hidden size-4 text-content-muted sm:inline-block"/>
                </button>
            </x-slot:trigger>

            <div class="border-b border-line-border px-3 py-2.5 mb-1.5">
                <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                <p class="truncate text-xs text-content-muted" dir="ltr">{{ $user->email }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.dropdown-item type="submit" icon="logout" danger>خروج</x-ui.dropdown-item>
            </form>
        </x-ui.dropdown>
    </div>
</header>

@php
    $links = [
        ['label' => 'قابلیت‌ها', 'href' => '#'],
        ['label' => 'اتوماسیون‌ها', 'href' => '#'],
        ['label' => 'تعرفه‌ها', 'href' => '#'],
        ['label' => 'مستندات', 'href' => '#'],
    ];
@endphp

<header
    x-data="{ open: false }"
    class="sticky top-0 z-50 w-full border-b border-line-border bg-surface-raised/90 font-vazirmatn-rd backdrop-blur-xl"
>
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-6">
        <div class="flex items-center gap-8">
            <a href="/" aria-label="صفحه اصلی تمشک">
                <x-layouts.logo />
            </a>

            <nav class="hidden items-center gap-1 md:flex">
                @foreach ($links as $link)
                    <a
                        href="{{ $link['href'] }}"
                        class="rounded-lg px-3 py-2 text-sm text-content-muted transition-colors hover:bg-surface-base hover:text-content-main"
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="hidden items-center gap-2 md:flex">
            <x-ui.button href="/login" variant="ghost">ورود</x-ui.button>
            <x-ui.button href="/register">ثبت‌نام</x-ui.button>
        </div>

        <button
            type="button"
            @click="open = !open"
            :aria-expanded="open"
            aria-label="منو"
            class="rounded-lg p-2 text-content-muted transition-colors hover:bg-surface-base hover:text-content-main md:hidden"
        >
            <svg
                class="h-6 w-6"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path x-show="!open" d="M4 7h16M4 12h16M4 17h16" />
                <path x-show="open" x-cloak d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </div>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity
        @click.outside="open = false"
        @keydown.escape.window="open = false"
        class="border-t border-line-border bg-surface-raised px-6 pb-4 pt-2 md:hidden"
    >
        <nav class="flex flex-col">
            @foreach ($links as $link)
                <a
                    href="{{ $link['href'] }}"
                    @click="open = false"
                    class="rounded-lg px-3 py-3 text-sm text-content-muted transition-colors hover:bg-surface-base hover:text-content-main"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="mt-3 grid grid-cols-2 gap-2">
            <x-ui.button href="/login" variant="secondary">ورود</x-ui.button>
            <x-ui.button href="/register">ثبت‌نام</x-ui.button>
        </div>
    </div>
</header>

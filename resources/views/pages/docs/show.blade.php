@php use App\Docs\DocumentationRepository; @endphp
<x-layouts.app
    :title="$article['title'] . ' | مستندات ' . config('app.name')"
    :description="$article['description']"
    :header_visibility="false"
    :footer_visibility="true">

    @php
        $allArticles = DocumentationRepository::allArticles();
    @endphp

    <div x-data="{
             mobileMenuOpen: false,
             searchModalOpen: false,
             searchQuery: '',
             articles: @js($allArticles),
             openSearch() {
                 this.searchModalOpen = true;
                 this.$nextTick(() => this.$refs.docSearchInput?.focus());
             },
             get filteredArticles() {
                 const q = this.searchQuery.trim().toLowerCase();
                 if (!q) return this.articles;
                 return this.articles.filter(a =>
                     a.title.toLowerCase().includes(q) ||
                     a.description.toLowerCase().includes(q) ||
                     a.section_title.toLowerCase().includes(q)
                 );
             },
             init() {
                 const onKeyDown = (e) => {
                     const isK = e.code === 'KeyK' || e.key?.toLowerCase() === 'k' || e.key === 'ن' || e.key === '«';

                     if ((e.ctrlKey || e.metaKey) && isK) {
                         e.preventDefault();
                         e.stopPropagation();
                         this.openSearch();
                         return;
                     }

                     if (e.key === '/' && !this.searchModalOpen && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
                         e.preventDefault();
                         this.openSearch();
                     }
                 };

                 window.addEventListener('keydown', onKeyDown, true);
             }
         }"
         @keydown.escape.window="mobileMenuOpen = false; searchModalOpen = false"
         class="min-h-screen bg-surface-base">

        <header class="sticky top-0 z-40 border-b border-line-border bg-surface-raised/90 backdrop-blur-xl">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <button type="button"
                            @click="mobileMenuOpen = true"
                            aria-label="فهرست مستندات"
                            class="flex size-9 items-center justify-center rounded-xl border border-line-border bg-surface-card text-content-muted transition hover:text-content-main lg:hidden cursor-pointer">
                        <x-ui.icon name="menu-2" class="size-5"/>
                    </button>

                    <x-layouts.logo :href="route('home')" logoClasses="h-9 w-auto"/>

                    <span class="hidden h-5 w-px bg-line-border sm:block"></span>

                    <a href="{{ route('docs.index') }}"
                       class="hidden items-center gap-1.5 rounded-lg bg-primary/10 px-2.5 py-1 text-xs font-extrabold text-primary sm:inline-flex">
                        <x-ui.icon name="file-text" class="size-3.5"/>
                        <span>مرکز آموزش و مستندات</span>
                    </a>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <button type="button"
                            @click="searchModalOpen = true; $nextTick(() => $refs.docSearchInput?.focus())"
                            class="flex h-9 w-9 items-center justify-center gap-2.5 rounded-xl border border-line-border bg-surface-card px-3 text-xs text-content-muted transition hover:border-primary/40 hover:text-content-main sm:w-64 sm:justify-between cursor-pointer">
                        <span class="flex items-center gap-2">
                            <x-ui.icon name="search" class="size-4 text-content-muted"/>
                            <span class="hidden sm:inline">جستجو در مستندات...</span>
                        </span>
                        <kbd dir="ltr"
                             class="hidden rounded-md border border-line-border bg-surface-base px-1.5 py-0.5 font-mono text-[10px] text-content-muted sm:inline-block">
                            Ctrl K
                        </kbd>
                    </button>

                    <button type="button"
                            x-data="{ dark: document.documentElement.classList.contains('dark') }"
                            @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('theme', dark ? 'dark' : 'light')"
                            aria-label="تغییر تم"
                            class="flex size-9 items-center justify-center rounded-xl border border-line-border bg-surface-card text-content-muted transition hover:border-content-muted/40 hover:text-content-main cursor-pointer">
                        <x-ui.icon name="moon" x-show="!dark" class="size-4.5"/>
                        <x-ui.icon name="sun" x-show="dark" x-cloak class="size-4.5"/>
                    </button>

                    @auth
                        <x-ui.button :href="route('app.workflows.index')" size="md">
                            <span>ورود به پنل</span>
                            <x-ui.icon name="chevron-left" class="size-4"/>
                        </x-ui.button>
                    @else
                        <x-ui.button :href="route('login')" variant="secondary" size="md">ورود</x-ui.button>
                    @endauth
                </div>
            </div>
        </header>

        <div class="mx-auto flex max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:py-10">
            <div x-show="mobileMenuOpen"
                 x-cloak
                 x-transition.opacity
                 @click="mobileMenuOpen = false"
                 class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"></div>

            <aside :class="mobileMenuOpen ? 'translate-x-0' : 'translate-x-full'"
                   class="fixed inset-y-0 right-0 z-50 w-72 shrink-0 overflow-y-auto border-l border-line-border bg-surface-raised p-5 transition-transform duration-200 lg:static lg:z-auto lg:w-64 lg:translate-x-0 lg:border-0 lg:bg-transparent lg:p-0">

                <div class="mb-5 flex items-center justify-between lg:hidden">
                    <span class="text-sm font-extrabold text-content-main">سرفصل‌های آموزشی</span>
                    <button type="button"
                            @click="mobileMenuOpen = false"
                            class="flex size-8 items-center justify-center rounded-lg text-content-muted hover:bg-line-border/60">
                        <x-ui.icon name="x" class="size-4"/>
                    </button>
                </div>

                <nav class="sticky top-24 space-y-6">
                    @foreach ($sections as $section)
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 px-2 text-xs font-extrabold text-content-main">
                                <span class="flex size-6 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <x-ui.icon :name="$section['icon'] ?? 'box'" class="size-3.5"/>
                                </span>
                                <span>{{ $section['title'] }}</span>
                            </div>

                            <ul class="space-y-1 border-r border-line-border/80 pr-2.5">
                                @foreach ($section['items'] as $item)
                                    @php
                                        $isActive = $item['slug'] === $article['slug'];
                                    @endphp
                                    <li>
                                        <a href="{{ route('docs.show', $item['slug']) }}"
                                            @class([
                                                'flex items-center justify-between rounded-xl px-3 py-2 text-xs transition',
                                                'bg-primary/10 font-bold text-primary' => $isActive,
                                                'font-medium text-content-muted hover:bg-surface-card hover:text-content-main' => ! $isActive,
                                            ])>
                                            <span class="truncate">{{ $item['title'] }}</span>
                                            @if ($isActive)
                                                <span class="size-1.5 shrink-0 rounded-full bg-primary"></span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </nav>
            </aside>

            <article class="min-w-0 flex-1">
                <div class="rounded-3xl border border-line-border bg-surface-card p-6 shadow-2xs sm:p-8 lg:p-10">
                    <div class="border-b border-line-border/70 pb-6">
                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-primary">
                            <span>{{ $article['section_title'] }}</span>
                            <span class="text-content-muted">•</span>
                            <span class="inline-flex items-center gap-1 text-content-muted">
                                <x-ui.icon name="clock" class="size-3.5"/>
                                <span>زمان مطالعه: {{ $article['readingTime'] }}</span>
                            </span>
                        </div>

                        <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-content-main sm:text-3xl">
                            {{ $article['title'] }}
                        </h1>

                        <p class="mt-2.5 text-sm leading-7 text-content-muted">
                            {{ $article['description'] }}
                        </p>
                    </div>

                    <div class="docs-prose mt-8 space-y-6 text-sm leading-8 text-content-main">
                        @include($article['view'])
                    </div>

                    <div class="mt-12 grid grid-cols-1 gap-4 border-t border-line-border/70 pt-6 sm:grid-cols-2">
                        @if ($prev)
                            <a href="{{ route('docs.show', $prev['slug']) }}"
                               class="group flex flex-col rounded-2xl border border-line-border bg-surface-base/60 p-4 text-start transition hover:border-primary/40 hover:bg-surface-raised">
                                <span class="flex items-center gap-1 text-[11px] font-semibold text-content-muted">
                                    <x-ui.icon name="chevron-right"
                                               class="size-3.5 transition-transform group-hover:translate-x-0.5"/>
                                    <span>مطلب قبلی</span>
                                </span>
                                <span class="mt-1 text-xs font-extrabold text-content-main group-hover:text-primary">
                                    {{ $prev['title'] }}
                                </span>
                            </a>
                        @else
                            <div></div>
                        @endif

                        @if ($next)
                            <a href="{{ route('docs.show', $next['slug']) }}"
                               class="group flex flex-col items-end rounded-2xl border border-line-border bg-surface-base/60 p-4 text-end transition hover:border-primary/40 hover:bg-surface-raised">
                                <span class="flex items-center gap-1 text-[11px] font-semibold text-content-muted">
                                    <span>مطلب بعدی</span>
                                    <x-ui.icon name="chevron-left"
                                               class="size-3.5 transition-transform group-hover:-translate-x-0.5"/>
                                </span>
                                <span class="mt-1 text-xs font-extrabold text-content-main group-hover:text-primary">
                                    {{ $next['title'] }}
                                </span>
                            </a>
                        @endif
                    </div>
                </div>
            </article>

            @if (! empty($article['toc']))
                <aside class="hidden w-56 shrink-0 xl:block">
                    <div class="sticky top-24 space-y-3 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
                        <div class="flex items-center gap-2 text-xs font-extrabold text-content-main">
                            <x-ui.icon name="list" class="size-4 text-primary"/>
                            <span>در این صفحه</span>
                        </div>
                        <ul class="space-y-1.5 border-r border-line-border/70 pr-3 text-xs">
                            @foreach ($article['toc'] as $heading)
                                <li>
                                    <a href="#{{ $heading['id'] }}"
                                       class="block py-1 font-medium text-content-muted transition hover:text-primary">
                                        {{ $heading['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </aside>
            @endif
        </div>

        <div x-show="searchModalOpen"
             x-cloak
             class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-20">
            <div x-show="searchModalOpen"
                 x-transition.opacity
                 @click="searchModalOpen = false"
                 class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>

            <div x-show="searchModalOpen"
                 x-transition
                 class="relative z-10 w-full max-w-xl overflow-hidden rounded-2xl border border-line-border bg-surface-raised shadow-2xl">
                <div class="flex items-center border-b border-line-border px-4">
                    <x-ui.icon name="search" class="size-5 text-content-muted"/>
                    <input x-ref="docSearchInput"
                           type="text"
                           x-model="searchQuery"
                           placeholder="جستجو در آموزش‌ها و مستندات..."
                           class="h-13 w-full bg-transparent px-3 text-sm text-content-main placeholder:text-content-muted/60 focus:outline-none"/>
                    <button type="button"
                            @click="searchModalOpen = false"
                            class="rounded-lg border border-line-border px-2 py-1 font-mono text-[10px] text-content-muted hover:text-content-main cursor-pointer">
                        ESC
                    </button>
                </div>

                <div class="custom-scrollbar max-h-80 overflow-y-auto p-2">
                    <template x-for="item in filteredArticles" :key="item.slug">
                        <a :href="'/docs/' + item.slug"
                           class="flex items-start justify-between gap-3 rounded-xl p-3 transition hover:bg-surface-card">
                            <div class="min-w-0">
                                <span class="text-[10px] font-bold text-primary" x-text="item.section_title"></span>
                                <p class="mt-0.5 text-xs font-extrabold text-content-main" x-text="item.title"></p>
                                <p class="mt-0.5 truncate text-[11px] text-content-muted" x-text="item.description"></p>
                            </div>
                            <x-ui.icon name="chevron-left" class="mt-2 size-4 shrink-0 text-content-muted"/>
                        </a>
                    </template>

                    <div x-show="filteredArticles.length === 0" class="py-10 text-center text-xs text-content-muted">
                        نتیجه‌ای برای عبارت واردشده یافت نشد.
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

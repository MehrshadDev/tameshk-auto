@props([
    'heading' => null,
    'subheading' => null,
])

@php
    $user = auth()->user();
    $activeCount = $user ? $user->workflows()->where('is_active', true)->count() : 0;
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
                    <button type="button" class="flex items-center gap-2 rounded-xl border border-line-border bg-surface-card p-1 pe-2.5 text-xs font-semibold text-content-main transition hover:border-content-muted/40 cursor-pointer">
                        @if ($user->avatar)
                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="size-7 rounded-lg object-cover">
                        @else
                            <span data-initial="{{ mb_substr($user->name, 0, 1) }}"
                                  class="flex size-7 items-center justify-center rounded-lg bg-primary font-mono text-xs font-bold text-white"
                                  x-text="$store.userProfile?.name ? $store.userProfile.name.slice(0, 1) : $el.dataset.initial">
                                {{ mb_substr($user->name, 0, 1) }}
                            </span>
                        @endif
                        <span dir="auto"
                              data-name="{{ $user->name }}"
                              class="hidden max-w-28 truncate sm:block"
                              x-text="$store.userProfile?.name || $el.dataset.name">{{ $user->name }}</span>
                        <x-ui.icon name="chevron-down" class="size-3.5 text-content-muted"/>
                    </button>
                </x-slot:trigger>

                <div data-name="{{ $user->name }}"
                     data-profile-url="{{ route('app.api.profile.update') }}"
                     x-data="{
                         editingName: false,
                         draftName: '',
                         savingName: false,
                         init() {
                             this.draftName = $store.userProfile?.name || this.$el.dataset.name;
                         },
                         startEdit() {
                             this.draftName = $store.userProfile?.name || this.$el.dataset.name;
                             this.editingName = true;
                             this.$nextTick(() => {
                                 this.$refs.nameInput?.focus();
                                 this.$refs.nameInput?.select();
                             });
                         },
                         async saveName() {
                             const trimmed = this.draftName.trim();
                             const current = $store.userProfile?.name || this.$el.dataset.name;

                             if (!trimmed || trimmed.length < 2 || trimmed.length > 50 || trimmed === current) {
                                 this.editingName = false;
                                 this.draftName = current;
                                 return;
                             }

                             this.savingName = true;

                             try {
                                 const res = await fetch(this.$el.dataset.profileUrl, {
                                     method: 'PATCH',
                                     headers: {
                                         'Content-Type': 'application/json',
                                         'Accept': 'application/json',
                                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? ''
                                     },
                                     body: JSON.stringify({ name: trimmed })
                                 });

                                 const data = await res.json().catch(() => ({}));

                                 if (res.ok && data.name) {
                                     $store.userProfile.name = data.name;
                                     this.draftName = data.name;
                                     window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'نام کاربری بروزرسانی شد.', type: 'success' } }));
                                 } else {
                                     this.draftName = current;
                                     window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message || 'نام واردشده معتبر نیست.', type: 'error' } }));
                                 }
                             } catch {
                                 this.draftName = current;
                                 window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'خطا در ذخیره نام.', type: 'error' } }));
                             } finally {
                                 this.savingName = false;
                                 this.editingName = false;
                             }
                         }
                     }"
                     @click.stop
                     class="mb-1.5 border-b border-line-border/70 px-3 py-2.5">
                    <template x-if="!editingName">
                        <button type="button"
                                @click="startEdit()"
                                title="برای تغییر نام کلیک کنید"
                                class="group/name flex w-full items-center justify-between gap-2 rounded-lg px-1.5 py-1 transition hover:bg-line-border/50 cursor-pointer">
                            <span dir="auto"
                                  class="min-w-0 flex-1 truncate text-start text-xs font-bold text-content-main"
                                  x-text="$store.userProfile?.name || draftName"></span>
                            <x-ui.icon name="pencil" class="size-3.5 shrink-0 text-content-muted opacity-0 transition group-hover/name:opacity-100"/>
                        </button>
                    </template>

                    <template x-if="editingName">
                        <div class="flex items-center gap-1.5">
                            <input x-ref="nameInput"
                                   type="text"
                                   dir="auto"
                                   maxlength="50"
                                   x-model="draftName"
                                   :disabled="savingName"
                                   @keydown.enter.prevent="saveName()"
                                   @keydown.escape.prevent.stop="editingName = false"
                                   @blur="saveName()"
                                   class="w-full rounded-lg border border-primary bg-surface-base px-2 py-1 text-start text-xs font-bold text-content-main focus:outline-none focus:ring-2 focus:ring-primary/30"/>
                        </div>
                    </template>

                    <p class="mt-1 px-1.5 truncate text-left font-mono text-[10px] text-content-muted" dir="ltr">{{ $user->email }}</p>
                </div>

                <x-ui.dropdown-item :href="route('app.workflows.index')" icon="hierarchy-2">
                    ورکفلوهای من
                </x-ui.dropdown-item>

                <x-ui.dropdown-item :href="route('app.credentials.index')" icon="key">
                    اعتبارنامه‌ها
                </x-ui.dropdown-item>

                <div class="my-1 border-t border-line-border/60"></div>

                <x-ui.dropdown-item icon="logout" danger @click="$dispatch('open-modal', 'confirm-logout')">
                    خروج از حساب کاربری
                </x-ui.dropdown-item>
            </x-ui.dropdown>
        @endif
    </div>
</header>

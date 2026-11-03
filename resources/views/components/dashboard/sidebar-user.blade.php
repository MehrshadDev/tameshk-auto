@props([
    'user',
    'isBusiness' => false,
])

@if ($user)
    @php
        $avatarUrl = $user->avatarUrl();
    @endphp
    <div class="flex items-center justify-between gap-2.5 rounded-xl bg-surface-card/60 p-2"
         :class="collapsed && 'lg:justify-center lg:bg-transparent lg:p-0'">

        <button type="button"
                :class="collapsed && 'lg:hidden'"
                @click="$dispatch('open-modal', 'confirm-logout')"
                title="خروج از حساب"
                aria-label="خروج از حساب"
                class="flex size-8 shrink-0 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary cursor-pointer">
            <x-ui.icon name="logout" class="size-4"/>
        </button>

        <div dir="ltr" class="flex min-w-0 flex-1 items-center gap-2.5" :class="collapsed && 'lg:flex-none'">
            @if ($avatarUrl)
                <img src="{{ $avatarUrl }}"
                     alt="{{ $user->name }}"
                     class="size-9 shrink-0 rounded-xl object-cover ring-1 ring-line-border">
            @else
                <span @class([
                    'flex size-9 shrink-0 items-center justify-center rounded-xl font-mono text-xs font-bold ring-1',
                    'bg-amber-500/15 text-amber-500 ring-amber-500/30' => $isBusiness,
                    'bg-primary/15 text-primary ring-primary/20' => ! $isBusiness,
                ])>
                    <span data-initial="{{ mb_substr($user->name, 0, 1) }}"
                          x-text="$store.userProfile?.name ? $store.userProfile.name.slice(0, 1) : $el.dataset.initial">
                        {{ mb_substr($user->name, 0, 1) }}
                    </span>
                </span>
            @endif

            <div class="min-w-0 flex-1 text-left" :class="collapsed && 'lg:hidden'">
                <p dir="auto"
                   data-name="{{ $user->name }}"
                   class="truncate text-xs font-bold text-content-main"
                   x-text="$store.userProfile?.name || $el.dataset.name">{{ $user->name }}</p>
                <p class="truncate font-mono text-[10px] text-content-muted">{{ $user->email }}</p>
            </div>
        </div>
    </div>
@endif

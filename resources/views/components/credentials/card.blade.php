@props([
    'credential',
    'definition' => null,
    'workflowsCount' => 0,
])

@php
    $icon = $definition['icon'] ?? 'key';
    $typeName = $definition['name'] ?? $credential->type;
    $isOAuth = in_array($credential->type, ['gmail', 'google'], true);
    $isConnected = ! empty($credential->get('refresh_token'));
    $connectedEmail = (string) ($credential->get('connected_email') ?? '');

    $iconStyles = match ($credential->type) {
        'telegram' => 'bg-sky-500/10 text-sky-500 ring-1 ring-sky-500/20',
        'gmail' => 'bg-rose-500/10 text-rose-500 ring-1 ring-rose-500/20',
        'google' => 'bg-emerald-500/10 text-emerald-500 ring-1 ring-emerald-500/20',
        'gemini', 'deepseek' => 'bg-blue-500/10 text-blue-500 ring-1 ring-blue-500/20',
        'anthropic', 'groq' => 'bg-amber-500/10 text-amber-500 ring-1 ring-amber-500/20',
        'openai', 'openrouter' => 'bg-purple-500/10 text-purple-500 ring-1 ring-purple-500/20',
        default => 'bg-primary/10 text-primary ring-1 ring-primary/20',
    };
@endphp

<div wire:click="openEdit({{ $credential->id }})"
     class="group relative has-aria-expanded:z-30 flex cursor-pointer items-center gap-3 rounded-2xl border border-line-border bg-surface-card p-3.5 shadow-2xs transition-all duration-150 hover:border-primary/40 hover:shadow-md select-none sm:gap-4 sm:p-4">

    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl transition-transform group-hover:scale-105 {{ $iconStyles }}">
        <x-ui.icon :name="$icon" class="size-5.5"/>
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <h3 dir="auto" class="truncate text-start text-sm font-bold text-content-main transition-colors group-hover:text-primary">
                {{ $credential->name }}
            </h3>

            <span class="rounded-lg border border-line-border/80 bg-surface-raised px-2 py-0.5 text-[11px] font-medium text-content-muted">
                {{ $typeName }}
            </span>
        </div>

        <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-content-muted">
            @if ($connectedEmail !== '')
                <span dir="ltr" class="inline-flex items-center rounded-md border border-line-border/70 bg-surface-base px-2 py-0.5 font-mono text-[10px] font-medium text-content-main">
                    {{ $connectedEmail }}
                </span>
            @else
                <span class="text-content-muted/70">ثبت‌شده {{ $credential->created_at->diffForHumans() }}</span>
            @endif
        </div>
    </div>

    <div class="hidden w-32 shrink-0 md:block">
        @if ($workflowsCount > 0)
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-line-border/80 bg-surface-raised px-2.5 py-1 text-xs font-semibold text-content-main">
                <x-ui.icon name="hierarchy-2" class="size-3.5 text-primary"/>
                <span>{{ $workflowsCount }} ورکفلو</span>
            </span>
        @else
            <span class="text-xs text-content-muted/60">بدون ورکفلو</span>
        @endif
    </div>

    <div class="hidden w-40 shrink-0 lg:block">
        @if ($credential->last_used_at)
            <div class="flex items-center gap-1.5 text-xs font-medium text-content-main">
                <x-ui.icon name="clock" class="size-3.5 text-emerald-500"/>
                <span>{{ $credential->last_used_at->diffForHumans() }}</span>
            </div>
            <p class="mt-0.5 text-[11px] text-content-muted/70">آخرین استفاده در اجرا</p>
        @else
            <span class="text-xs text-content-muted/70">هنوز استفاده نشده</span>
        @endif
    </div>

    <div class="flex shrink-0 items-center gap-2 sm:gap-2.5" @click.stop>
        @if ($isOAuth)
            @if ($isConnected)
                <x-ui.badge variant="success" dot>متصل</x-ui.badge>
            @else
                <button type="button"
                        data-cred-id="{{ $credential->id }}"
                        data-cred-type="{{ $credential->type }}"
                        @click="openGoogleOAuth(Number($el.dataset.credId), $el.dataset.credType)"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-500 transition hover:bg-amber-500/20 cursor-pointer">
                    <x-ui.icon name="external-link" class="size-3.5"/>
                    <span>اتصال گوگل</span>
                </button>
            @endif
        @else
            <x-ui.badge variant="neutral">کلید API</x-ui.badge>
        @endif

        <x-ui.dropdown align="left" width="w-48">
            <x-slot:trigger>
                <button type="button"
                        aria-label="گزینه‌ها"
                        class="flex size-8.5 items-center justify-center rounded-xl border border-transparent text-content-muted transition hover:border-line-border hover:bg-surface-raised hover:text-content-main cursor-pointer">
                    <x-ui.icon name="dots-vertical" class="size-4.5"/>
                </button>
            </x-slot:trigger>

            <x-ui.dropdown-item icon="pencil"
                                wire:click="openEdit({{ $credential->id }})">
                ویرایش اعتبارنامه
            </x-ui.dropdown-item>

            @if ($isOAuth)
                <x-ui.dropdown-item icon="external-link"
                                    data-cred-id="{{ $credential->id }}"
                                    data-cred-type="{{ $credential->type }}"
                                    @click="openGoogleOAuth(Number($el.dataset.credId), $el.dataset.credType)">
                    {{ $isConnected ? 'اتصال مجدد به گوگل' : 'اتصال به حساب گوگل' }}
                </x-ui.dropdown-item>
            @endif

            <div class="my-1 border-t border-line-border/60"></div>

            <x-ui.dropdown-item icon="trash" danger
                                wire:click="confirmDelete({{ $credential->id }})">
                حذف اعتبارنامه
            </x-ui.dropdown-item>
        </x-ui.dropdown>
    </div>
</div>

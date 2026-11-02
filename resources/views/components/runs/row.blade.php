@props([
    'run',
    'definitions' => [],
])

@php
    $workflow = $run->workflow;
    $editorUrl = $workflow ? route('app.workflows.show', $workflow) : null;
    $duration = $run->durationInSeconds();

    $statusMeta = match ($run->status) {
        'success' => [
            'variant' => 'success',
            'label' => 'موفق',
            'icon' => 'check',
            'box' => 'bg-emerald-500/10 text-emerald-500 ring-1 ring-emerald-500/20',
        ],
        'failed' => [
            'variant' => 'danger',
            'label' => 'ناموفق',
            'icon' => 'alert-triangle',
            'box' => 'bg-primary/10 text-primary ring-1 ring-primary/20',
        ],
        'running' => [
            'variant' => 'info',
            'label' => 'در حال اجرا',
            'icon' => 'loader',
            'box' => 'bg-sky-500/10 text-sky-500 ring-1 ring-sky-500/20',
        ],
        'canceled' => [
            'variant' => 'warning',
            'label' => 'لغو شده',
            'icon' => 'ban',
            'box' => 'bg-amber-500/10 text-amber-500 ring-1 ring-amber-500/20',
        ],
        default => [
            'variant' => 'neutral',
            'label' => 'در انتظار',
            'icon' => 'clock',
            'box' => 'bg-line-border/50 text-content-muted',
        ],
    };

    $triggerLabel = match ($run->trigger_type) {
        'single_node' => 'تست تک‌مرحله',
        'stream' => 'اجرای تستی',
        'manual' => 'اجرای دستی / وبهوک',
        default => $run->trigger_type ?: 'اجرای خودکار',
    };

    $nodeRuns = $run->nodeRuns ?? collect();
    $uniqueNodeRuns = $nodeRuns
        ->filter(fn ($nr) => $nr->node !== null)
        ->unique(fn ($nr) => $nr->node->type)
        ->values();

    $visibleNodeRuns = $uniqueNodeRuns->take(4);
    $extraNodesCount = max(0, $uniqueNodeRuns->count() - 4);
@endphp

<div wire:click="inspectRun({{ $run->id }})"
     class="group relative has-aria-expanded:z-30 flex cursor-pointer items-center gap-3 rounded-2xl border border-line-border bg-surface-card p-3.5 shadow-2xs transition-all duration-150 hover:border-primary/40 hover:shadow-md select-none sm:gap-4 sm:p-4">

    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl transition-transform group-hover:scale-105 {{ $statusMeta['box'] }}">
        <x-ui.icon :name="$statusMeta['icon']" @class(['size-5.5', 'animate-spin' => $run->status === 'running'])/>
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <span dir="auto" class="truncate text-start text-sm font-bold text-content-main transition-colors group-hover:text-primary">
                {{ $workflow?->name ?? 'ورکفلو حذف‌شده' }}
            </span>

            <span dir="ltr" class="rounded-md border border-line-border/80 bg-surface-raised px-1.5 py-0.5 font-mono text-[10px] font-bold text-content-muted">
                #{{ $run->id }}
            </span>

            <span class="hidden rounded-lg border border-line-border/60 bg-surface-base px-2 py-0.5 text-[10px] font-medium text-content-muted sm:inline-block">
                {{ $triggerLabel }}
            </span>
        </div>

        @if ($run->error)
            <p dir="auto" class="mt-1 truncate text-start text-xs font-medium text-primary">
                {{ $run->error }}
            </p>
        @else
            <p class="mt-1 truncate text-[11px] text-content-muted/70">
                {{ $nodeRuns->count() }} مرحله ثبت‌شده • {{ $run->started_at?->diffForHumans() ?? '—' }}
            </p>
        @endif
    </div>

    @if ($visibleNodeRuns->isNotEmpty())
        <div class="hidden items-center -space-x-1.5 space-x-reverse md:flex" title="نودهای اجراشده">
            @foreach ($visibleNodeRuns as $nr)
                @php
                    $def = $definitions[$nr->node->type] ?? null;
                    $icon = $def?->icon ?? 'box';
                @endphp
                <span @class([
                          'flex size-8 items-center justify-center rounded-xl border bg-surface-raised text-content-main shadow-2xs',
                          'border-primary/50' => $nr->status === 'failed',
                          'border-line-border' => $nr->status !== 'failed',
                      ])
                      title="{{ $nr->node->name }}">
                    <x-ui.icon :name="$icon" class="size-4"/>
                </span>
            @endforeach

            @if ($extraNodesCount > 0)
                <span class="flex size-8 items-center justify-center rounded-xl border border-line-border bg-surface-raised font-mono text-[10px] font-bold tabular-nums text-content-muted shadow-2xs" dir="ltr">
                    +{{ $extraNodesCount }}
                </span>
            @endif
        </div>
    @endif

    <div class="hidden shrink-0 items-center gap-3 lg:flex">
        @if ($duration !== null)
            <span dir="ltr" class="inline-flex items-center gap-1 rounded-lg border border-line-border/70 bg-surface-base px-2.5 py-1 font-mono text-[11px] font-semibold tabular-nums text-content-muted">
                <x-ui.icon name="clock" class="size-3.5 text-content-muted/70"/>
                <span>{{ $duration }}s</span>
            </span>
        @endif

        <x-ui.badge :variant="$statusMeta['variant']" dot>{{ $statusMeta['label'] }}</x-ui.badge>
    </div>

    <div class="flex shrink-0 items-center gap-2" @click.stop>
        <button type="button"
                wire:click="inspectRun({{ $run->id }})"
                class="hidden items-center gap-1.5 rounded-xl border border-line-border bg-surface-raised px-3 py-1.5 text-xs font-semibold text-content-muted transition hover:border-primary/40 hover:text-primary sm:inline-flex cursor-pointer">
            <x-ui.icon name="eye" class="size-3.5"/>
            <span>جزئیات</span>
        </button>

        <x-ui.dropdown align="left" width="w-48">
            <x-slot:trigger>
                <button type="button"
                        aria-label="گزینه‌ها"
                        class="flex size-8.5 items-center justify-center rounded-xl border border-transparent text-content-muted transition hover:border-line-border hover:bg-surface-raised hover:text-content-main cursor-pointer">
                    <x-ui.icon name="dots-vertical" class="size-4.5"/>
                </button>
            </x-slot:trigger>

            <x-ui.dropdown-item icon="eye"
                                wire:click="inspectRun({{ $run->id }})">
                مشاهده جزئیات اجرا
            </x-ui.dropdown-item>

            @if ($editorUrl)
                <x-ui.dropdown-item :href="$editorUrl" icon="external-link">
                    باز کردن ورکفلو
                </x-ui.dropdown-item>
            @endif

            <div class="my-1 border-t border-line-border/60"></div>

            <x-ui.dropdown-item icon="trash" danger
                                wire:click="confirmDelete({{ $run->id }})">
                حذف این گزارش
            </x-ui.dropdown-item>
        </x-ui.dropdown>
    </div>
</div>

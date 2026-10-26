@props(['workflow'])

@php
    $run = $workflow->latestRun;

    $runStatuses = [
        'success'  => ['variant' => 'success', 'label' => 'موفق'],
        'failed'   => ['variant' => 'danger', 'label' => 'ناموفق'],
        'running'  => ['variant' => 'info', 'label' => 'در حال اجرا'],
        'pending'  => ['variant' => 'neutral', 'label' => 'در انتظار'],
        'canceled' => ['variant' => 'warning', 'label' => 'لغو شده'],
    ];

    $runStatus = $run ? ($runStatuses[$run->status] ?? $runStatuses['pending']) : null;
@endphp

<div {{ $attributes->class(['group relative flex items-center gap-3 rounded-2xl border border-line-border bg-surface-card p-3 shadow-sm transition duration-200 hover:border-primary/40 hover:shadow-md sm:gap-4 sm:p-4']) }}>
    <a href="{{ route('app.workflows.show', $workflow) }}"
       aria-label="باز کردن {{ $workflow->name }}"
       class="absolute inset-0 z-0 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"></a>

    <div @class([
        'relative z-10 flex size-11 shrink-0 items-center justify-center rounded-xl transition-colors',
        'bg-primary/10 text-primary group-hover:bg-primary/15' => $workflow->is_active,
        'bg-line-border/60 text-content-muted' => ! $workflow->is_active,
    ])>
        <x-ui.icon name="hierarchy-2" class="size-6"/>
    </div>

    <div class="min-w-0 flex-1">
        <h3 class="truncate text-sm font-bold text-content-main transition-colors group-hover:text-primary">
            {{ $workflow->name }}
        </h3>
        @if ($workflow->description)
            <p class="mt-0.5 truncate text-xs text-content-muted">{{ $workflow->description }}</p>
        @endif
    </div>

    <div class="hidden w-40 shrink-0 sm:block">
        @if ($run)
            <x-ui.badge :variant="$runStatus['variant']" dot>{{ $runStatus['label'] }}</x-ui.badge>
            <p class="mt-1 text-xs text-content-muted">{{ $run->started_at?->diffForHumans() ?? '—' }}</p>
        @else
            <span class="text-xs text-content-muted">هنوز اجرا نشده</span>
        @endif
    </div>

    <div class="hidden w-32 shrink-0 text-xs text-content-muted lg:block">
        <p class="text-content-muted/70">آخرین ویرایش</p>
        <p class="mt-0.5">{{ $workflow->updated_at->diffForHumans() }}</p>
    </div>

    <div class="relative z-10 flex shrink-0 items-center gap-2 sm:gap-3">
        @if ($workflow->isPublished())
            <div class="flex items-center gap-2">
                <x-ui.switch :checked="$workflow->is_active"
                             :label="$workflow->is_active ? 'غیرفعال کردن' : 'فعال کردن'"
                             wire:click="toggleActive({{ $workflow->id }})"
                             wire:loading.attr="disabled"
                             wire:target="toggleActive({{ $workflow->id }})"/>
                <span class="hidden w-14 text-xs text-content-muted md:inline">
                    {{ $workflow->is_active ? 'فعال' : 'غیرفعال' }}
                </span>
            </div>
        @else
            <x-ui.badge variant="neutral">پیش‌نویس</x-ui.badge>
        @endif

        <x-ui.dropdown align="left" width="w-44">
            <x-slot:trigger>
                <button type="button" aria-label="گزینه‌ها" class="flex items-center justify-center rounded-lg p-2 text-content-muted transition hover:bg-line-border/60 hover:text-content-main">
                    <x-ui.icon name="dots-vertical"/>
                </button>
            </x-slot:trigger>

            <x-ui.dropdown-item icon="pencil"
                                wire:click="edit({{ $workflow->id }})"
                                @click="$dispatch('open-modal', 'edit-workflow')">
                تغییر نام و توضیح
            </x-ui.dropdown-item>

            @if ($workflow->isPublished())
                <x-ui.dropdown-item icon="cloud-off"
                                    wire:click="unpublish({{ $workflow->id }})">
                    لغو انتشار
                </x-ui.dropdown-item>
            @endif

            <x-ui.dropdown-item icon="trash" danger
                                wire:click="confirmDelete({{ $workflow->id }})"
                                @click="$dispatch('open-modal', 'delete-workflow')">
                حذف
            </x-ui.dropdown-item>
        </x-ui.dropdown>
    </div>
</div>

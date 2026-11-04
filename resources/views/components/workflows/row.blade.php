@props([
    'workflow',
    'definitions' => [],
    'sortable' => true,
])

@php
    $run = $workflow->latestRun;
    $editorUrl = route('app.workflows.show', $workflow);

    $runStatuses = [
        'success'  => ['variant' => 'success', 'label' => 'موفق'],
        'failed'   => ['variant' => 'danger', 'label' => 'ناموفق'],
        'running'  => ['variant' => 'info', 'label' => 'در حال اجرا'],
        'pending'  => ['variant' => 'neutral', 'label' => 'در انتظار'],
        'canceled' => ['variant' => 'warning', 'label' => 'لغو شده'],
    ];

    $runStatus = $run ? ($runStatuses[$run->status] ?? $runStatuses['pending']) : null;

    $uniqueNodes = $workflow->nodes
        ->where('type', '!=', 'sticky_note')
        ->unique('type')
        ->values();

    $visibleNodes = $uniqueNodes->take(4);
    $extraNodesCount = max(0, $uniqueNodes->count() - 4);
@endphp

<div data-workflow-id="{{ $workflow->id }}"
     data-editor-url="{{ $editorUrl }}"
     @if ($sortable)
         draggable="true"
     @dragstart="startDrag($event, {{ $workflow->id }})"
     @dragover.prevent="dragOver($event, {{ $workflow->id }})"
     @drop.prevent="drop($event, {{ $workflow->id }})"
     @dragend="endDrag()"
     :class="{
             'opacity-45 scale-[0.99] border-primary/50': draggingId === {{ $workflow->id }},
             'border-primary ring-2 ring-primary/25 bg-primary/2': dragOverId === {{ $workflow->id }} && draggingId !== {{ $workflow->id }}
         }"
     @endif
     @click="if (!isDragging && !$event.target.closest('[data-no-row-nav]')) window.location.href = $el.dataset.editorUrl"
    {{ $attributes->class(['group relative has-aria-expanded:z-30 flex cursor-pointer items-center gap-3 rounded-2xl border border-line-border bg-surface-card p-3.5 shadow-2xs transition-all duration-150 hover:border-primary/40 hover:shadow-md select-none sm:gap-4 sm:p-4']) }}>

    @if ($sortable)
        <div title="برای تغییر ترتیب بکشید"
             class="flex shrink-0 cursor-grab items-center justify-center rounded-lg p-1 text-content-muted/40 transition group-hover:text-content-muted active:cursor-grabbing">
            <x-ui.icon name="grip-vertical" class="size-4.5"/>
        </div>
    @endif

    <div @class([
        'flex size-11 shrink-0 items-center justify-center rounded-xl transition-colors',
        'bg-emerald-500/10 text-emerald-500 ring-1 ring-emerald-500/20' => $workflow->is_active && $workflow->isPublished(),
        'bg-amber-500/10 text-amber-500 ring-1 ring-amber-500/20' => ! $workflow->is_active && $workflow->isPublished(),
        'bg-line-border/50 text-content-muted' => ! $workflow->isPublished(),
    ])>
        <x-ui.icon name="hierarchy-2" class="size-5.5"/>
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
            <a href="{{ $editorUrl }}"
               draggable="false"
               data-no-row-nav
               dir="auto"
               class="truncate text-start text-sm font-bold text-content-main transition-colors group-hover:text-primary focus:outline-none">
                {{ $workflow->name }}
            </a>
        </div>

        @if ($workflow->description)
            <p dir="auto" class="mt-0.5 truncate text-start text-xs text-content-muted">{{ $workflow->description }}</p>
        @else
            <p class="mt-0.5 truncate text-[11px] text-content-muted">
                {{ $workflow->nodes->where('type', '!=', 'sticky_note')->count() }} نود • آخرین ویرایش {{ $workflow->updated_at->diffForHumans() }}
            </p>
        @endif
    </div>

    @if ($visibleNodes->isNotEmpty())
        <div class="hidden items-center -space-x-1.5 space-x-reverse md:flex" title="سرویس‌های استفاده‌شده">
            @foreach ($visibleNodes as $node)
                @php
                    $def = $definitions[$node->type] ?? null;
                    $icon = $def?->icon ?? 'box';
                @endphp
                <span class="flex size-8 items-center justify-center rounded-xl border border-line-border bg-surface-raised text-content-main shadow-2xs"
                      title="{{ $def?->name ?? $node->name }}">
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

    <div class="hidden w-36 shrink-0 lg:block">
        @if ($run)
            <div class="flex items-center gap-1.5">
                <x-ui.badge :variant="$runStatus['variant']" dot>{{ $runStatus['label'] }}</x-ui.badge>
            </div>
            <p class="mt-1 flex items-center gap-1 text-[11px] text-content-muted">
                <span>{{ $run->started_at?->diffForHumans() ?? '—' }}</span>
                @if ($run->durationInSeconds() !== null)
                    <span class="font-mono tabular-nums" dir="ltr">({{ $run->durationInSeconds() }}s)</span>
                @endif
            </p>
        @else
            <span class="text-xs text-content-muted">هنوز اجرا نشده</span>
        @endif
    </div>

    <div data-no-row-nav class="flex shrink-0 items-center gap-2 sm:gap-3" @click.stop>
        @if ($workflow->isPublished())
            <div class="flex items-center gap-2">
                <x-ui.switch :checked="$workflow->is_active"
                             :label="$workflow->is_active ? 'غیرفعال کردن' : 'فعال کردن'"
                             wire:click="toggleActive({{ $workflow->id }})"
                             wire:loading.attr="disabled"
                             wire:target="toggleActive({{ $workflow->id }})"/>
                <span class="hidden w-12 text-xs font-medium text-content-muted sm:inline">
                    {{ $workflow->is_active ? 'فعال' : 'متوقف' }}
                </span>
            </div>
        @else
            <x-ui.badge variant="neutral">پیش‌نویس</x-ui.badge>
        @endif

        <x-ui.dropdown align="left" width="w-48">
            <x-slot:trigger>
                <button type="button"
                        aria-label="گزینه‌ها"
                        class="flex size-8.5 items-center justify-center rounded-xl text-content-muted transition hover:bg-line-border/60 hover:text-content-main in-[[aria-expanded=true]]:bg-line-border/60 in-[[aria-expanded=true]]:text-content-main focus:outline-none cursor-pointer">
                    <x-ui.icon name="dots-vertical" class="size-4.5"/>
                </button>
            </x-slot:trigger>

            <x-ui.dropdown-item :href="$editorUrl" icon="external-link">
                باز کردن در ویرایشگر
            </x-ui.dropdown-item>

            <x-ui.dropdown-item icon="pencil"
                                wire:click="edit({{ $workflow->id }})">
                تغییر نام و توضیح
            </x-ui.dropdown-item>

            <x-ui.dropdown-item icon="copy"
                                wire:click="duplicate({{ $workflow->id }})">
                تکثیر ورکفلو
            </x-ui.dropdown-item>

            @if ($workflow->isPublished())
                <x-ui.dropdown-item icon="cloud-off"
                                    wire:click="unpublish({{ $workflow->id }})">
                    لغو انتشار
                </x-ui.dropdown-item>
            @endif

            <div class="my-1 border-t border-line-border/60"></div>

            <x-ui.dropdown-item icon="trash" danger
                                wire:click="confirmDelete({{ $workflow->id }})">
                حذف ورکفلو
            </x-ui.dropdown-item>
        </x-ui.dropdown>
    </div>
</div>

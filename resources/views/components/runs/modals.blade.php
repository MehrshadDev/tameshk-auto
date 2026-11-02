@props([
    'inspectingRun' => null,
    'definitions' => [],
    'deletingRunId' => null,
])

<x-ui.modal name="run-details-modal" :title="$inspectingRun ? 'جزئیات اجرای #' . $inspectingRun->id : 'جزئیات اجرا'" maxWidth="xl">
    @if ($inspectingRun)
        @php
            $duration = $inspectingRun->durationInSeconds();
            $statusVariant = match ($inspectingRun->status) {
                'success' => 'success',
                'failed' => 'danger',
                'running' => 'info',
                'canceled' => 'warning',
                default => 'neutral',
            };
            $statusLabel = match ($inspectingRun->status) {
                'success' => 'موفق',
                'failed' => 'ناموفق',
                'running' => 'در حال اجرا',
                'canceled' => 'لغو شده',
                default => 'در انتظار',
            };
        @endphp

        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line-border/80 bg-surface-card p-3.5">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <x-ui.icon name="hierarchy-2" class="size-5"/>
                    </div>
                    <div class="min-w-0">
                        <p dir="auto" class="truncate text-start text-xs font-bold text-content-main">
                            {{ $inspectingRun->workflow?->name ?? 'ورکفلو حذف‌شده' }}
                        </p>
                        <p class="mt-0.5 font-mono text-[11px] text-content-muted" dir="ltr">
                            {{ $inspectingRun->started_at?->format('Y-m-d H:i:s') ?? '—' }}
                            @if ($duration !== null)
                                • {{ $duration }}s
                            @endif
                        </p>
                    </div>
                </div>

                <x-ui.badge :variant="$statusVariant" dot>{{ $statusLabel }}</x-ui.badge>
            </div>

            @if ($inspectingRun->error)
                <div class="space-y-1.5 rounded-2xl border border-primary/25 bg-primary/10 p-3.5 text-xs text-primary">
                    <div class="flex items-center justify-between border-b border-primary/15 pb-1.5">
                        <span class="font-bold">خطای کلی اجرا</span>
                        <button type="button"
                                data-err="{{ $inspectingRun->error }}"
                                @click="navigator.clipboard.writeText($el.dataset.err); $dispatch('notify', { message: 'متن خطا کپی شد.', type: 'success' })"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold hover:underline cursor-pointer">
                            <x-ui.icon name="copy" class="size-3.5"/>
                            <span>کپی</span>
                        </button>
                    </div>
                    <p dir="auto" class="font-mono text-[11px] leading-5 wrap-break-word whitespace-pre-wrap text-start">{{ $inspectingRun->error }}</p>
                </div>
            @endif

            <div class="space-y-2">
                <div class="flex items-center justify-between px-1">
                    <span class="text-xs font-bold text-content-main">مراحل اجرای نودها ({{ $inspectingRun->nodeRuns->count() }})</span>
                    <span class="text-[11px] text-content-muted">برای مشاهده ورودی و خروجی کلیک کنید</span>
                </div>

                <div class="custom-scrollbar max-h-96 space-y-2 overflow-y-auto pe-1">
                    @forelse ($inspectingRun->nodeRuns as $index => $nodeRun)
                        @php
                            $node = $nodeRun->node;
                            $def = $node ? ($definitions[$node->type] ?? null) : null;
                            $icon = $def?->icon ?? 'box';
                            $inputJson = json_encode($nodeRun->input_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null';
                            $outputJson = json_encode($nodeRun->output_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null';
                        @endphp

                        <div x-data="{ expanded: {{ $nodeRun->status === 'failed' ? 'true' : 'false' }}, tab: 'output' }"
                             class="overflow-hidden rounded-2xl border border-line-border/80 bg-surface-card transition">
                            <button type="button"
                                    @click="expanded = !expanded"
                                    class="flex w-full items-center justify-between gap-3 px-3.5 py-2.5 text-start transition hover:bg-surface-raised cursor-pointer">
                                <div class="flex min-w-0 flex-1 items-center gap-2.5">
                                    <span class="flex size-6 shrink-0 items-center justify-center rounded-lg bg-line-border/50 font-mono text-[10px] font-bold text-content-muted">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-xl border border-line-border/60 bg-surface-base text-content-main">
                                        <x-ui.icon :name="$icon" class="size-4"/>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p dir="auto" class="truncate text-start text-xs font-bold text-content-main">
                                            {{ $node?->name ?? 'نود حذف‌شده #' . $nodeRun->node_id }}
                                        </p>
                                        <p class="truncate text-[10px] text-content-muted">
                                            {{ $def?->name ?? ($node?->type ?? '—') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    @if ($nodeRun->duration_ms !== null)
                                        <span dir="ltr" class="font-mono text-[10px] font-medium text-content-muted">
                                            {{ $nodeRun->duration_ms }} ms
                                        </span>
                                    @endif

                                    <span @class([
                                        'rounded-md px-2 py-0.5 text-[10px] font-bold',
                                        'bg-emerald-500/10 text-emerald-500' => $nodeRun->status === 'success',
                                        'bg-primary/10 text-primary' => $nodeRun->status === 'failed',
                                        'bg-line-border/60 text-content-muted' => ! in_array($nodeRun->status, ['success', 'failed'], true),
                                    ])>
                                        {{ $nodeRun->status === 'success' ? 'موفق' : ($nodeRun->status === 'failed' ? 'خطا' : $nodeRun->status) }}
                                    </span>

                                    <x-ui.icon name="chevron-down"
                                               x-bind:class="expanded && 'rotate-180 text-primary'"
                                               class="size-4 text-content-muted transition-transform duration-150"/>
                                </div>
                            </button>

                            <div x-show="expanded" x-cloak class="space-y-3 border-t border-line-border/60 bg-surface-base/60 p-3.5">
                                @if ($nodeRun->error)
                                    <div class="rounded-xl border border-primary/20 bg-primary/10 p-2.5 font-mono text-[11px] leading-5 text-primary wrap-break-word whitespace-pre-wrap text-start" dir="auto">
                                        {{ $nodeRun->error }}
                                    </div>
                                @endif

                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1 rounded-xl border border-line-border bg-surface-card p-0.5 text-[11px] font-semibold">
                                        <button type="button"
                                                @click="tab = 'output'"
                                                :class="tab === 'output' ? 'bg-primary text-white shadow-2xs' : 'text-content-muted hover:text-content-main'"
                                                class="rounded-lg px-2.5 py-1 transition cursor-pointer">
                                            خروجی (Output)
                                        </button>
                                        <button type="button"
                                                @click="tab = 'input'"
                                                :class="tab === 'input' ? 'bg-primary text-white shadow-2xs' : 'text-content-muted hover:text-content-main'"
                                                class="rounded-lg px-2.5 py-1 transition cursor-pointer">
                                            ورودی (Input)
                                        </button>
                                    </div>

                                    <button type="button"
                                            data-input-json="{{ $inputJson }}"
                                            data-output-json="{{ $outputJson }}"
                                            @click="navigator.clipboard.writeText(tab === 'output' ? $el.dataset.outputJson : $el.dataset.inputJson); $dispatch('notify', { message: 'داده JSON کپی شد.', type: 'success' })"
                                            class="inline-flex items-center gap-1 rounded-lg border border-line-border bg-surface-card px-2.5 py-1 text-[11px] font-medium text-content-muted transition hover:text-content-main cursor-pointer">
                                        <x-ui.icon name="copy" class="size-3.5"/>
                                        <span>کپی JSON</span>
                                    </button>
                                </div>

                                <pre x-show="tab === 'output'"
                                     dir="ltr"
                                     class="custom-scrollbar max-h-52 overflow-auto rounded-xl border border-line-border bg-surface-card p-3 text-left font-mono text-[11px] leading-5 text-content-main select-text">{{ $outputJson }}</pre>

                                <pre x-show="tab === 'input'"
                                     x-cloak
                                     dir="ltr"
                                     class="custom-scrollbar max-h-52 overflow-auto rounded-xl border border-line-border bg-surface-card p-3 text-left font-mono text-[11px] leading-5 text-content-main select-text">{{ $inputJson }}</pre>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-line-border py-10 text-center text-xs text-content-muted">
                            هیچ مرحله‌ای برای این اجرا ثبت نشده است.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <x-slot:footer>
        @if ($inspectingRun?->workflow)
            <x-ui.button :href="route('app.workflows.show', $inspectingRun->workflow)" variant="secondary">
                <x-ui.icon name="external-link" class="size-4"/>
                <span>باز کردن ورکفلو</span>
            </x-ui.button>
        @endif
        <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'run-details-modal')" class="cursor-pointer">
            بستن
        </x-ui.button>
    </x-slot:footer>
</x-ui.modal>

<x-ui.modal name="delete-run-modal" title="حذف گزارش اجرا" maxWidth="sm">
    <div class="flex items-start gap-3.5">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <x-ui.icon name="trash" class="size-5"/>
        </div>
        <p class="text-xs leading-6 text-content-muted">
            گزارش اجرای شماره
            <span dir="ltr" class="font-mono font-bold text-content-main">#{{ $deletingRunId }}</span>
            و تمام لاگ‌های نودهای آن برای همیشه حذف خواهد شد. آیا مطمئن هستید؟
        </p>
    </div>

    <x-slot:footer>
        <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'delete-run-modal')" class="cursor-pointer">انصراف</x-ui.button>
        <x-ui.button type="button" wire:click="deleteRun" wire:loading.attr="disabled" wire:target="deleteRun" class="cursor-pointer">بله، حذف کن</x-ui.button>
    </x-slot:footer>
</x-ui.modal>

<x-ui.modal name="clear-runs-modal" title="پاک‌سازی تاریخچه اجراها" maxWidth="sm">
    <div class="flex items-start gap-3.5">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <x-ui.icon name="trash" class="size-5"/>
        </div>
        <p class="text-xs leading-6 text-content-muted">
            تمام گزارش‌های اجرای پایان‌یافته شما حذف خواهند شد. این عملیات قابل بازگشت نیست. آیا ادامه می‌دهید؟
        </p>
    </div>

    <x-slot:footer>
        <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'clear-runs-modal')" class="cursor-pointer">انصراف</x-ui.button>
        <x-ui.button type="button" wire:click="clearFinishedRuns" wire:loading.attr="disabled" wire:target="clearFinishedRuns" class="cursor-pointer">بله، پاک‌سازی کن</x-ui.button>
    </x-slot:footer>
</x-ui.modal>

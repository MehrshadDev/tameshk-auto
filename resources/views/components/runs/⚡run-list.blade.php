<?php

use App\Models\WorkflowRun;
use App\Workflow\Registry\NodeRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'workflow', except: 'all')]
    public string $workflowFilter = 'all';

    #[Url(as: 'status', except: 'all')]
    public string $status = 'all';

    #[Locked]
    public ?int $inspectingRunId = null;

    #[Locked]
    public ?int $deletingRunId = null;

    public function mount(): void
    {
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->status = $this->normalizeStatus($this->status);
    }

    public function updatedSearch(): void
    {
        $this->search = mb_substr($this->search, 0, 100);
        $this->resetPage();
    }

    public function updatedWorkflowFilter(): void
    {
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        $status = $this->normalizeStatus($status);

        if ($this->status === $status) {
            return;
        }

        $this->status = $status;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'workflowFilter', 'status');
        $this->resetPage();
    }

    public function inspectRun(int $id): void
    {
        $run = $this->ownedRunQuery()->findOrFail($id);
        $this->inspectingRunId = $run->id;
        $this->dispatch('open-modal', name: 'run-details-modal');
    }

    public function confirmDelete(int $id): void
    {
        $run = $this->ownedRunQuery()->findOrFail($id);
        $this->deletingRunId = $run->id;
        $this->dispatch('open-modal', name: 'delete-run-modal');
    }

    public function deleteRun(): void
    {
        if (! $this->deletingRunId) {
            return;
        }

        $run = $this->ownedRunQuery()->findOrFail($this->deletingRunId);

        if ($this->inspectingRunId === $run->id) {
            $this->inspectingRunId = null;
        }

        $run->delete();
        $this->deletingRunId = null;

        unset($this->runs, $this->stats);
        $this->dispatch('close-modal', name: 'delete-run-modal');

        if ($this->getPage() > 1 && $this->runs->isEmpty()) {
            $this->previousPage();
            unset($this->runs);
        }

        $this->notify('گزارش اجرا حذف شد.');
    }

    public function clearFinishedRuns(): void
    {
        $rateKey = 'runs-clear:' . auth()->id();

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $this->notify('لطفاً چند لحظه صبر کنید.', 'error');

            return;
        }

        RateLimiter::hit($rateKey, 60);

        $this->ownedRunQuery()
            ->whereIn('status', ['success', 'failed', 'canceled'])
            ->delete();

        $this->inspectingRunId = null;
        $this->deletingRunId = null;
        $this->resetPage();

        unset($this->runs, $this->stats);
        $this->dispatch('close-modal', name: 'clear-runs-modal');
        $this->notify('تاریخچه اجراها پاک‌سازی شد.');
    }

    #[Computed]
    public function workflowOptions(): array
    {
        $options = [
            ['value' => 'all', 'label' => 'همه ورکفلوها', 'icon' => 'hierarchy-2'],
        ];

        $workflows = auth()->user()->workflows()->orderBy('name')->get(['id', 'name']);

        foreach ($workflows as $wf) {
            $options[] = [
                'value' => (string) $wf->id,
                'label' => $wf->name,
                'icon' => 'hierarchy-2',
            ];
        }

        return $options;
    }

    #[Computed]
    public function definitions(): array
    {
        return app(NodeRegistry::class)->definitions();
    }

    #[Computed]
    public function stats(): array
    {
        $base = $this->ownedRunQuery();

        $total = (clone $base)->count();
        $succeeded = (clone $base)->where('status', 'success')->count();
        $failed = (clone $base)->where('status', 'failed')->count();

        $recentFinished = (clone $base)
            ->whereNotNull('started_at')
            ->whereNotNull('finished_at')
            ->latest('id')
            ->limit(100)
            ->get(['started_at', 'finished_at']);

        $avgSeconds = null;

        if ($recentFinished->isNotEmpty()) {
            $totalMs = $recentFinished->sum(fn (WorkflowRun $r) => abs($r->finished_at->diffInMilliseconds($r->started_at)));
            $avgSeconds = round(($totalMs / $recentFinished->count()) / 1000, 2);
        }

        return [
            'total' => $total,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'avg' => $avgSeconds,
        ];
    }

    #[Computed]
    public function runs()
    {
        $term = trim($this->search);
        $cleanId = ltrim($term, '#');

        return $this->ownedRunQuery()
            ->with([
                'workflow:id,public_id,name',
                'nodeRuns' => fn ($q) => $q->with('node:id,type,name')->orderBy('id'),
            ])
            ->when(
                $this->workflowFilter !== 'all' && ctype_digit($this->workflowFilter),
                fn (Builder $q) => $q->where('workflow_id', (int) $this->workflowFilter),
            )
            ->when(
                $this->status !== 'all',
                fn (Builder $q) => $q->where('status', $this->status),
            )
            ->when($term !== '', function (Builder $q) use ($term, $cleanId) {
                $like = '%' . addcslashes(mb_substr($term, 0, 100), '%_\\') . '%';

                $q->where(function (Builder $sub) use ($like, $cleanId) {
                    if ($cleanId !== '' && ctype_digit($cleanId)) {
                        $sub->orWhere('id', (int) $cleanId);
                    }

                    $sub->orWhere('error', 'like', $like)
                        ->orWhereHas('workflow', fn (Builder $wf) => $wf->where('name', 'like', $like));
                });
            })
            ->latest('id')
            ->paginate(12);
    }

    #[Computed]
    public function inspectingRun(): ?WorkflowRun
    {
        if (! $this->inspectingRunId) {
            return null;
        }

        return $this->ownedRunQuery()
            ->with([
                'workflow:id,public_id,name',
                'nodeRuns' => fn ($q) => $q->with('node:id,type,name')->orderBy('id'),
            ])
            ->find($this->inspectingRunId);
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->workflowFilter !== 'all' || $this->status !== 'all';
    }

    private function ownedRunQuery(): Builder
    {
        return WorkflowRun::query()
            ->whereHas('workflow', fn (Builder $q) => $q->where('user_id', auth()->id()));
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, ['all', 'success', 'failed', 'canceled'], true) ? $status : 'all';
    }

    private function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', message: $message, type: $type);
    }
};
?>

<div class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    @php
        $statData = $this->stats;
    @endphp

    <x-runs.stats
        :total="$statData['total']"
        :succeeded="$statData['succeeded']"
        :failed="$statData['failed']"
        :avg-duration-seconds="$statData['avg']"
    />

    <div class="relative has-aria-expanded:z-40 flex flex-col gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:flex-row sm:flex-wrap sm:items-center">
        <div class="relative min-w-48 flex-1">
            <x-ui.icon name="search"
                       class="pointer-events-none absolute inset-s-3.5 top-1/2 size-4.5 -translate-y-1/2 text-content-muted"/>
            <input type="text"
                   maxlength="100"
                   wire:model.live.debounce.300ms="search"
                   placeholder="جستجو در نام ورکفلو، شماره اجرا (#12) یا خطا..."
                   class="h-9.5 w-full rounded-xl border border-line-border bg-surface-base pe-9 ps-10 text-xs font-medium text-content-main transition placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"/>
            @if ($search !== '')
                <button type="button"
                        wire:click="$set('search', '')"
                        aria-label="پاک کردن جستجو"
                        class="absolute inset-e-2.5 top-1/2 flex size-6 -translate-y-1/2 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main cursor-pointer">
                    <x-ui.icon name="x" class="size-3.5"/>
                </button>
            @endif
        </div>

        <x-ui.select
            :options="$this->workflowOptions"
            :selected="$workflowFilter"
            wire-model="workflowFilter"
            placeholder="همه ورکفلوها"
        />

        <div class="flex shrink-0 items-center gap-1 rounded-xl border border-line-border bg-surface-base p-1">
            @foreach (['all' => 'همه', 'success' => 'موفق', 'failed' => 'ناموفق', 'canceled' => 'لغو شده'] as $key => $label)
                <button type="button"
                        @if ($status !== $key) wire:click="setStatus('{{ $key }}')" @endif
                    @class([
                        'flex-1 rounded-lg px-3 py-1.5 text-xs whitespace-nowrap transition sm:flex-none',
                        'cursor-default bg-primary text-white font-bold shadow-2xs' => $status === $key,
                        'cursor-pointer font-medium text-content-muted hover:text-content-main' => $status !== $key,
                    ])>
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($statData['total'] > 0)
            <button type="button"
                    @click="$dispatch('open-modal', 'clear-runs-modal')"
                    title="پاک‌سازی تاریخچه اجراها"
                    class="inline-flex h-9.5 shrink-0 items-center justify-center gap-1.5 rounded-xl border border-line-border bg-surface-base px-3.5 text-xs font-semibold text-content-muted transition hover:border-primary/30 hover:bg-primary/10 hover:text-primary cursor-pointer">
                <x-ui.icon name="trash" class="size-4"/>
                <span>پاک‌سازی</span>
            </button>
        @endif
    </div>

    <div class="space-y-2.5">
        @forelse ($this->runs as $run)
            <x-runs.row
                :run="$run"
                :definitions="$this->definitions"
                wire:key="run-{{ $run->id }}"
            />
        @empty
            <x-ui.card :padding="false">
                @if ($this->hasFilters)
                    <x-ui.empty-state icon="search"
                                      title="اجرایی با این مشخصات یافت نشد"
                                      description="هیچ گزارش اجرایی متناسب با فیلترهای انتخابی شما وجود ندارد.">
                        <x-ui.button type="button" variant="secondary" wire:click="clearFilters" class="cursor-pointer">
                            پاک کردن فیلترها
                        </x-ui.button>
                    </x-ui.empty-state>
                @else
                    <x-ui.empty-state icon="history"
                                      title="هنوز هیچ اجرایی ثبت نشده است"
                                      description="به محض اینکه یکی از ورکفلوهای خود را تست یا منتشر کنید، گزارش گام‌به‌گام اجرای آن در این صفحه قابل مشاهده خواهد بود.">
                        <x-ui.button :href="route('app.workflows.index')">
                            <x-ui.icon name="hierarchy-2" class="size-4"/>
                            مشاهده ورکفلوها
                        </x-ui.button>
                    </x-ui.empty-state>
                @endif
            </x-ui.card>
        @endforelse
    </div>

    <x-ui.pagination :paginator="$this->runs" />

    <x-runs.modals
        :inspecting-run="$this->inspectingRun"
        :definitions="$this->definitions"
        :deleting-run-id="$deletingRunId"
    />
</div>

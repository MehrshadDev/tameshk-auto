<?php

use App\Models\Edge;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Workflow\Exceptions\WorkflowPublishException;
use App\Workflow\Publishing\WorkflowPublisher;
use App\Workflow\Registry\NodeRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
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

    #[Url(as: 'status', except: 'all')]
    public string $status = 'all';

    #[Locked]
    public ?int $deletingId = null;
    
    #[Locked]
    public ?int $editingId = null;

    public string $editName = '';

    public string $editDescription = '';

    public function mount(): void
    {
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->status = $this->normalizeStatus($this->status);
    }

    public function updatedStatus(): void
    {
        $this->status = $this->normalizeStatus($this->status);
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

    public function updatedSearch(): void
    {
        $this->search = mb_substr($this->search, 0, 100);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status');
        $this->resetPage();
    }

    public function create(): void
    {
        $key = 'workflow-create:' . auth()->id();

        if (RateLimiter::tooManyAttempts($key, config('workflow.create_per_minute', 10))) {
            $this->notify('درخواست‌های زیادی فرستادی. یک دقیقه صبر کن.', 'error');

            return;
        }

        RateLimiter::hit($key);

        try {
            DB::transaction(function () {
                $user = User::query()->whereKey(auth()->id())->lockForUpdate()->firstOrFail();

                $this->authorize('create', Workflow::class);

                $user->workflows()->create([
                    'name' => 'ورکفلو بدون عنوان',
                    'settings' => ['sort_order' => '000000'],
                ]);
            });
        } catch (AuthorizationException $e) {
            $this->notify($e->getMessage(), 'error');

            return;
        }

        $this->clearFilters();
        unset($this->workflows, $this->total, $this->activeCount);
        $this->notify('ورکفلو جدید ساخته شد.');
    }

    public function duplicate(int $id): void
    {
        $key = 'workflow-create:' . auth()->id();

        if (RateLimiter::tooManyAttempts($key, config('workflow.create_per_minute', 10))) {
            $this->notify('درخواست‌های زیادی فرستادی. یک دقیقه صبر کن.', 'error');

            return;
        }

        RateLimiter::hit($key);

        $source = $this->ownedWorkflow($id);
        $this->authorize('view', $source);

        try {
            DB::transaction(function () use ($source) {
                $user = User::query()->whereKey(auth()->id())->lockForUpdate()->firstOrFail();

                $this->authorize('create', Workflow::class);

                $source->load(['nodes', 'edges']);

                $copyName = mb_substr(trim($source->name) . ' (کپی)', 0, 100);

                $clone = $user->workflows()->create([
                    'name' => $copyName,
                    'description' => $source->description,
                    'is_active' => false,
                    'published_at' => null,
                    'trigger_type' => $source->trigger_type,
                    'settings' => ['sort_order' => '000000'],
                ]);

                $nodeIdMap = [];

                foreach ($source->nodes as $node) {
                    $newNode = $clone->nodes()->create([
                        'type' => $node->type,
                        'name' => $node->name,
                        'position_x' => $node->position_x,
                        'position_y' => $node->position_y,
                        'config' => $node->config,
                    ]);

                    $nodeIdMap[$node->id] = $newNode->id;
                }

                $now = now();
                $edgeRows = [];

                foreach ($source->edges as $edge) {
                    if (isset($nodeIdMap[$edge->source_node_id], $nodeIdMap[$edge->target_node_id])) {
                        $edgeRows[] = [
                            'workflow_id' => $clone->id,
                            'source_node_id' => $nodeIdMap[$edge->source_node_id],
                            'target_node_id' => $nodeIdMap[$edge->target_node_id],
                            'source_handle' => $edge->source_handle,
                            'target_handle' => $edge->target_handle,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($edgeRows !== []) {
                    Edge::query()->insert($edgeRows);
                }
            });
        } catch (AuthorizationException $e) {
            $this->notify($e->getMessage(), 'error');

            return;
        }

        unset($this->workflows, $this->total);
        $this->notify('ورکفلو با موفقیت تکثیر شد.');
    }

    public function reorder(array $orderedIds): void
    {
        $rateKey = 'workflow-reorder:' . auth()->id();

        if (RateLimiter::tooManyAttempts($rateKey, 60)) {
            $this->notify('تعداد درخواست‌ها بیش از حد مجاز است.', 'error');

            return;
        }

        RateLimiter::hit($rateKey, 60);

        $maxAllowed = (int) config('workflow.max_per_user', 50);

        $cleanIds = collect($orderedIds)
            ->filter(fn ($id) => is_int($id) || (is_string($id) && ctype_digit($id)))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->take($maxAllowed)
            ->values();

        if ($cleanIds->isEmpty()) {
            return;
        }

        $ownedWorkflows = auth()->user()
            ->workflows()
            ->whereIn('id', $cleanIds->all())
            ->get()
            ->keyBy('id');

        if ($ownedWorkflows->isEmpty()) {
            return;
        }

        $pageOffset = max(0, ($this->getPage() - 1) * 10);

        DB::transaction(function () use ($cleanIds, $ownedWorkflows, $pageOffset) {
            foreach ($cleanIds as $index => $workflowId) {
                $workflow = $ownedWorkflows->get($workflowId);

                if (! $workflow) {
                    continue;
                }

                $settings = is_array($workflow->settings) ? $workflow->settings : [];
                $settings['sort_order'] = sprintf('%06d', $pageOffset + $index + 1);

                $workflow->timestamps = false;
                $workflow->update(['settings' => $settings]);
                $workflow->timestamps = true;
            }
        });

        unset($this->workflows);
    }

    public function toggleActive(int $id, WorkflowPublisher $publisher): void
    {
        $workflow = $this->ownedWorkflow($id);

        $this->authorize('update', $workflow);

        if (! $workflow->isPublished()) {
            $this->notify('این ورکفلو هنوز منتشر نشده.', 'error');

            return;
        }

        $activate = ! $workflow->is_active;

        try {
            $publisher->setActive($workflow, $activate);
        } catch (WorkflowPublishException $e) {
            $this->notify($e->summary(), 'error');

            return;
        }

        unset($this->workflows, $this->activeCount);
        $this->notify($activate ? 'ورکفلو فعال شد.' : 'ورکفلو متوقف شد.');
    }

    public function unpublish(int $id, WorkflowPublisher $publisher): void
    {
        $workflow = $this->ownedWorkflow($id);

        $this->authorize('update', $workflow);

        $publisher->unpublish($workflow);

        unset($this->workflows, $this->activeCount);
        $this->notify('انتشار ورکفلو لغو شد.');
    }

    public function confirmDelete(int $id): void
    {
        $workflow = $this->ownedWorkflow($id);

        $this->authorize('delete', $workflow);

        $this->deletingId = $workflow->id;
        $this->dispatch('open-modal', name: 'delete-workflow');
    }

    public function delete(WorkflowPublisher $publisher): void
    {
        $workflow = $this->ownedWorkflow($this->deletingId);

        $this->authorize('delete', $workflow);

        if ($workflow->isPublished()) {
            $publisher->unpublish($workflow);
        }

        $workflow->delete();

        $this->deletingId = null;
        $this->dispatch('close-modal', name: 'delete-workflow');

        unset($this->workflows, $this->total, $this->activeCount);

        if ($this->getPage() > 1 && $this->workflows->isEmpty()) {
            $this->previousPage();
            unset($this->workflows);
        }

        $this->notify('ورکفلو حذف شد.');
    }

    public function edit(int $id): void
    {
        $workflow = $this->ownedWorkflow($id);

        $this->authorize('update', $workflow);

        $this->editingId = $workflow->id;
        $this->editName = $workflow->name;
        $this->editDescription = (string) $workflow->description;
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'edit-workflow');
    }

    public function saveEdit(): void
    {
        $this->editName = trim(strip_tags($this->editName));
        $this->editDescription = trim(strip_tags($this->editDescription));

        $data = $this->validate(
            [
                'editName' => ['required', 'string', 'max:100', 'regex:/^(?!.*\S{40,}).*$/u'],
                'editDescription' => ['nullable', 'string', 'max:500'],
            ],
            [
                'editName.required' => 'نام ورکفلو الزامی است.',
                'editName.regex' => 'نام ورکفلو نباید کلمه‌ی خیلی طولانی بدون فاصله داشته باشد.',
                'editName.max' => 'نام ورکفلو نباید بیشتر از ۱۰۰ کاراکتر باشد.',
                'editDescription.max' => 'توضیحات نباید بیشتر از ۵۰۰ کاراکتر باشد.',
            ],
        );

        $workflow = $this->ownedWorkflow($this->editingId);

        $this->authorize('update', $workflow);

        $workflow->update([
            'name' => $data['editName'],
            'description' => $data['editDescription'] !== '' ? $data['editDescription'] : null,
        ]);

        $this->editingId = null;
        unset($this->workflows);
        $this->dispatch('close-modal', name: 'edit-workflow');
        $this->notify('تغییرات ذخیره شد.');
    }

    #[Computed]
    public function workflows()
    {
        return auth()->user()
            ->workflows()
            ->with(['latestRun', 'nodes:id,workflow_id,type,name'])
            ->search($this->search)
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderByRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings, '$.sort_order')), '999999') ASC")
            ->latest('updated_at')
            ->paginate(10);
    }

    #[Computed]
    public function definitions(): array
    {
        return app(NodeRegistry::class)->definitions();
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->status !== 'all';
    }

    #[Computed]
    public function total(): int
    {
        return auth()->user()->workflows()->count();
    }

    #[Computed]
    public function activeCount(): int
    {
        return auth()->user()->workflows()->where('is_active', true)->count();
    }

    #[Computed]
    public function totalRunsCount(): int
    {
        return WorkflowRun::query()
            ->whereHas('workflow', fn ($q) => $q->where('user_id', auth()->id()))
            ->count();
    }

    #[Computed]
    public function successRate(): ?int
    {
        $totalFinished = WorkflowRun::query()
            ->whereHas('workflow', fn ($q) => $q->where('user_id', auth()->id()))
            ->whereIn('status', ['success', 'failed'])
            ->count();

        if ($totalFinished === 0) {
            return null;
        }

        $succeeded = WorkflowRun::query()
            ->whereHas('workflow', fn ($q) => $q->where('user_id', auth()->id()))
            ->where('status', 'success')
            ->count();

        return (int) round(($succeeded / $totalFinished) * 100);
    }

    #[Computed]
    public function atLimit(): bool
    {
        return $this->total >= config('workflow.max_per_user');
    }

    #[Computed]
    public function deleting(): ?Workflow
    {
        return $this->deletingId
            ? auth()->user()->workflows()->find($this->deletingId)
            : null;
    }

    private function ownedWorkflow(?int $id): Workflow
    {
        return auth()->user()->workflows()->findOrFail($id);
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, ['all', 'active', 'inactive'], true) ? $status : 'all';
    }

    private function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', message: $message, type: $type);
    }
};
?>

<div class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    <x-workflows.stats
        :total="$this->total"
        :max="(int) config('workflow.max_per_user', 10)"
        :active="$this->activeCount"
        :runs="$this->totalRunsCount"
        :success-rate="$this->successRate"
    />

    <div class="flex flex-col gap-3 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-col gap-2.5 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <x-ui.icon name="search"
                           class="pointer-events-none absolute inset-s-3.5 top-1/2 size-4.5 -translate-y-1/2 text-content-muted"/>
                <input type="text"
                       maxlength="100"
                       wire:model.live.debounce.300ms="search"
                       placeholder="جستجو در نام و توضیحات ورکفلو..."
                       class="w-full rounded-xl border border-line-border bg-surface-base py-2 pe-9 ps-10 text-xs font-medium text-content-main transition placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
                @if ($search !== '')
                    <button type="button"
                            wire:click="$set('search', '')"
                            aria-label="پاک کردن جستجو"
                            class="absolute inset-e-2.5 top-1/2 flex size-6 -translate-y-1/2 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main cursor-pointer">
                        <x-ui.icon name="x" class="size-3.5"/>
                    </button>
                @endif
            </div>

            <div class="flex items-center gap-1 rounded-xl border border-line-border bg-surface-base p-1">
                @foreach (['all' => 'همه', 'active' => 'فعال', 'inactive' => 'غیرفعال'] as $key => $label)
                    <button type="button"
                            @if ($status !== $key) wire:click="setStatus('{{ $key }}')" @endif
                        @class([
                            'flex-1 rounded-lg px-3.5 py-1.5 text-xs transition sm:flex-none',
                            'cursor-default bg-primary text-white font-bold shadow-2xs' => $status === $key,
                            'cursor-pointer font-medium text-content-muted hover:text-content-main' => $status !== $key,
                        ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <x-ui.button type="button"
                     wire:click="create"
                     wire:loading.attr="disabled"
                     wire:target="create"
                     :disabled="$this->atLimit"
                     class="cursor-pointer shrink-0">
            <x-ui.icon name="plus" class="size-4"/>
            <span>ورکفلو جدید</span>
        </x-ui.button>
    </div>

    <div x-data="{
             draggingId: null,
             dragOverId: null,
             isDragging: false,
             startDrag(event, id) {
                 this.draggingId = id;
                 this.isDragging = true;
                 event.dataTransfer.effectAllowed = 'move';
                 event.dataTransfer.setData('text/plain', String(id));
             },
             dragOver(event, targetId) {
                 if (this.draggingId === null || this.draggingId === targetId) return;
                 this.dragOverId = targetId;
             },
             drop(event, targetId) {
                 if (this.draggingId === null || this.draggingId === targetId) {
                     this.endDrag();
                     return;
                 }

                 const container = this.$refs.listContainer;
                 const rows = Array.from(container.querySelectorAll('[data-workflow-id]'));
                 const ids = rows.map(el => Number(el.dataset.workflowId));

                 const fromIndex = ids.indexOf(this.draggingId);
                 const toIndex = ids.indexOf(targetId);

                 if (fromIndex !== -1 && toIndex !== -1) {
                     const movedEl = rows[fromIndex];
                     const targetEl = rows[toIndex];

                     if (fromIndex < toIndex) {
                         targetEl.after(movedEl);
                     } else {
                         targetEl.before(movedEl);
                     }

                     const updatedIds = Array.from(container.querySelectorAll('[data-workflow-id]'))
                         .map(el => Number(el.dataset.workflowId));

                     $wire.reorder(updatedIds);
                 }

                 this.endDrag();
             },
             endDrag() {
                 this.draggingId = null;
                 this.dragOverId = null;
                 setTimeout(() => { this.isDragging = false; }, 80);
             }
         }"
         x-ref="listContainer"
         class="space-y-2.5">
        @forelse ($this->workflows as $workflow)
            <x-workflows.row
                :workflow="$workflow"
                :definitions="$this->definitions"
                :sortable="! $this->hasFilters && $this->workflows->count() > 1"
                wire:key="workflow-{{ $workflow->id }}"
            />
        @empty
            <x-ui.card :padding="false">
                @if ($this->hasFilters)
                    <x-ui.empty-state icon="search"
                                      title="نتیجه‌ای پیدا نشد"
                                      description="ورکفلویی با این عبارت جستجو یا فیلتر وضعیت پیدا نشد.">
                        <x-ui.button type="button" variant="secondary" wire:click="clearFilters" class="cursor-pointer">
                            پاک کردن فیلترها
                        </x-ui.button>
                    </x-ui.empty-state>
                @else
                    <x-ui.empty-state icon="hierarchy-2"
                                      title="هنوز ورکفلویی نساخته‌اید"
                                      description="اولین ورکفلوی خود را بسازید و فرآیندهای تکراری، ربات‌ها و هوش مصنوعی را به هم متصل کنید.">
                        <x-ui.button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create" class="cursor-pointer">
                            <x-ui.icon name="plus" class="size-4"/>
                            ساخت اولین ورکفلو
                        </x-ui.button>
                    </x-ui.empty-state>
                @endif
            </x-ui.card>
        @endforelse
    </div>

    @if ($this->workflows->hasPages())
        <div>
            {{ $this->workflows->links() }}
        </div>
    @endif

    <x-workflows.modals :deleting-name="$this->deleting?->name"/>
</div>

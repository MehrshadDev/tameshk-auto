<?php

use App\Models\Edge;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Subscriptions\SubscriptionManager;
use App\Workflow\Exceptions\WorkflowPublishException;
use App\Workflow\Publishing\WorkflowPublisher;
use App\Workflow\Registry\NodeRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
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
        $userId = auth()->id();
        $debounceKey = "workflow-create-lock:$userId";

        if (! Cache::add($debounceKey, true, 3)) {
            return;
        }

        $key = 'workflow-create:' . $userId;

        if (RateLimiter::tooManyAttempts($key, config('workflow.create_per_minute', 10))) {
            Cache::forget($debounceKey);
            $this->notify('درخواست‌های زیادی فرستادی. یک دقیقه صبر کن.', 'error');

            return;
        }

        RateLimiter::hit($key);

        try {
            $workflow = DB::transaction(function () use ($userId) {
                $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

                $this->authorize('create', Workflow::class);

                return $user->workflows()->create([
                    'name' => 'ورکفلو بدون عنوان',
                    'settings' => ['sort_order' => '000000'],
                ]);
            });
        } catch (AuthorizationException $e) {
            Cache::forget($debounceKey);
            $this->notify($e->getMessage(), 'error');

            return;
        }

        $this->redirectRoute('app.workflows.show', ['workflow' => $workflow]);
    }

    public function duplicate(int $id): void
    {
        $userId = auth()->id();
        $debounceKey = "workflow-dup-lock:$userId";

        if (! Cache::add($debounceKey, true, 3)) {
            return;
        }

        $key = 'workflow-create:' . $userId;

        if (RateLimiter::tooManyAttempts($key, config('workflow.create_per_minute', 10))) {
            Cache::forget($debounceKey);
            $this->notify('درخواست‌های زیادی فرستادی. یک دقیقه صبر کن.', 'error');

            return;
        }

        RateLimiter::hit($key);

        $source = $this->ownedWorkflow($id);
        $this->authorize('view', $source);

        try {
            DB::transaction(function () use ($source, $userId) {
                $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

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
            Cache::forget($debounceKey);
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

        RateLimiter::hit($rateKey);

        $maxAllowed = $this->maxTotalWorkflows ?? 200;

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
    public function maxTotalWorkflows(): ?int
    {
        return app(SubscriptionManager::class)->maxTotalWorkflows(auth()->user());
    }

    #[Computed]
    public function maxActiveWorkflows(): ?int
    {
        return app(SubscriptionManager::class)->maxActiveWorkflows(auth()->user());
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
        $max = $this->maxTotalWorkflows;

        return $max !== null && $this->total >= $max;
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
        :max="$this->maxTotalWorkflows"
        :active="$this->activeCount"
        :max-active="$this->maxActiveWorkflows"
        :runs="$this->totalRunsCount"
        :success-rate="$this->successRate"
    />

    <x-workflows.toolbar
        :search="$search"
        :status="$status"
        :at-limit="$this->atLimit"
    />

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
                                      title="هنوز ورکفلویی نساخته‌اید">
                        <x-ui.button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create" class="cursor-pointer">
                            <x-ui.icon name="plus" class="size-4"/>
                            <span wire:loading.remove wire:target="create">ساخت اولین ورکفلو</span>
                            <span wire:loading wire:target="create">در حال ساخت...</span>
                        </x-ui.button>
                    </x-ui.empty-state>
                @endif
            </x-ui.card>
        @endforelse
    </div>

    <x-ui.pagination :paginator="$this->workflows" />

    <x-workflows.modals :deleting-name="$this->deleting?->name"/>
</div>

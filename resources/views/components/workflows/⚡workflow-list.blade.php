<?php

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use App\Workflow\Exceptions\WorkflowPublishException;
use App\Workflow\Publishing\WorkflowPublisher;

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
        $this->status = in_array($this->status, ['all', 'active', 'inactive'], true) ? $this->status : 'all';
    }

    public function updatedSearch(): void
    {
        $this->search = mb_substr($this->search, 0, 100);
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->status = in_array($this->status, ['all', 'active', 'inactive'], true) ? $this->status : 'all';
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        $status = in_array($status, ['all', 'active', 'inactive'], true) ? $status : 'all';

        if ($this->status === $status) {
            return;
        }

        $this->status = $status;
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

        if (RateLimiter::tooManyAttempts($key, config('workflow.create_per_minute'))) {
            $this->notify('درخواست‌های زیادی فرستادی. یه دقیقه صبر کن.', 'error');

            return;
        }

        RateLimiter::hit($key);

        try {
            DB::transaction(function () {
                $user = User::query()->whereKey(auth()->id())->lockForUpdate()->firstOrFail();

                $this->authorize('create', Workflow::class);

                $user->workflows()->create(['name' => 'ورکفلو بدون عنوان']);
            });
        } catch (AuthorizationException $e) {
            $this->notify($e->getMessage(), 'error');

            return;
        }

        $this->clearFilters();
        $this->notify('ورکفلو جدید ساخته شد.');
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

        $this->notify($activate ? 'ورکفلو فعال شد.' : 'ورکفلو غیرفعال شد.');
    }

    public function unpublish(int $id, WorkflowPublisher $publisher): void
    {
        $workflow = $this->ownedWorkflow($id);

        $this->authorize('update', $workflow);

        $publisher->unpublish($workflow);

        $this->notify('انتشار ورکفلو لغو شد.');
    }

    public function confirmDelete(int $id): void
    {
        $workflow = $this->ownedWorkflow($id);

        $this->authorize('delete', $workflow);

        $this->deletingId = $workflow->id;
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

        unset($this->workflows);

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
    }

    public function saveEdit(): void
    {
        $this->editName = trim($this->editName);
        $this->editDescription = trim($this->editDescription);

        $data = $this->validate(
            [
                'editName' => ['required', 'string', 'max:100', 'regex:/^(?!.*\S{40,}).*$/u'],
                'editDescription' => ['nullable', 'string', 'max:500'],
            ],
            [
                'editName.required' => 'نام ورکفلو الزامیه.',
                'editName.regex' => 'نام ورکفلو نباید کلمه‌ی خیلی طولانی بدون فاصله داشته باشه.',
                'editName.max' => 'نام ورکفلو نباید بیشتر از ۱۰۰ کاراکتر باشه.',
                'editDescription.max' => 'توضیحات نباید بیشتر از ۵۰۰ کاراکتر باشه.',
            ],
        );

        $workflow = $this->ownedWorkflow($this->editingId);

        $this->authorize('update', $workflow);

        $workflow->update([
            'name' => $data['editName'],
            'description' => $data['editDescription'] !== '' ? $data['editDescription'] : null,
        ]);

        $this->editingId = null;
        $this->dispatch('close-modal', name: 'edit-workflow');
        $this->notify('تغییرات ذخیره شد.');
    }

    #[Computed]
    public function workflows()
    {
        return auth()->user()
            ->workflows()
            ->with('latestRun')
            ->search($this->search)
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest('updated_at')
            ->paginate(10);
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

    private function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', message: $message, type: $type);
    }
};
?>

<div class="mx-auto max-w-6xl space-y-5 p-4 sm:p-6">
    <div class="flex items-center justify-between gap-4">
        <p class="text-sm text-content-muted">
            <span class="font-bold text-content-main">{{ $this->total }}</span>
            از {{ config('workflow.max_per_user') }} ورکفلو
        </p>

        <x-ui.button type="button"
                     wire:click="create"
                     wire:loading.attr="disabled"
                     wire:target="create"
                     :disabled="$this->atLimit">
            <x-ui.icon name="plus" class="size-4"/>
            ورکفلو جدید
        </x-ui.button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <x-ui.icon name="search"
                       class="pointer-events-none absolute inset-s-3.5 top-1/2 size-5 -translate-y-1/2 text-content-muted"/>
            <input type="text"
                   maxlength="100"
                   wire:model.live.debounce.300ms="search"
                   placeholder="جستجو در نام و توضیحات..."
                   class="w-full rounded-xl border border-line-border bg-surface-card py-2.5 pe-4 ps-11 text-sm text-content-main transition placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/40">
        </div>

        <div class="flex items-center gap-1 rounded-xl border border-line-border bg-surface-card p-1">
            @foreach (['all' => 'همه', 'active' => 'فعال', 'inactive' => 'غیرفعال'] as $key => $label)
                <button type="button"
                        @if ($status !== $key) wire:click="setStatus('{{ $key }}')" @endif
                    @class([
                        'flex-1 rounded-lg px-4 py-1.5 text-sm transition sm:w-24 sm:flex-none',
                        'cursor-default bg-primary/10 font-medium text-primary' => $status === $key,
                        'cursor-pointer text-content-muted hover:text-content-main' => $status !== $key,
                    ])>
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="space-y-2.5">
        @forelse ($this->workflows as $workflow)
            <x-workflows.row :workflow="$workflow" wire:key="workflow-{{ $workflow->id }}"/>
        @empty
            <x-ui.card :padding="false">
                @if ($this->hasFilters)
                    <x-ui.empty-state icon="search"
                                      title="نتیجه‌ای پیدا نشد"
                                      description="ورکفلویی با این جستجو یا فیلتر وجود نداره.">
                        <x-ui.button type="button" variant="secondary" wire:click="clearFilters">
                            پاک کردن فیلترها
                        </x-ui.button>
                    </x-ui.empty-state>
                @else
                    <x-ui.empty-state icon="hierarchy-2"
                                      title="هنوز ورکفلویی نساختی"
                                      description="اولین ورکفلوت رو بساز و کارهای تکراریت رو خودکار کن.">
                        <x-ui.button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create">
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

    <x-ui.modal name="edit-workflow" title="تغییر نام و توضیح">
        <form wire:submit="saveEdit" class="space-y-4">
            <x-ui.input name="editName" label="نام" wire:model="editName" maxlength="100" placeholder="مثلاً: ارسال گزارش روزانه"/>

            <div>
                <label for="editDescription" class="mb-1.5 block text-sm font-medium text-content-main">توضیحات</label>
                <textarea id="editDescription"
                          rows="3"
                          maxlength="500"
                          wire:model="editDescription"
                          placeholder="این ورکفلو چه کاری انجام می‌ده؟"
                          class="w-full resize-none rounded-xl border border-line-border bg-surface-base px-4 py-2.5 text-sm text-content-main transition placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/40"></textarea>
                @error('editDescription')
                <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'edit-workflow')">انصراف</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="saveEdit">ذخیره</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal name="delete-workflow" title="حذف ورکفلو" maxWidth="sm">
        <p class="text-sm leading-7 text-content-muted">
            ورکفلوی
            <span class="wrap-break-word font-bold text-content-main">«{{ \Illuminate\Support\Str::limit($this->deleting?->name, 60) }}»</span>
            حذف می‌شه و دیگه اجرا نخواهد شد. مطمئنی؟
        </p>

        <x-slot:footer>
            <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'delete-workflow')">انصراف</x-ui.button>
            <x-ui.button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">بله، حذف کن</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>

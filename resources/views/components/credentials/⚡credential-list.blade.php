<?php

use App\Credentials\Exceptions\InvalidCredentialException;
use App\Credentials\Registry\CredentialRegistry;
use App\Models\Credential;
use App\Models\Node;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'type', except: 'all')]
    public string $typeFilter = 'all';

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public string $selectedType = '';

    public string $credentialName = '';

    public array $formData = [];

    public function mount(): void
    {
        $this->search = mb_substr(trim($this->search), 0, 100);
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->selectedType = '';
        $this->credentialName = '';
        $this->formData = [];
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'credential-form-modal');
    }

    public function selectType(string $type): void
    {
        $types = $this->types;

        if (! isset($types[$type])) {
            return;
        }

        $this->selectedType = $type;
        $this->credentialName = $types[$type]['name'];
        $this->formData = [];

        foreach ($types[$type]['schema'] as $key => $field) {
            $this->formData[$key] = isset($field['default']) ? (string) $field['default'] : '';
        }

        $this->resetValidation();
    }

    public function backToTypeList(): void
    {
        if ($this->editingId !== null) {
            return;
        }

        $this->selectedType = '';
        $this->credentialName = '';
        $this->formData = [];
        $this->resetValidation();
    }

    public function openEdit(int $id): void
    {
        $credential = $this->ownedCredential($id);
        $this->authorize('update', $credential);

        $types = $this->types;

        if (! isset($types[$credential->type])) {
            return;
        }

        $this->editingId = $credential->id;
        $this->selectedType = $credential->type;
        $this->credentialName = $credential->name;
        $this->formData = [];

        foreach ($types[$credential->type]['schema'] as $key => $field) {
            $isSecret = ($field['type'] ?? 'text') === 'password';
            $this->formData[$key] = $isSecret ? '' : (string) ($credential->get($key) ?? ($field['default'] ?? ''));
        }

        $this->resetValidation();
        $this->dispatch('open-modal', name: 'credential-form-modal');
    }

    public function saveCredential(CredentialRegistry $registry): void
    {
        $rateKey = 'credential-save:' . auth()->id();

        if (RateLimiter::tooManyAttempts($rateKey, 20)) {
            $this->notify('تعداد درخواست‌ها بیش از حد مجاز است. کمی صبر کنید.', 'error');

            return;
        }

        RateLimiter::hit($rateKey, 60);

        $types = $this->types;

        if (! isset($types[$this->selectedType])) {
            $this->notify('نوع سرویس انتخاب‌شده معتبر نیست.', 'error');

            return;
        }

        $this->credentialName = trim(strip_tags($this->credentialName));

        $this->validate(
            [
                'credentialName' => ['required', 'string', 'min:2', 'max:100'],
            ],
            [
                'credentialName.required' => 'نام اعتبارنامه الزامی است.',
                'credentialName.min' => 'نام اعتبارنامه باید حداقل ۲ کاراکتر باشد.',
                'credentialName.max' => 'نام اعتبارنامه نباید بیشتر از ۱۰۰ کاراکتر باشد.',
            ],
        );

        $schema = $types[$this->selectedType]['schema'] ?? [];
        $cleanInput = [];

        foreach ($schema as $key => $field) {
            $val = isset($this->formData[$key]) && is_scalar($this->formData[$key])
                ? trim((string) $this->formData[$key])
                : '';

            if (mb_strlen($val) > 4000) {
                throw ValidationException::withMessages([
                    "formData.$key" => 'مقدار واردشده بیش از حد طولانی است.',
                ]);
            }

            $cleanInput[$key] = $val;
        }

        if ($this->editingId !== null) {
            $credential = $this->ownedCredential($this->editingId);
            $this->authorize('update', $credential);

            $existingData = is_array($credential->data) ? $credential->data : [];
            $mergedData = $existingData;
            $hasAnyNewSecret = false;

            foreach ($schema as $key => $field) {
                if ($cleanInput[$key] !== '') {
                    $mergedData[$key] = $cleanInput[$key];
                    $hasAnyNewSecret = true;
                }
            }

            if ($hasAnyNewSecret) {
                try {
                    $registry->make($credential->type)->validate($mergedData);
                } catch (InvalidCredentialException $e) {
                    throw ValidationException::withMessages(['formData' => $e->getMessage()]);
                }
            }

            $credential->update([
                'name' => $this->credentialName,
                'data' => $mergedData,
            ]);

            $this->editingId = null;
            $this->formData = [];
            unset($this->credentials, $this->allCredentials);
            $this->dispatch('close-modal', name: 'credential-form-modal');
            $this->notify('اعتبارنامه با موفقیت بروزرسانی شد.');

            return;
        }

        $this->authorize('create', Credential::class);

        if (auth()->user()->credentials()->count() >= 50) {
            $this->notify('به سقف مجاز ثبت اعتبارنامه رسیده‌اید.', 'error');

            return;
        }

        try {
            $registry->make($this->selectedType)->validate($cleanInput);
        } catch (InvalidCredentialException $e) {
            throw ValidationException::withMessages(['formData' => $e->getMessage()]);
        }

        $created = auth()->user()->credentials()->create([
            'type' => $this->selectedType,
            'name' => $this->credentialName,
            'data' => $cleanInput,
        ]);

        $this->formData = [];
        unset($this->credentials, $this->allCredentials);
        $this->dispatch('close-modal', name: 'credential-form-modal');
        $this->notify('اعتبارنامه با موفقیت ذخیره شد.');

        if (in_array($created->type, ['gmail', 'google'], true)) {
            $this->dispatch('trigger-google-oauth', id: $created->id, type: $created->type);
        }
    }

    public function confirmDelete(int $id): void
    {
        $credential = $this->ownedCredential($id);
        $this->authorize('delete', $credential);

        $this->deletingId = $credential->id;
        $this->dispatch('open-modal', name: 'delete-credential');
    }

    public function deleteCredential(): void
    {
        $credential = $this->ownedCredential($this->deletingId);
        $this->authorize('delete', $credential);

        $credential->delete();

        $this->deletingId = null;
        unset($this->credentials, $this->allCredentials, $this->workflowUsageByCredential);
        $this->dispatch('close-modal', name: 'delete-credential');
        $this->notify('اعتبارنامه حذف شد.');
    }

    #[Computed]
    public function types(): array
    {
        $definitions = app(CredentialRegistry::class)->definitionsAsArray();

        return collect($definitions)->keyBy('key')->all();
    }

    #[Computed]
    public function filterOptions(): array
    {
        $options = [
            ['value' => 'all', 'label' => 'همه سرویس‌ها', 'icon' => 'key'],
        ];

        foreach ($this->types as $typeKey => $def) {
            $options[] = [
                'value' => $typeKey,
                'label' => $def['name'],
                'icon' => $def['icon'] ?? 'key',
            ];
        }

        return $options;
    }

    #[Computed]
    public function allCredentials()
    {
        return auth()->user()->credentials()->get();
    }

    #[Computed]
    public function workflowUsageByCredential(): array
    {
        $nodes = Node::query()
            ->whereHas('workflow', fn ($q) => $q->where('user_id', auth()->id()))
            ->get(['workflow_id', 'config']);

        $map = [];

        foreach ($nodes as $node) {
            $config = is_array($node->config) ? $node->config : [];

            foreach (['credential_id', 'basic_credential_id'] as $field) {
                $credId = isset($config[$field]) && is_numeric($config[$field]) ? (int) $config[$field] : 0;

                if ($credId > 0) {
                    $map[$credId][$node->workflow_id] = true;
                }
            }
        }

        return array_map('count', $map);
    }

    #[Computed]
    public function credentials()
    {
        $term = trim($this->search);

        return auth()->user()
            ->credentials()
            ->when($this->typeFilter !== 'all' && isset($this->types[$this->typeFilter]), fn ($q) => $q->where('type', $this->typeFilter))
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . addcslashes(mb_substr($term, 0, 100), '%_\\') . '%';
                $q->where('name', 'like', $like);
            })
            ->latest('updated_at')
            ->get();
    }

    #[Computed]
    public function deleting(): ?Credential
    {
        return $this->deletingId
            ? auth()->user()->credentials()->find($this->deletingId)
            : null;
    }

    private function ownedCredential(?int $id): Credential
    {
        return auth()->user()->credentials()->findOrFail($id);
    }

    private function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', message: $message, type: $type);
    }
};
?>

<div x-data="{
         openGoogleOAuth(credentialId, serviceType) {
             window.open(
                 `/app/api/oauth/google/redirect?credential_id=${credentialId}`,
                 'google_oauth',
                 'width=550,height=650'
             );

             const expectedEvent = serviceType === 'google' ? 'GOOGLE_OAUTH_RESULT' : 'GMAIL_OAUTH_RESULT';

             const handler = (event) => {
                 if (event.data?.type === expectedEvent) {
                     window.removeEventListener('message', handler);
                     $wire.$refresh();
                     window.dispatchEvent(new CustomEvent('notify', {
                         detail: {
                             message: event.data.message || 'وضعیت اتصال گوگل بروزرسانی شد.',
                             type: event.data.status === 'success' ? 'success' : 'error'
                         }
                     }));
                 }
             };

             window.addEventListener('message', handler);
         }
     }"
     @trigger-google-oauth.window="openGoogleOAuth($event.detail.id, $event.detail.type)"
     class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">

    @php
        $all = $this->allCredentials;
        $usageMap = $this->workflowUsageByCredential;
        $aiTypes = ['gemini', 'openai', 'anthropic', 'deepseek', 'groq', 'openrouter'];
        $aiCount = $all->whereIn('type', $aiTypes)->count();
        $oauthCount = $all->whereIn('type', ['gmail', 'google'])->filter(fn ($c) => ! empty($c->get('refresh_token')))->count();
        $inUseCount = $all->filter(fn ($c) => ($usageMap[$c->id] ?? 0) > 0)->count();
    @endphp

    <x-credentials.stats
        :total="$all->count()"
        :ai-count="$aiCount"
        :oauth-count="$oauthCount"
        :in-use-count="$inUseCount"
    />

    <div class="relative has-aria-expanded:z-40 flex flex-col gap-3 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-col gap-2.5 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <x-ui.icon name="search" class="pointer-events-none absolute inset-s-3.5 top-1/2 size-4.5 -translate-y-1/2 text-content-muted"/>
                <input type="text"
                       maxlength="100"
                       wire:model.live.debounce.300ms="search"
                       placeholder="جستجو در نام اعتبارنامه‌ها..."
                       class="w-full rounded-xl border border-line-border bg-surface-base py-2 pe-9 ps-10 text-xs font-medium text-content-main transition placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"/>
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
                :options="$this->filterOptions"
                :selected="$typeFilter"
                wire-model="typeFilter"
                placeholder="همه سرویس‌ها"
            />
        </div>

        <x-ui.button type="button" wire:click="openCreate" class="cursor-pointer shrink-0">
            <x-ui.icon name="plus" class="size-4"/>
            <span>افزودن اعتبارنامه</span>
        </x-ui.button>
    </div>

    <div class="space-y-2.5">
        @forelse ($this->credentials as $credential)
            <x-credentials.card
                :credential="$credential"
                :definition="$this->types[$credential->type] ?? null"
                :workflows-count="$usageMap[$credential->id] ?? 0"
                wire:key="cred-{{ $credential->id }}"
            />
        @empty
            <x-ui.card :padding="false">
                @if ($search !== '' || $typeFilter !== 'all')
                    <x-ui.empty-state icon="search"
                                      title="اعتبارنامه‌ای یافت نشد"
                                      description="هیچ حساب یا کلیدی با این فیلتر پیدا نشد.">
                        <x-ui.button type="button" variant="secondary" wire:click="$set('search', ''); $set('typeFilter', 'all')" class="cursor-pointer">
                            نمایش همه
                        </x-ui.button>
                    </x-ui.empty-state>
                @else
                    <x-ui.empty-state icon="key"
                                      title="هنوز اعتبارنامه‌ای ثبت نکرده‌اید"
                                      description="کلیدهای API هوش مصنوعی، ربات تلگرام یا حساب گوگل خود را اینجا اضافه کنید تا در همه ورکفلوها به آن‌ها دسترسی داشته باشید.">
                        <x-ui.button type="button" wire:click="openCreate" class="cursor-pointer">
                            <x-ui.icon name="plus" class="size-4"/>
                            افزودن اولین اعتبارنامه
                        </x-ui.button>
                    </x-ui.empty-state>
                @endif
            </x-ui.card>
        @endforelse
    </div>

    <x-credentials.modals
        :types="$this->types"
        :selected-type="$selectedType"
        :editing-id="$editingId"
        :deleting-name="$this->deleting?->name"
    />
</div>

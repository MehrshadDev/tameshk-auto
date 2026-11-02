@php use Illuminate\Support\Str; @endphp
@props([
    'types' => [],
    'selectedType' => '',
    'editingId' => null,
    'deletingName' => null,
])

@php
    $activeDef = $selectedType !== '' ? ($types[$selectedType] ?? null) : null;
    $schema = $activeDef['schema'] ?? [];
    $isOAuthType = in_array($selectedType, ['gmail', 'google'], true);
    $callbackUrl = url('/oauth/google/callback');
    $modalTitle = $editingId
        ? 'ویرایش اعتبارنامه — ' . ($activeDef['name'] ?? '')
        : ($activeDef ? 'افزودن ' . $activeDef['name'] : 'انتخاب سرویس اعتبارنامه');
@endphp

<x-ui.modal name="credential-form-modal"
            :title="$modalTitle"
            :icon="$activeDef['icon'] ?? null"
            maxWidth="xl">
    @if (! $editingId && ! $activeDef)
        <div x-data="{ typeQuery: '' }" class="space-y-4">
            <div class="relative">
                <x-ui.icon name="search"
                           class="pointer-events-none absolute inset-s-3.5 top-1/2 size-4 -translate-y-1/2 text-content-muted"/>
                <input type="text"
                       x-model="typeQuery"
                       placeholder="جستجوی سرویس (مثلاً: Gemini, OpenAI, Telegram, Gmail)..."
                       class="w-full rounded-xl border border-line-border bg-surface-base py-2.5 pe-4 ps-10 text-xs font-medium text-content-main placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"/>
            </div>

            <div class="custom-scrollbar grid max-h-96 grid-cols-1 gap-2.5 overflow-y-auto pe-1 sm:grid-cols-2">
                @foreach ($types as $typeKey => $def)
                    <button type="button"
                            data-search="{{ mb_strtolower($def['name'] . ' ' . $def['description'] . ' ' . $typeKey) }}"
                            x-show="!typeQuery.trim() || $el.dataset.search.includes(typeQuery.trim().toLowerCase())"
                            wire:click="selectType('{{ $typeKey }}')"
                            class="group flex items-start gap-3 rounded-2xl border border-line-border bg-surface-card p-3.5 text-start transition hover:border-primary/50 hover:bg-surface-raised cursor-pointer">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-line-border/60 bg-surface-base text-primary transition group-hover:scale-105">
                            <x-ui.icon :name="$def['icon'] ?? 'key'" class="size-5"/>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold text-content-main group-hover:text-primary">{{ $def['name'] }}</p>
                            <p dir="auto" class="mt-1 line-clamp-2 text-[11px] leading-5 text-content-muted">{{ $def['description'] }}</p>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    @elseif ($activeDef)
        <form wire:key="cred-form-{{ $editingId ?? 'new' }}-{{ $selectedType }}"
              wire:submit="saveCredential"
              autocomplete="off"
              class="space-y-4">
            @if (! $editingId)
                <button type="button"
                        wire:click="backToTypeList"
                        class="flex w-full items-center justify-between gap-3 rounded-xl border border-line-border/70 bg-surface-card px-3.5 py-2.5 text-start transition hover:border-primary/40 hover:bg-surface-raised cursor-pointer">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <x-ui.icon :name="$activeDef['icon'] ?? 'key'" class="size-5 shrink-0 text-primary"/>
                        <span class="truncate text-xs font-bold text-content-main">{{ $activeDef['name'] }}</span>
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-1 text-xs font-medium text-primary">
                        <span>تغییر سرویس</span>
                        <x-ui.icon name="chevron-left" class="size-3.5"/>
                    </span>
                </button>
            @endif

            <x-ui.input
                name="credentialName"
                label="نام حساب در تمشک"
                value="{{ $this->credentialName }}"
                wire:model="credentialName"
                maxlength="100"
                placeholder="مثلاً: حساب اصلی شرکت"
            />

            @foreach ($schema as $fieldKey => $field)
                @php
                    $isSecret = ($field['type'] ?? 'text') === 'password';
                    $label = $field['label'] ?? $fieldKey;
                    $placeholder = $editingId && $isSecret
                        ? 'بدون تغییر (برای تغییر، مقدار جدید وارد کنید)'
                        : ($field['placeholder'] ?? '');
                @endphp

                <div>
                    <x-ui.input
                        :name="'formData.' . $fieldKey"
                        :type="$isSecret ? 'password' : 'text'"
                        :label="$label"
                        :placeholder="$placeholder"
                        value="{{ $this->formData[$fieldKey] ?? '' }}"
                        wire:model="formData.{{ $fieldKey }}"
                        autocomplete="new-password"
                    />
                    @if (! empty($field['help']))
                        <p dir="auto" class="mt-1.5 text-start text-[11px] leading-5 text-content-muted/80">{{ $field['help'] }}</p>
                    @endif
                </div>
            @endforeach

            @error('formData')
            <div class="rounded-xl border border-primary/20 bg-primary/10 p-3 text-xs font-medium text-primary">
                {{ $message }}
            </div>
            @enderror

            @if ($isOAuthType)
                <div>
                    <label dir="auto" class="mb-1.5 block text-start text-sm font-medium text-content-main">
                        Redirect URI
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="text"
                               readonly
                               dir="ltr"
                               value="{{ $callbackUrl }}"
                               onclick="this.select()"
                               class="w-full rounded-xl border border-line-border bg-surface-base px-4 py-2.5 font-mono text-xs text-content-muted focus:border-primary focus:outline-none"/>
                        <button type="button"
                                data-url="{{ $callbackUrl }}"
                                title="کپی آدرس"
                                @click="navigator.clipboard.writeText($el.dataset.url); $dispatch('notify', { message: 'آدرس Redirect URI کپی شد.', type: 'success' })"
                                class="flex size-10.5 shrink-0 items-center justify-center rounded-xl border border-line-border bg-surface-base text-content-muted transition hover:border-primary hover:text-primary cursor-pointer">
                            <x-ui.icon name="copy" class="size-4.5"/>
                        </button>
                    </div>
                    <p dir="auto" class="mt-1.5 text-start text-[11px] leading-5 text-content-muted/80">
                        این آدرس را در بخش Authorized redirect URIs در کنسول Google Cloud ثبت کنید.
                    </p>
                </div>
            @endif

            <div class="flex items-center justify-end gap-2 border-t border-line-border/60 pt-4">
                <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'credential-form-modal')"
                             class="cursor-pointer">
                    انصراف
                </x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="saveCredential"
                             class="cursor-pointer">
                    {{ $editingId ? 'ذخیره تغییرات' : ($isOAuthType ? 'ذخیره و اتصال به گوگل' : 'ذخیره اعتبارنامه') }}
                </x-ui.button>
            </div>
        </form>
    @endif
</x-ui.modal>

<x-ui.modal name="delete-credential" title="حذف اعتبارنامه" maxWidth="sm">
    <div class="flex items-start gap-3.5">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <x-ui.icon name="trash" class="size-5"/>
        </div>
        <p class="text-xs leading-6 text-content-muted">
            اعتبارنامه
            <span class="wrap-break-word font-bold text-content-main">«{{ Str::limit($deletingName, 60) }}»</span>
            حذف خواهد شد. نودهایی که در ورکفلوها به این حساب متصل هستند تا زمان انتخاب اعتبارنامه جدید با خطا مواجه
            می‌شوند.
        </p>
    </div>

    <x-slot:footer>
        <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'delete-credential')"
                     class="cursor-pointer">انصراف
        </x-ui.button>
        <x-ui.button type="button" wire:click="deleteCredential" wire:loading.attr="disabled"
                     wire:target="deleteCredential" class="cursor-pointer">بله، حذف کن
        </x-ui.button>
    </x-slot:footer>
</x-ui.modal>

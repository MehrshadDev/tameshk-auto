@props([
    'selectedDepartment' => '',
    'selectedPriority' => 'medium',
    'attachments' => [],
])

@php
    use App\Tickets\Enums\TicketDepartment;
    use App\Tickets\Enums\TicketPriority;

    $departmentOptions = array_map(fn (TicketDepartment $dept) => [
        'value' => $dept->value,
        'label' => $dept->label(),
    ], TicketDepartment::cases());

    $priorityOptions = array_map(fn (TicketPriority $prio) => [
        'value' => $prio->value,
        'label' => $prio->label(),
    ], TicketPriority::cases());
@endphp

<x-ui.modal name="create-ticket-modal" title="ارسال تیکت پشتیبانی جدید" icon="ticket" maxWidth="2xl">
    <form wire:submit="createTicket" class="space-y-3.5">
        <x-ui.input
            name="subject"
            label="عنوان تیکت"
            wire:model="subject"
            maxlength="200"
            placeholder="مثلاً: راهنمایی درباره اتصال وب‌هوک تلگرام"
        />

        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-start text-sm font-medium text-content-main">
                    بخش مربوطه
                </label>
                <x-ui.select
                    :options="$departmentOptions"
                    :selected="$selectedDepartment"
                    wire-model="ticketDepartment"
                    placeholder="بخش مربوطه را انتخاب کنید..."
                    :searchable="false"
                    :full-width="true"
                />
                @error('ticketDepartment')
                <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-start text-sm font-medium text-content-main">
                    اولویت تیکت
                </label>
                <x-ui.select
                    :options="$priorityOptions"
                    :selected="$selectedPriority"
                    wire-model="ticketPriority"
                    placeholder="انتخاب اولویت"
                    :searchable="false"
                    :full-width="true"
                />
                @error('ticketPriority')
                <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="ticket_body" class="mb-1.5 block text-start text-sm font-medium text-content-main">
                شرح کامل درخواست یا مشکل
            </label>
            <textarea id="ticket_body"
                      rows="4"
                      maxlength="5000"
                      dir="auto"
                      style="unicode-bidi: plaintext;"
                      wire:model="body"
                      placeholder="جزئیات سوال، نام ورکفلو یا خطایی که دریافت کرده‌اید را بنویسید..."
                      class="w-full resize-none rounded-xl border border-line-border bg-surface-base px-4 py-2.5 text-start text-xs leading-6 text-content-main transition placeholder:text-right placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/40 sm:text-sm"></textarea>
            @error('body')
            <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-dashed border-line-border bg-surface-base px-3.5 py-2 text-xs font-semibold text-content-muted transition hover:border-primary/50 hover:text-primary">
                    <x-ui.icon name="photo" class="size-4"/>
                    <span>پیوست تصویر (حداکثر ۳ تصویر تا ۵ مگابایت)</span>
                    <input type="file"
                           wire:model="attachments"
                           multiple
                           accept="image/png,image/jpeg,image/webp,image/gif"
                           class="hidden"/>
                </label>

                <span wire:loading wire:target="attachments" class="text-xs font-medium text-primary">
                    در حال بارگذاری تصویر...
                </span>
            </div>

            @error('attachments')
            <p class="text-xs text-primary">{{ $message }}</p>
            @enderror
            @error('attachments.*')
            <p class="text-xs text-primary">{{ $message }}</p>
            @enderror

            @if (! empty($attachments))
                <div class="flex flex-wrap items-center gap-2.5 pt-1">
                    @foreach ($attachments as $idx => $file)
                        @if (is_object($file) && method_exists($file, 'temporaryUrl'))
                            <div class="group relative size-16 overflow-hidden rounded-xl border border-line-border bg-surface-base shadow-2xs">
                                <img src="{{ $file->temporaryUrl() }}" alt="پیوست" class="size-full object-cover">
                                <button type="button"
                                        wire:click="removeAttachment({{ $idx }})"
                                        aria-label="حذف تصویر"
                                        class="absolute top-1 left-1 flex size-5 items-center justify-center rounded-md bg-black/70 text-white transition hover:bg-primary cursor-pointer">
                                    <x-ui.icon name="x" class="size-3.5"/>
                                </button>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-line-border/60 pt-3.5">
            <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'create-ticket-modal')" class="cursor-pointer">
                انصراف
            </x-ui.button>
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="createTicket, attachments" class="cursor-pointer">
                <x-ui.icon name="send" class="size-4 -scale-x-100"/>
                <span wire:loading.remove wire:target="createTicket">ثبت و ارسال تیکت</span>
                <span wire:loading wire:target="createTicket">در حال ثبت...</span>
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>

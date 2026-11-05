<?php

use App\Models\Ticket;
use App\Tickets\Exceptions\TicketException;
use App\Tickets\TicketManager;
use App\Workflow\Support\DateTimeHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    #[Locked]
    public int $ticketId;

    public string $replyBody = '';
    public array $attachments = [];

    public function mount(Ticket $ticket): void
    {
        $this->authorize('view', $ticket);
        $this->ticketId = $ticket->id;
    }

    public function updatedAttachments(): void
    {
        if (count($this->attachments) > 3) {
            $this->attachments = array_slice($this->attachments, 0, 3);
            $this->notify('حداکثر ۳ تصویر می‌توانید پیوست کنید.', 'error');
        }
    }

    public function removeAttachment(int $index): void
    {
        if (isset($this->attachments[$index])) {
            unset($this->attachments[$index]);
            $this->attachments = array_values($this->attachments);
        }
    }

    public function sendReply(TicketManager $manager): void
    {
        $ticket = $this->ticket;
        $this->authorize('reply', $ticket);

        $userId = auth()->id();
        $lockKey = "ticket-reply-lock:{$userId}:{$ticket->id}";

        if (!Cache::add($lockKey, true, 3)) {
            return;
        }

        $rateKey = "ticket-reply:{$userId}";

        if (!$this->isAdmin && RateLimiter::tooManyAttempts($rateKey, 8)) {
            Cache::forget($lockKey);
            $this->notify('تعداد پیام‌های ارسالی بیش از حد مجاز است. یک دقیقه صبر کنید.', 'error');

            return;
        }

        $this->replyBody = trim($this->replyBody);

        try {
            $this->validate(
                [
                    'replyBody' => ['required', 'string', 'min:2', 'max:5000'],
                    'attachments' => ['nullable', 'array', 'max:3'],
                    'attachments.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
                ],
                [
                    'replyBody.required' => 'متن پاسخ نمی‌تواند خالی باشد.',
                    'replyBody.min' => 'متن پاسخ باید حداقل ۲ کاراکتر باشد.',
                    'replyBody.max' => 'متن پاسخ نباید بیشتر از ۵۰۰۰ کاراکتر باشد.',
                    'attachments.max' => 'حداکثر ۳ تصویر قابل پیوست است.',
                    'attachments.*.image' => 'فایل انتخاب‌شده باید یک تصویر معتبر باشد.',
                    'attachments.*.mimes' => 'فقط فرمت‌های JPG، PNG، WebP و GIF مجاز هستند.',
                    'attachments.*.max' => 'حجم هر تصویر نباید بیشتر از ۵ مگابایت باشد.',
                ],
            );
        } catch (ValidationException $e) {
            Cache::forget($lockKey);
            throw $e;
        }

        if (!$this->isAdmin) {
            RateLimiter::hit($rateKey, 60);
        }

        try {
            $manager->reply($ticket, auth()->user(), $this->replyBody, $this->attachments);
        } catch (TicketException $e) {
            Cache::forget($lockKey);
            $this->notify($e->getMessage(), 'error');

            return;
        }

        $this->replyBody = '';
        $this->attachments = [];
        $this->resetValidation();
        unset($this->ticket);
        $this->notify('پاسخ شما با موفقیت ثبت شد.');
    }

    public function closeTicket(TicketManager $manager): void
    {
        $ticket = $this->ticket;
        $this->authorize('updateStatus', $ticket);

        if (!$this->allowStatusToggle($ticket->id)) {
            return;
        }

        $manager->close($ticket);

        unset($this->ticket);
        $this->notify('تیکت بسته شد.');
    }

    public function reopenTicket(TicketManager $manager): void
    {
        $ticket = $this->ticket;
        $this->authorize('updateStatus', $ticket);

        if (!$this->allowStatusToggle($ticket->id)) {
            return;
        }

        $manager->reopen($ticket, auth()->user());

        unset($this->ticket);
        $this->notify('تیکت مجدداً بازگشایی شد.');
    }

    public function deleteTicket(TicketManager $manager): void
    {
        $ticket = $this->ticket;
        $this->authorize('delete', $ticket);

        $manager->delete($ticket);

        $this->redirectRoute('app.tickets.index');
    }

    #[Computed]
    public function ticket(): Ticket
    {
        $ticket = Ticket::query()
            ->with(['user:id,name,email,avatar', 'messages.user:id,name,email,avatar'])
            ->findOrFail($this->ticketId);

        $this->authorize('view', $ticket);

        return $ticket;
    }

    #[Computed]
    public function isAdmin(): bool
    {
        return auth()->user()->isAdmin();
    }

    private function allowStatusToggle(int $ticketId): bool
    {
        if ($this->isAdmin) {
            return true;
        }

        $userId = auth()->id();
        $lockKey = "ticket-status-lock:{$userId}:{$ticketId}";

        if (!Cache::add($lockKey, true, 3)) {
            return false;
        }

        $rateKey = "ticket-status-toggle:{$userId}:{$ticketId}";

        if (RateLimiter::tooManyAttempts($rateKey, 2)) {
            $seconds = RateLimiter::availableIn($rateKey);
            $minutes = max(1, (int)ceil($seconds / 60));
            $this->notify("تعداد دفعات تغییر وضعیت این تیکت بیش از حد مجاز است. لطفاً {$minutes} دقیقه دیگر تلاش کنید.", 'error');

            return false;
        }

        RateLimiter::hit($rateKey, 900);

        return true;
    }

    private function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', message: $message, type: $type);
    }
};
?>

@php
    $ticket = $this->ticket;
    $createdAt = ($ticket->created_at ?? now())->copy()->setTimezone('Asia/Tehran');
    $createdPersian = DateTimeHelper::toPersianDate($createdAt, 'yyyy/MM/dd HH:mm');
@endphp

<div x-data="{ previewUrl: null }"
     @keydown.escape.window="previewUrl = null"
     class="mx-auto max-w-4xl space-y-6 p-4 sm:p-6">

    <div
        class="flex flex-col justify-between gap-4 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs sm:flex-row sm:items-center sm:p-5">
        <div class="flex min-w-0 items-start gap-3.5">
            <a href="{{ route('app.tickets.index') }}"
               title="بازگشت به لیست تیکت‌ها"
               class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-xl border border-line-border bg-surface-base text-content-muted transition hover:border-primary/40 hover:text-primary">
                <x-ui.icon name="arrow-right" class="size-5"/>
            </a>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 dir="auto" class="text-start text-sm font-extrabold text-content-main sm:text-base">
                        {{ $ticket->subject }}
                    </h2>
                    <span dir="ltr"
                          class="rounded-md border border-line-border/80 bg-surface-raised px-2 py-0.5 font-mono text-[11px] font-bold text-content-muted">
                        #{{ $ticket->id }}
                    </span>
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-content-muted">
                    <x-ui.badge :variant="$ticket->status->badgeVariant()" dot>
                        {{ $ticket->status->label() }}
                    </x-ui.badge>

                    <x-ui.badge :variant="$ticket->priority->badgeVariant()">
                        اولویت: {{ $ticket->priority->label() }}
                    </x-ui.badge>

                    <span
                        class="rounded-lg border border-line-border/70 bg-surface-base px-2.5 py-0.5 text-[11px] font-medium text-content-muted">
                        {{ $ticket->department->label() }}
                    </span>

                    <span dir="ltr" class="font-mono text-[11px] text-content-muted">
                        {{ $createdPersian }}
                    </span>

                    @if ($this->isAdmin && $ticket->user)
                        <span
                            class="inline-flex items-center gap-1.5 rounded-lg border border-primary/25 bg-primary/8 px-2.5 py-0.5 text-[11px] font-bold text-primary">
                            <span>{{ $ticket->user->name }}</span>
                            <span dir="ltr" class="font-mono font-normal opacity-80">({{ $ticket->user->email }})</span>
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex shrink-0 items-center justify-end gap-2">
            @if ($ticket->isClosed())
                <x-ui.button type="button"
                             variant="secondary"
                             wire:click="reopenTicket"
                             wire:loading.attr="disabled"
                             wire:target="reopenTicket"
                             class="cursor-pointer text-xs">
                    <x-ui.icon name="player-play" class="size-4"/>
                    <span>بازگشایی تیکت</span>
                </x-ui.button>
            @else
                <x-ui.button type="button"
                             variant="secondary"
                             wire:click="closeTicket"
                             wire:loading.attr="disabled"
                             wire:target="closeTicket"
                             class="cursor-pointer text-xs">
                    <x-ui.icon name="lock" class="size-4"/>
                    <span>بستن تیکت</span>
                </x-ui.button>
            @endif

            @if ($this->isAdmin)
                <button type="button"
                        @click="$dispatch('open-modal', 'delete-thread-ticket-modal')"
                        title="حذف کامل تیکت"
                        aria-label="حذف تیکت"
                        class="flex size-9.5 items-center justify-center rounded-xl border border-line-border bg-surface-base text-content-muted transition hover:border-primary/40 hover:bg-primary/10 hover:text-primary cursor-pointer">
                    <x-ui.icon name="trash" class="size-4.5"/>
                </button>
            @endif
        </div>
    </div>

    <div class="space-y-4">
        @foreach ($ticket->messages as $msg)
            @php
                $isSupport = $msg->is_admin_reply;
                $senderName = $msg->user?->name ?? 'کاربر';
                $senderAvatar = $msg->user?->avatarUrl();
                $msgDate = ($msg->created_at ?? now())->copy()->setTimezone('Asia/Tehran');
                $msgPersian = DateTimeHelper::toPersianDate($msgDate, 'yyyy/MM/dd HH:mm');
                $images = $msg->attachmentUrls();
            @endphp

            <div wire:key="msg-{{ $msg->id }}"
                @class([
                    'rounded-2xl border p-4 shadow-2xs transition sm:p-5',
                    'border-primary/30 bg-linear-to-b from-primary/6 to-surface-card' => $isSupport,
                    'border-line-border bg-surface-card' => ! $isSupport,
                ])>
                <div class="flex items-center justify-between gap-3 border-b border-line-border/60 pb-3">
                    <div class="flex min-w-0 items-center gap-2.5">
                        @if ($isSupport)
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary text-white shadow-xs shadow-primary/25">
                                <x-ui.icon name="headset" class="size-5"/>
                            </span>
                        @elseif ($senderAvatar)
                            <img src="{{ $senderAvatar }}" alt="{{ $senderName }}"
                                 class="size-9 shrink-0 rounded-xl object-cover ring-1 ring-line-border">
                        @else
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-line-border/60 font-mono text-xs font-bold text-content-main">
                                {{ mb_substr($senderName, 0, 1) }}
                            </span>
                        @endif

                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span dir="auto" class="truncate text-xs font-extrabold text-content-main">
                                    {{ $senderName }}
                                </span>
                                @if ($isSupport)
                                    <span
                                        class="rounded-md bg-primary/15 px-2 py-0.5 text-[10px] font-extrabold text-primary">
                                        پشتیبانی تمشک
                                    </span>
                                @else
                                    <span
                                        class="rounded-md bg-line-border/60 px-2 py-0.5 text-[10px] font-semibold text-content-muted">
                                        کاربر
                                    </span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-[11px] text-content-muted">
                                {{ $msg->created_at?->diffForHumans() }}
                            </p>
                        </div>
                    </div>

                    <span dir="ltr" class="shrink-0 font-mono text-[11px] text-content-muted">
                        {{ $msgPersian }}
                    </span>
                </div>

                <div dir="auto"
                     class="mt-3.5 space-y-2 text-start text-xs leading-7 text-content-main wrap-break-word sm:text-sm sm:leading-8 [&_ol]:list-decimal [&_ol]:pr-5 [&_p]:mb-2 [&_p:last-child]:mb-0 [&_strong]:font-extrabold [&_ul]:list-disc [&_ul]:pr-5">
                    {!! $msg->formattedBody() !!}
                </div>

                @if ($images !== [])
                    <div class="mt-4 flex flex-wrap items-center gap-2.5 border-t border-line-border/50 pt-3.5">
                        @foreach ($images as $imgUrl)
                            <button type="button"
                                    data-img="{{ $imgUrl }}"
                                    @click="previewUrl = $el.dataset.img"
                                    class="group relative size-22 overflow-hidden rounded-xl border border-line-border bg-surface-base shadow-2xs transition hover:border-primary cursor-pointer sm:size-26">
                                <img src="{{ $imgUrl }}" alt="تصویر پیوست" loading="lazy"
                                     class="size-full object-cover transition duration-200 group-hover:scale-105">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if ($ticket->isClosed() && ! $this->isAdmin)
        <div
            class="flex flex-col items-center justify-between gap-3 rounded-2xl border border-line-border bg-surface-card p-5 text-center sm:flex-row sm:text-start">
            <div class="flex items-center gap-3">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-line-border/50 text-content-muted">
                    <x-ui.icon name="lock" class="size-5"/>
                </span>
                <div>
                    <p class="text-xs font-bold text-content-main">این تیکت بسته شده است</p>
                    <p class="mt-0.5 text-[11px] text-content-muted">در صورتی که نیاز به ادامه گفتگو دارید، روی دکمه
                        بازگشایی تیکت کلیک کنید.</p>
                </div>
            </div>

            <x-ui.button type="button" variant="secondary" wire:click="reopenTicket" class="cursor-pointer text-xs">
                بازگشایی و ارسال پیام
            </x-ui.button>
        </div>
    @else
        <div class="rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs sm:p-5">
            <form wire:submit="sendReply" class="space-y-3.5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label for="replyBody" class="flex items-center gap-2 text-xs font-extrabold text-content-main">
                        <x-ui.icon :name="$this->isAdmin ? 'headset' : 'message-circle'" class="size-4 text-primary"/>
                        <span>{{ $this->isAdmin ? 'ارسال پاسخ به عنوان پشتیبان' : 'ارسال پاسخ جدید' }}</span>
                    </label>

                    <div class="flex items-center gap-2">
                        @if ($ticket->isClosed() && $this->isAdmin)
                            <span class="text-[11px] font-medium text-amber-500">
                                با ارسال پاسخ، تیکت خودکار بازگشایی می‌شود
                            </span>
                        @endif
                    </div>
                </div>

                <div>
                    <textarea id="replyBody"
                              rows="4"
                              maxlength="5000"
                              dir="auto"
                              style="unicode-bidi: plaintext;"
                              wire:model="replyBody"
                              placeholder="متن پیام یا پاسخ خود را اینجا بنویسید..."
                              class="w-full resize-none rounded-xl border border-line-border bg-surface-base px-4 py-3 text-start text-xs leading-6 text-content-main transition placeholder:text-right placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 sm:text-sm"></textarea> @error('replyBody')
                    <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
                    @enderror
                </div>

                @if (! empty($attachments))
                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                        @foreach ($attachments as $idx => $file)
                            @if (is_object($file) && method_exists($file, 'temporaryUrl'))
                                <div
                                    class="group relative size-18 overflow-hidden rounded-xl border border-line-border bg-surface-base shadow-2xs">
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

                @error('attachments')
                <p class="text-xs text-primary">{{ $message }}</p>
                @enderror
                @error('attachments.*')
                <p class="text-xs text-primary">{{ $message }}</p>
                @enderror

                <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                    <div class="flex items-center gap-2.5">
                        <label
                            class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-dashed border-line-border bg-surface-base px-3.5 py-2 text-xs font-semibold text-content-muted transition hover:border-primary/50 hover:text-primary">
                            <x-ui.icon name="photo" class="size-4"/>
                            <span>پیوست تصویر (حداکثر ۳ فایل)</span>
                            <input type="file"
                                   wire:model="attachments"
                                   multiple
                                   accept="image/png,image/jpeg,image/webp,image/gif"
                                   class="hidden"/>
                        </label>

                        <span wire:loading wire:target="attachments" class="text-xs font-medium text-primary">
                            در حال بارگذاری...
                        </span>
                    </div>

                    <x-ui.button type="submit"
                                 wire:loading.attr="disabled"
                                 wire:target="sendReply, attachments"
                                 class="cursor-pointer">
                        <x-ui.icon name="send" class="size-4 -scale-x-100"/>
                        <span wire:loading.remove wire:target="sendReply">ارسال پاسخ</span>
                        <span wire:loading wire:target="sendReply">در حال ارسال...</span>
                    </x-ui.button>
                </div>
            </form>
        </div>
    @endif

    <div x-show="previewUrl !== null"
         x-cloak
         x-transition.opacity
         @click="previewUrl = null"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm">
        <button type="button"
                @click="previewUrl = null"
                aria-label="بستن تصویر"
                class="fixed top-5 left-5 flex size-10 items-center justify-center rounded-xl bg-white/10 text-white transition hover:bg-primary cursor-pointer">
            <x-ui.icon name="x" class="size-5"/>
        </button>

        <img :src="previewUrl"
             alt="پیش‌نمایش تصویر"
             @click.stop
             class="max-h-[88vh] max-w-[92vw] rounded-2xl border border-white/15 object-contain shadow-2xl">
    </div>

    @if ($this->isAdmin)
        <x-ui.modal name="delete-thread-ticket-modal" title="حذف کامل تیکت" maxWidth="sm">
            <div class="flex items-start gap-3.5">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <x-ui.icon name="trash" class="size-5"/>
                </div>
                <p class="text-xs leading-6 text-content-muted">
                    آیا از حذف کامل تیکت
                    <span dir="ltr" class="font-mono font-bold text-content-main">#{{ $ticket->id }}</span>
                    و تمام پیام‌ها و تصاویر پیوست آن مطمئن هستید؟ این عملیات غیرقابل بازگشت است.
                </p>
            </div>

            <x-slot:footer>
                <x-ui.button type="button" variant="ghost"
                             @click="$dispatch('close-modal', 'delete-thread-ticket-modal')" class="cursor-pointer">
                    انصراف
                </x-ui.button>
                <x-ui.button type="button" wire:click="deleteTicket" wire:loading.attr="disabled"
                             wire:target="deleteTicket" class="cursor-pointer">
                    بله، حذف کن
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>

<?php

use App\Models\Ticket;
use App\Tickets\Enums\TicketDepartment;
use App\Tickets\Enums\TicketPriority;
use App\Tickets\Enums\TicketStatus;
use App\Tickets\Exceptions\TicketException;
use App\Tickets\TicketManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, WithFileUploads;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: 'all')]
    public string $status = 'all';

    #[Url(as: 'department', except: 'all')]
    public string $department = 'all';

    #[Locked]
    public ?int $deletingTicketId = null;

    public string $subject = '';
    public string $ticketDepartment = '';
    public string $ticketPriority = TicketPriority::Medium->value;
    public string $body = '';
    public array $attachments = [];

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

    public function updatedDepartment(): void
    {
        $this->resetPage();
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
        $this->reset('search', 'status', 'department');
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('create', Ticket::class);

        $this->subject = '';
        $this->ticketDepartment = '';
        $this->ticketPriority = TicketPriority::Medium->value;
        $this->body = '';
        $this->attachments = [];
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'create-ticket-modal');
    }

    public function createTicket(TicketManager $manager): void
    {
        $this->authorize('create', Ticket::class);

        $userId = auth()->id();
        $lockKey = "ticket-create-lock:$userId";

        if (! Cache::add($lockKey, true, 3)) {
            return;
        }

        $rateKey = "ticket-create:$userId";

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            Cache::forget($lockKey);
            $this->notify('تعداد درخواست‌های ثبت تیکت بیش از حد مجاز است. یک دقیقه صبر کنید.', 'error');

            return;
        }

        $this->subject = trim(strip_tags($this->subject));
        $this->body = trim($this->body);

        try {
            $validated = $this->validate(
                [
                    'subject' => ['required', 'string', 'min:5', 'max:100'],
                    'ticketDepartment' => ['required', 'string', Rule::enum(TicketDepartment::class)],
                    'ticketPriority' => ['required', 'string', Rule::enum(TicketPriority::class)],
                    'body' => ['required', 'string', 'min:10', 'max:5000'],
                    'attachments' => ['nullable', 'array', 'max:3'],
                    'attachments.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
                ],
                [
                    'subject.required' => 'عنوان تیکت الزامی است.',
                    'subject.min' => 'عنوان تیکت باید حداقل 5 کاراکتر باشد.',
                    'subject.max' => 'عنوان تیکت نباید بیشتر از 100 کاراکتر باشد.',
                    'ticketDepartment.required' => 'لطفاً بخش مربوطه را از لیست انتخاب کنید.',
                    'ticketDepartment.Illuminate\Validation\Rules\Enum' => 'بخش انتخاب‌شده معتبر نیست.',
                    'ticketPriority.required' => 'انتخاب اولویت الزامی است.',
                    'body.required' => 'متن پیام تیکت الزامی است.',
                    'body.min' => 'متن پیام باید حداقل 10 کاراکتر باشد.',
                    'body.max' => 'متن پیام نباید بیشتر از ۵۰۰۰ کاراکتر باشد.',
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

        RateLimiter::hit($rateKey, 60);

        try {
            $ticket = $manager->create(
                user: auth()->user(),
                subject: $validated['subject'],
                body: $validated['body'],
                department: TicketDepartment::from($validated['ticketDepartment']),
                priority: TicketPriority::from($validated['ticketPriority']),
                attachments: $this->attachments,
            );
        } catch (TicketException $e) {
            Cache::forget($lockKey);
            $this->notify($e->getMessage(), 'error');

            return;
        }

        $this->attachments = [];
        $this->dispatch('close-modal', name: 'create-ticket-modal');
        $this->redirectRoute('app.tickets.show', ['ticket' => $ticket]);
    }

    public function confirmDeleteTicket(int $id): void
    {
        $ticket = Ticket::query()->findOrFail($id);
        $this->authorize('delete', $ticket);

        $this->deletingTicketId = $ticket->id;
        $this->dispatch('open-modal', name: 'delete-ticket-modal');
    }

    public function deleteTicket(TicketManager $manager): void
    {
        if (! $this->deletingTicketId) {
            return;
        }

        $ticket = Ticket::query()->findOrFail($this->deletingTicketId);
        $this->authorize('delete', $ticket);

        $manager->delete($ticket);

        $this->deletingTicketId = null;
        unset($this->tickets, $this->stats);
        $this->dispatch('close-modal', name: 'delete-ticket-modal');

        if ($this->getPage() > 1 && $this->tickets->isEmpty()) {
            $this->previousPage();
            unset($this->tickets);
        }

        $this->notify('تیکت و پیوست‌های آن به طور کامل حذف شدند.');
    }

    #[Computed]
    public function deletingTicket(): ?Ticket
    {
        return $this->deletingTicketId
            ? Ticket::query()->find($this->deletingTicketId)
            : null;
    }

    #[Computed]
    public function isAdmin(): bool
    {
        return auth()->user()->isAdmin();
    }

    #[Computed]
    public function departmentOptions(): array
    {
        $options = [
            ['value' => 'all', 'label' => 'همه بخش‌ها', 'icon' => 'ticket'],
        ];

        foreach (TicketDepartment::cases() as $dept) {
            $options[] = [
                'value' => $dept->value,
                'label' => $dept->label(),
                'icon' => 'ticket',
            ];
        }

        return $options;
    }

    #[Computed]
    public function stats(): array
    {
        $base = $this->baseQuery();

        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->whereIn('status', [
                TicketStatus::Open->value,
                TicketStatus::CustomerReply->value,
            ])->count(),
            'answered' => (clone $base)->where('status', TicketStatus::Answered->value)->count(),
            'closed' => (clone $base)->where('status', TicketStatus::Closed->value)->count(),
        ];
    }

    #[Computed]
    public function tickets()
    {
        $allowedDepartments = array_column(TicketDepartment::cases(), 'value');

        return $this->baseQuery()
            ->with(['user:id,name,email,avatar', 'latestMessage'])
            ->withCount('messages')
            ->search($this->search)
            ->when(
                in_array($this->department, $allowedDepartments, true),
                fn (Builder $q) => $q->where('department', $this->department),
            )
            ->when(
                $this->status === 'open',
                fn (Builder $q) => $q->whereIn('status', [TicketStatus::Open->value, TicketStatus::CustomerReply->value]),
            )
            ->when(
                $this->status === 'answered',
                fn (Builder $q) => $q->where('status', TicketStatus::Answered->value),
            )
            ->when(
                $this->status === 'closed',
                fn (Builder $q) => $q->where('status', TicketStatus::Closed->value),
            )
            ->orderByRaw("CASE WHEN status = 'closed' THEN 1 ELSE 0 END ASC")
            ->latest('last_reply_at')
            ->paginate(10);
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->status !== 'all' || $this->department !== 'all';
    }

    private function baseQuery(): Builder
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return Ticket::query();
        }

        return $user->tickets()->getQuery();
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, ['all', 'open', 'answered', 'closed'], true) ? $status : 'all';
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

    <x-tickets.stats
        :total="$statData['total']"
        :pending="$statData['pending']"
        :answered="$statData['answered']"
        :closed="$statData['closed']"
        :is-admin="$this->isAdmin"
    />

    <div class="relative has-aria-expanded:z-40 flex flex-col gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:flex-row sm:flex-wrap sm:items-center">
        <div class="relative min-w-48 flex-1">
            <x-ui.icon name="search"
                       class="pointer-events-none absolute inset-s-3.5 top-1/2 size-4.5 -translate-y-1/2 text-content-muted"/>
            <input type="text"
                   maxlength="100"
                   wire:model.live.debounce.300ms="search"
                   placeholder="{{ $this->isAdmin ? 'جستجو در عنوان، شماره تیکت (#12) یا نام و ایمیل کاربر...' : 'جستجو در عنوان یا شماره تیکت (#12)...' }}"
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
            :options="$this->departmentOptions"
            :selected="$department"
            wire-model="department"
            placeholder="همه بخش‌ها"
            :searchable="false"
        />

        <div class="flex shrink-0 items-center gap-1 rounded-xl border border-line-border bg-surface-base p-1">
            @foreach (['all' => 'همه', 'open' => 'در انتظار پاسخ', 'answered' => 'پاسخ داده شده', 'closed' => 'بسته شده'] as $key => $label)
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

        @if (! $this->isAdmin)
            <x-ui.button type="button" wire:click="openCreate" class="h-9.5 shrink-0 cursor-pointer">
                <x-ui.icon name="plus" class="size-4"/>
                <span>تیکت جدید</span>
            </x-ui.button>
        @endif
    </div>

    <div class="space-y-2.5">
        @forelse ($this->tickets as $ticket)
            <x-tickets.row
                :ticket="$ticket"
                :is-admin="$this->isAdmin"
                wire:key="ticket-{{ $ticket->id }}"
            />
        @empty
            <x-ui.card :padding="false">
                @if ($this->hasFilters)
                    <x-ui.empty-state icon="search"
                                      title="تیکتی با این مشخصات یافت نشد"
                                      description="هیچ تیکتی متناسب با فیلترهای انتخابی شما وجود ندارد.">
                        <x-ui.button type="button" variant="secondary" wire:click="clearFilters" class="cursor-pointer">
                            پاک کردن فیلترها
                        </x-ui.button>
                    </x-ui.empty-state>
                @elseif ($this->isAdmin)
                    <x-ui.empty-state icon="ticket"
                                      title="هنوز تیکتی در سیستم ثبت نشده است"
                                      description="به محض اینکه کاربران تیکت پشتیبانی جدیدی ثبت کنند، در این بخش نمایش داده خواهد شد."/>
                @else
                    <x-ui.empty-state icon="ticket"
                                      title="هنوز تیکتی ثبت نکرده‌اید"
                                      description="هر سوال فنی، پیشنهاد یا ابهامی در مورد ساخت ورکفلوها یا اشتراک دارید، همین حالا تیکت بزنید تا تیم تمشک بررسی کند.">
                        <x-ui.button type="button" wire:click="openCreate" class="cursor-pointer">
                            <x-ui.icon name="plus" class="size-4"/>
                            <span>ارسال اولین تیکت</span>
                        </x-ui.button>
                    </x-ui.empty-state>
                @endif
            </x-ui.card>
        @endforelse
    </div>

    <x-ui.pagination :paginator="$this->tickets" />

    @if (! $this->isAdmin)
        <x-tickets.create-modal
            :selected-department="$ticketDepartment"
            :selected-priority="$ticketPriority"
            :attachments="$attachments"
        />
    @endif

    @if ($this->isAdmin)
        <x-ui.modal name="delete-ticket-modal" title="حذف کامل تیکت" maxWidth="sm">
            <div class="flex items-start gap-3.5">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <x-ui.icon name="trash" class="size-5"/>
                </div>
                <p class="text-xs leading-6 text-content-muted">
                    تیکت شماره
                    <span dir="ltr" class="font-mono font-bold text-content-main">#{{ $this->deletingTicket?->id }}</span>
                    با عنوان
                    <span class="wrap-break-word font-bold text-content-main">«{{ \Illuminate\Support\Str::limit($this->deletingTicket?->subject, 50) }}»</span>
                    و تمام پیام‌ها و تصاویر پیوست آن برای همیشه حذف خواهد شد. آیا مطمئن هستید؟
                </p>
            </div>

            <x-slot:footer>
                <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'delete-ticket-modal')" class="cursor-pointer">
                    انصراف
                </x-ui.button>
                <x-ui.button type="button" wire:click="deleteTicket" wire:loading.attr="disabled" wire:target="deleteTicket" class="cursor-pointer">
                    بله، حذف کن
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>

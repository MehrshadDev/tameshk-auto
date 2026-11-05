@props([
    'ticket',
    'isAdmin' => false,
])

@php
    use App\Tickets\Enums\TicketStatus;
    use App\Workflow\Support\DateTimeHelper;

    $ticketUrl = route('app.tickets.show', $ticket);
    $lastDate = ($ticket->last_reply_at ?? $ticket->created_at ?? now())->copy()->setTimezone('Asia/Tehran');
    $persianDate = DateTimeHelper::toPersianDate($lastDate, 'yyyy/MM/dd HH:mm');

    $iconMeta = match ($ticket->status) {
        TicketStatus::Answered => [
            'icon' => 'headset',
            'box' => 'bg-emerald-500/10 text-emerald-500 ring-1 ring-emerald-500/20',
        ],
        TicketStatus::CustomerReply => [
            'icon' => 'message-circle',
            'box' => 'bg-amber-500/10 text-amber-500 ring-1 ring-amber-500/20',
        ],
        TicketStatus::Closed => [
            'icon' => 'lock',
            'box' => 'bg-line-border/50 text-content-muted',
        ],
        default => [
            'icon' => 'ticket',
            'box' => 'bg-sky-500/10 text-sky-500 ring-1 ring-sky-500/20',
        ],
    };
@endphp

<div data-ticket-url="{{ $ticketUrl }}"
     @click="if (!$event.target.closest('[data-no-row-nav]')) window.location.href = $el.dataset.ticketUrl"
     class="group relative flex cursor-pointer items-center gap-3 rounded-2xl border border-line-border bg-surface-card p-3.5 shadow-2xs transition-all duration-150 hover:border-primary/40 hover:shadow-md select-none sm:gap-4 sm:p-4">

    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl transition-transform group-hover:scale-105 {{ $iconMeta['box'] }}">
        <x-ui.icon :name="$iconMeta['icon']" class="size-5.5"/>
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $ticketUrl }}"
               data-no-row-nav
               dir="auto"
               class="truncate text-start text-sm font-bold text-content-main transition-colors group-hover:text-primary focus:outline-none">
                {{ $ticket->subject }}
            </a>

            <span dir="ltr" class="rounded-md border border-line-border/80 bg-surface-raised px-1.5 py-0.5 font-mono text-[10px] font-bold text-content-muted">
                #{{ $ticket->id }}
            </span>

            <span class="hidden rounded-lg border border-line-border/70 bg-surface-base px-2 py-0.5 text-[10px] font-medium text-content-muted sm:inline-block">
                {{ $ticket->department->label() }}
            </span>
        </div>
    </div>

    <div class="hidden shrink-0 items-center gap-3 md:flex">
        <x-ui.badge :variant="$ticket->priority->badgeVariant()">
            {{ $ticket->priority->label() }}
        </x-ui.badge>

        <div class="w-32 text-end">
            <p class="font-mono text-[11px] font-semibold text-content-main" dir="ltr">{{ $persianDate }}</p>
            <p class="mt-0.5 text-[10px] text-content-muted">
                {{ $ticket->messages_count ?? 1 }} پیام • {{ ($ticket->last_reply_at ?? $ticket->created_at)?->diffForHumans() }}
            </p>
        </div>
    </div>

    <div data-no-row-nav class="flex shrink-0 items-center gap-2" @click.stop>
        <x-ui.badge :variant="$ticket->status->badgeVariant()" dot>
            {{ $ticket->status->label() }}
        </x-ui.badge>

        @if ($isAdmin)
            <button type="button"
                    wire:click="confirmDeleteTicket({{ $ticket->id }})"
                    title="حذف کامل تیکت"
                    aria-label="حذف تیکت"
                    class="flex size-8.5 items-center justify-center rounded-xl border border-line-border bg-surface-raised text-content-muted transition hover:border-primary/40 hover:bg-primary/10 hover:text-primary cursor-pointer">
                <x-ui.icon name="trash" class="size-4"/>
            </button>
        @else
            <span class="hidden size-8.5 items-center justify-center rounded-xl border border-line-border bg-surface-raised text-content-muted transition group-hover:border-primary/40 group-hover:text-primary sm:flex">
                <x-ui.icon name="chevron-left" class="size-4"/>
            </span>
        @endif
    </div>
</div>

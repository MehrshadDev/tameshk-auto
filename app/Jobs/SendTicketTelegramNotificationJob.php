<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Tickets\TelegramTicketNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendTicketTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    public function __construct(
        public readonly int $ticketId,
    ) {}

    public static function dispatchSmart(int $ticketId): void
    {
        self::dispatchAfterResponse($ticketId);
    }

    public function handle(TelegramTicketNotifier $notifier): void
    {
        $ticket = Ticket::query()->with(['user', 'latestMessage'])->find($this->ticketId);

        if (! $ticket) {
            return;
        }

        try {
            $notifier->sendNewTicketAlert($ticket);
        } catch (Throwable $e) {
            report($e);
        }
    }
}

<?php

namespace App\Jobs;

use App\Mail\TicketRepliedMail;
use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTicketReplyEmailJob implements ShouldQueue
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

    public function handle(): void
    {
        $ticket = Ticket::query()->with('user')->find($this->ticketId);

        if (! $ticket || ! $ticket->user || empty($ticket->user->email)) {
            return;
        }

        try {
            Mail::to($ticket->user->email)->send(new TicketRepliedMail($ticket));
        } catch (Throwable $e) {
            report($e);
        }
    }
}

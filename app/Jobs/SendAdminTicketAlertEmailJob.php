<?php

namespace App\Jobs;

use App\Mail\AdminTicketEscalationMail;
use App\Models\Ticket;
use App\Subscriptions\SubscriptionManager;
use App\Tickets\Enums\TicketDepartment;
use App\Tickets\Enums\TicketPriority;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAdminTicketAlertEmailJob implements ShouldQueue
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

    public function handle(SubscriptionManager $subscriptions): void
    {
        $adminEmails = array_values(array_filter((array) config('tickets.admin_emails', [])));

        if ($adminEmails === []) {
            return;
        }

        $ticket = Ticket::query()->with('user')->find($this->ticketId);

        if (! $ticket || ! $ticket->user) {
            return;
        }

        $plan = $subscriptions->resolvePlan($ticket->user);
        $isPaidPlan = ! $plan->isFree();
        $isUrgentBilling = $ticket->department === TicketDepartment::Billing
            && $ticket->priority === TicketPriority::High;

        if (! $isPaidPlan && ! $isUrgentBilling) {
            return;
        }

        try {
            Mail::to($adminEmails)->send(
                new AdminTicketEscalationMail(
                    ticket: $ticket,
                    planName: $plan->name,
                    isUrgentBilling: $isUrgentBilling,
                )
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}

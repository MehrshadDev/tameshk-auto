<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminTicketEscalationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $planName,
        public readonly bool $isUrgentBilling,
    ) {}

    public function envelope(): Envelope
    {
        $prefix = $this->isUrgentBilling ? '🚨 تیکت فوری مالی' : "⭐️ تیکت ویژه ({$this->planName})";

        return new Envelope(
            subject: "{$prefix} شماره #{$this->ticket->id} - تمشک",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-ticket-alert',
        );
    }
}

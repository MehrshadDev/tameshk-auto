<?php

namespace App\Tickets\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Answered = 'answered';
    case CustomerReply = 'customer_reply';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'باز',
            self::Answered => 'پاسخ داده شده',
            self::CustomerReply => 'پاسخ کاربر',
            self::Closed => 'بسته شده',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::Answered => 'success',
            self::CustomerReply => 'warning',
            self::Closed => 'neutral',
        };
    }
}

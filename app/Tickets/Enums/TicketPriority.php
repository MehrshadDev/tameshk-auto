<?php

namespace App\Tickets\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'کم',
            self::Medium => 'متوسط',
            self::High => 'فوری',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Low => 'neutral',
            self::Medium => 'warning',
            self::High => 'danger',
        };
    }
}

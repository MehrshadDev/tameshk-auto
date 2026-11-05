<?php

namespace App\Tickets\Enums;

enum TicketDepartment: string
{
    case Technical = 'technical';
    case Billing = 'billing';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Technical => 'فنی و ورکفلوها',
            self::Billing => 'مالی و اشتراک',
            self::General => 'عمومی و پیشنهادات',
        };
    }
}

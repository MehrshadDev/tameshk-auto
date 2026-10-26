<?php

namespace App\Workflow\Enums;

enum NodeCategory: string
{
    case Trigger   = 'trigger';
    case Ai        = 'ai';
    case Action    = 'action';
    case Logic     = 'logic';
    case Transform = 'transform';

    public function label(): string
    {
        return match ($this) {
            self::Trigger   => 'شروع‌کننده',
            self::Ai        => 'هوش مصنوعی',
            self::Action    => 'عملیات',
            self::Logic     => 'منطق',
            self::Transform => 'تغییر داده',
        };
    }
}

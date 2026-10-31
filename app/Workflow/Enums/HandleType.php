<?php

namespace App\Workflow\Enums;

enum HandleType: string
{
    case Main = 'main';
    case True = 'true';
    case False = 'false';
    case Case1 = 'case_1';
    case Case2 = 'case_2';
    case Case3 = 'case_3';
    case Default = 'default';
    case Stopped = 'stopped';
    case ChatModel = 'chat_model';
    case Memory = 'memory';
    case Tool = 'tool';

    public static function special(): array
    {
        return [self::ChatModel->value, self::Memory->value, self::Tool->value];
    }

    public static function flow(): array
    {
        return [
            self::Main->value,
            self::True->value,
            self::False->value,
            self::Case1->value,
            self::Case2->value,
            self::Case3->value,
            self::Default->value,
        ];
    }

    public static function isSpecial(string $handle): bool
    {
        return in_array($handle, self::special(), true);
    }
}

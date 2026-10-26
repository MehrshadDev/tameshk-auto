<?php

namespace App\Workflow\Enums;

enum HandleType: string
{
    case Main = 'main';
    case True = 'true';
    case False = 'false';
    case ChatModel = 'chat_model';
    case Memory = 'memory';
    case Tool = 'tool';

    /** @return array<int, string> */
    public static function special(): array
    {
        return [self::ChatModel->value, self::Memory->value, self::Tool->value];
    }

    /** @return array<int, string> */
    public static function flow(): array
    {
        return [self::Main->value, self::True->value, self::False->value];
    }

    public static function isSpecial(string $handle): bool
    {
        return in_array($handle, self::special(), true);
    }
}

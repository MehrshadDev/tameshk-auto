<?php

namespace App\Workflow\Support;

final class ChatReply
{
    private const KEYS = ['response', 'text', 'output', 'message'];

    public static function extract(mixed $output): string
    {
        if (is_string($output)) {
            return $output;
        }

        if (! is_array($output)) {
            return '';
        }

        foreach (self::KEYS as $key) {
            $value = $output[$key] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '';
    }
}

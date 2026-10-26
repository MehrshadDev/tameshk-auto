<?php

namespace App\Workflow\Support;

final class TemplateEngine
{
    public static function render(mixed $value, array $context): mixed
    {
        if (is_string($value)) {
            return self::interpolateString($value, $context);
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = self::render($v, $context);
            }
            return $result;
        }

        return $value;
    }

    private static function interpolateString(string $template, array $context): string
    {
        return (string) preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', function ($matches) use ($context) {
            $key = $matches[1];
            $val = data_get($context, $key);

            if (is_array($val) || is_object($val)) {
                return json_encode($val, JSON_UNESCAPED_UNICODE);
            }

            return (string) ($val ?? '');
        }, $template);
    }
}

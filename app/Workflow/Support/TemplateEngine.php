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
        return (string) preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function ($matches) use ($context) {
            $rawExpr = trim($matches[1]);

            if (preg_match('/^\$\([\'"](.+?)[\'"]\)(?:\.item)?(?:\.json)?(?:\.(.+))?$/', $rawExpr, $nodeMatch)) {
                $nodeName = $nodeMatch[1];
                $propPath = $nodeMatch[2] ?? '';

                $nodeData = data_get($context, "nodes.{$nodeName}")
                    ?? data_get($context, "nodes." . str_replace(' ', '_', $nodeName))
                    ?? data_get($context, $nodeName)
                    ?? [];

                $val = $propPath !== '' ? data_get($nodeData, $propPath) : $nodeData;
                return self::formatValue($val);
            }

            if (str_starts_with($rawExpr, 'nodes.')) {
                $sub = substr($rawExpr, 6);
                $val = data_get($context, "nodes.{$sub}");
                if ($val !== null) {
                    return self::formatValue($val);
                }
            }

            if (str_starts_with($rawExpr, 'trigger.')) {
                $sub = substr($rawExpr, 8);
                $val = data_get($context, "trigger.{$sub}");
                if ($val !== null) {
                    return self::formatValue($val);
                }
            }

            $cleanKey = preg_replace('/^input\./', '', $rawExpr);
            $val = data_get($context, "input.{$cleanKey}");

            if ($val === null) {
                $val = data_get($context, $rawExpr);
            }

            if ($val === null) {
                $val = data_get($context, "trigger.{$cleanKey}");
            }

            if ($val === null && isset($context['nodes']) && is_array($context['nodes'])) {
                foreach ($context['nodes'] as $nodeOutput) {
                    if (is_array($nodeOutput) && array_key_exists($cleanKey, $nodeOutput)) {
                        $val = $nodeOutput[$cleanKey];
                        break;
                    }
                }
            }

            return self::formatValue($val);
        }, $template);
    }

    private static function formatValue(mixed $val): string
    {
        if (is_array($val) || is_object($val)) {
            return json_encode($val, JSON_UNESCAPED_UNICODE);
        }

        return (string) ($val ?? '');
    }
}

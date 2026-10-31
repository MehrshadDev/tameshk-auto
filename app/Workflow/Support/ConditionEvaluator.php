<?php

namespace App\Workflow\Support;

final class ConditionEvaluator
{
    public const OPERATORS = [
        'eq',
        'neq',
        'gt',
        'gte',
        'lt',
        'lte',
        'contains',
        'not_contains',
        'starts_with',
        'ends_with',
        'empty',
        'not_empty',
        'regex',
    ];

    public static function evaluateRules(array $conditions, string $combinator, mixed $contextData): bool
    {
        $results = [];

        foreach ($conditions as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $rawField = trim((string) ($rule['field'] ?? ''));
            $operator = (string) ($rule['operator'] ?? 'eq');
            $expected = $rule['value'] ?? null;

            $actual = self::resolveValue($rawField, $contextData);
            $results[] = self::compare($actual, $operator, $expected);
        }

        if ($results === []) {
            return true;
        }

        if ($combinator === 'or') {
            return in_array(true, $results, true);
        }

        return ! in_array(false, $results, true);
    }

    public static function resolveValue(string $rawField, mixed $contextData): mixed
    {
        if ($rawField === '') {
            return null;
        }

        if (! is_array($contextData) && ! is_object($contextData)) {
            return $rawField;
        }

        $cleanPath = preg_replace('/^\{\{\s*(?:input\.)?|\s*\}\}$/', '', $rawField);
        $cleanPath = preg_replace('/^input\./', '', (string) $cleanPath);

        $actual = data_get($contextData, $cleanPath);

        if ($actual === null && data_get($contextData, $rawField) !== null) {
            $actual = data_get($contextData, $rawField);
        }

        if ($actual === null && ! str_contains($rawField, '{{')) {
            return $rawField;
        }

        return $actual;
    }

    public static function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        if (is_numeric($actual) && is_numeric($expected) && in_array($operator, ['eq', 'neq', 'gt', 'gte', 'lt', 'lte'], true)) {
            $actualNum = (float) $actual;
            $expectedNum = (float) $expected;

            return match ($operator) {
                'eq'  => $actualNum == $expectedNum,
                'neq' => $actualNum != $expectedNum,
                'gt'  => $actualNum > $expectedNum,
                'gte' => $actualNum >= $expectedNum,
                'lt'  => $actualNum < $expectedNum,
                'lte' => $actualNum <= $expectedNum,
            };
        }

        $actualStr = is_array($actual) || is_object($actual)
            ? (string) json_encode($actual, JSON_UNESCAPED_UNICODE)
            : trim((string) ($actual ?? ''));

        $expectedStr = is_array($expected) || is_object($expected)
            ? (string) json_encode($expected, JSON_UNESCAPED_UNICODE)
            : trim((string) ($expected ?? ''));

        return match ($operator) {
            'eq'           => $actualStr === $expectedStr,
            'neq'          => $actualStr !== $expectedStr,
            'gt'           => strcmp($actualStr, $expectedStr) > 0,
            'gte'          => strcmp($actualStr, $expectedStr) >= 0,
            'lt'           => strcmp($actualStr, $expectedStr) < 0,
            'lte'          => strcmp($actualStr, $expectedStr) <= 0,
            'contains'     => $expectedStr !== '' && mb_stripos($actualStr, $expectedStr) !== false,
            'not_contains' => $expectedStr === '' || mb_stripos($actualStr, $expectedStr) === false,
            'starts_with'  => $expectedStr !== '' && str_starts_with(mb_strtolower($actualStr), mb_strtolower($expectedStr)),
            'ends_with'    => $expectedStr !== '' && str_ends_with(mb_strtolower($actualStr), mb_strtolower($expectedStr)),
            'empty'        => self::isEmpty($actual),
            'not_empty'    => ! self::isEmpty($actual),
            'regex'        => self::matchesRegex($actualStr, $expectedStr),
            default        => false,
        };
    }

    private static function isEmpty(mixed $val): bool
    {
        if ($val === null || $val === false || $val === []) {
            return true;
        }

        if (is_string($val)) {
            return trim($val) === '';
        }

        return false;
    }

    private static function matchesRegex(string $subject, string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        $delimited = preg_match('/^[\/#~].*[\/#~][imsxADSUXJu]*$/', $pattern)
            ? $pattern
            : '/' . str_replace('/', '\/', $pattern) . '/u';

        return @preg_match($delimited, $subject) === 1;
    }
}

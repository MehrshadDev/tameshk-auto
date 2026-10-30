<?php

namespace App\Workflow\Support;

use App\Workflow\Exceptions\NodeExecutionException;

final class MathEvaluator
{
    private const OPERATORS = ['+', '-', '*', '/', '^', '%'];
    private const DELIMITERS = ['(', ')', ','];
    private const PRECEDENCE = ['+' => 1, '-' => 1, '*' => 2, '/' => 2, '%' => 2, '^' => 3];

    public static function evaluate(string $expression): float|int
    {
        $clean = preg_replace('/\s+/', '', strtolower($expression));

        if ($clean === '' || ! preg_match('/^[0-9+\-*\/^%().,a-z]+$/', $clean)) {
            throw NodeExecutionException::because('عبارت ریاضی نامعتبر است.');
        }

        $tokens = self::tokenize($clean);
        $rpn = self::shuntingYard($tokens);

        return self::calculateRpn($rpn);
    }

    private static function tokenize(string $expr): array
    {
        $tokens = [];
        $len = strlen($expr);
        $i = 0;

        while ($i < $len) {
            $ch = $expr[$i];

            if (is_numeric($ch) || $ch === '.') {
                $tokens[] = [
                    'type'  => 'number',
                    'value' => (float) self::consume($expr, $i, fn ($c) => is_numeric($c) || $c === '.'),
                ];
                continue;
            }

            if (ctype_alpha($ch)) {
                $tokens[] = ['type' => 'func', 'value' => self::consume($expr, $i, 'ctype_alpha')];
                continue;
            }

            if (in_array($ch, [...self::OPERATORS, ...self::DELIMITERS], true)) {
                $previous = $tokens[count($tokens) - 1]['value'] ?? null;

                if ($ch === '-' && ($previous === null || in_array($previous, [...self::OPERATORS, '('], true))) {
                    $tokens[] = ['type' => 'number', 'value' => 0];
                    $tokens[] = ['type' => 'op', 'value' => '-'];
                } else {
                    $tokens[] = ['type' => in_array($ch, self::DELIMITERS, true) ? $ch : 'op', 'value' => $ch];
                }

                $i++;
                continue;
            }

            throw NodeExecutionException::because("کاراکتر نامعتبر: $ch");
        }

        return $tokens;
    }

    private static function consume(string $expr, int &$i, callable $accepts): string
    {
        $start = $i;
        $len = strlen($expr);

        while ($i < $len && $accepts($expr[$i])) {
            $i++;
        }

        return substr($expr, $start, $i - $start);
    }

    private static function shuntingYard(array $tokens): array
    {
        $output = [];
        $ops = [];

        foreach ($tokens as $token) {
            if ($token['type'] === 'number') {
                $output[] = $token;
            } elseif ($token['type'] === 'func' || $token['type'] === '(') {
                $ops[] = $token;
            } elseif ($token['type'] === ',') {
                self::flushToParen($ops, $output);
            } elseif ($token['type'] === 'op') {
                while (
                    ! empty($ops)
                    && end($ops)['type'] === 'op'
                    && self::takesPrecedence(end($ops)['value'], $token['value'])
                ) {
                    $output[] = array_pop($ops);
                }

                $ops[] = $token;
            } elseif ($token['type'] === ')') {
                self::flushToParen($ops, $output);
                array_pop($ops);

                if (! empty($ops) && end($ops)['type'] === 'func') {
                    $output[] = array_pop($ops);
                }
            }
        }

        while (! empty($ops)) {
            $output[] = array_pop($ops);
        }

        return $output;
    }

    private static function flushToParen(array &$ops, array &$output): void
    {
        while (! empty($ops) && end($ops)['value'] !== '(') {
            $output[] = array_pop($ops);
        }
    }

    private static function takesPrecedence(string $top, string $incoming): bool
    {
        $a = self::PRECEDENCE[$top];
        $b = self::PRECEDENCE[$incoming];

        return $a > $b || ($a === $b && $incoming !== '^');
    }

    private static function calculateRpn(array $rpn): float|int
    {
        $stack = [];

        foreach ($rpn as $token) {
            if ($token['type'] === 'number') {
                $stack[] = $token['value'];
            } elseif ($token['type'] === 'op') {
                if (count($stack) < 2) {
                    throw NodeExecutionException::because('ساختار عبارت ریاضی ناقص است.');
                }
                $b = array_pop($stack);
                $a = array_pop($stack);

                $stack[] = match ($token['value']) {
                    '+' => $a + $b,
                    '-' => $a - $b,
                    '*' => $a * $b,
                    '/' => $b == 0 ? throw NodeExecutionException::because('تقسیم بر صفر امکان‌پذیر نیست.') : $a / $b,
                    '%' => $b == 0 ? throw NodeExecutionException::because('باقیمانده تقسیم بر صفر تعریف نشده است.') : fmod($a, $b),
                    '^' => pow($a, $b),
                    default => throw NodeExecutionException::because('عملگر ناشناخته.'),
                };
            } elseif ($token['type'] === 'func') {
                if (empty($stack)) {
                    throw NodeExecutionException::because('ورودی تابع ریاضی ناقص است.');
                }
                $arg = array_pop($stack);
                $stack[] = match ($token['value']) {
                    'sqrt' => sqrt($arg),
                    'abs' => abs($arg),
                    'round' => round($arg),
                    'floor' => floor($arg),
                    'ceil' => ceil($arg),
                    'sin' => sin($arg),
                    'cos' => cos($arg),
                    default => throw NodeExecutionException::because("تابع ریاضی پشتیبانی نمی‌شود: {$token['value']}"),
                };
            }
        }

        if (count($stack) !== 1) {
            throw NodeExecutionException::because('محاسبه عبارت ریاضی با خطا مواجه شد.');
        }

        $res = $stack[0];

        return floor($res) == $res ? (int) $res : $res;
    }
}

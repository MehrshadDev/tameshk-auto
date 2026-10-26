<?php

namespace App\Workflow\Support;

use App\Workflow\Exceptions\NodeExecutionException;

final class MathEvaluator
{
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
                $num = '';
                while ($i < $len && (is_numeric($expr[$i]) || $expr[$i] === '.')) {
                    $num .= $expr[$i];
                    $i++;
                }
                $tokens[] = ['type' => 'number', 'value' => (float) $num];
                continue;
            }

            if (ctype_alpha($ch)) {
                $func = '';
                while ($i < $len && ctype_alpha($expr[$i])) {
                    $func .= $expr[$i];
                    $i++;
                }
                $tokens[] = ['type' => 'func', 'value' => $func];
                continue;
            }

            if (in_array($ch, ['+', '-', '*', '/', '^', '%', '(', ')', ','], true)) {
                if ($ch === '-' && ($i === 0 || in_array($tokens[count($tokens) - 1]['value'] ?? '', ['+', '-', '*', '/', '^', '%', '('], true))) {
                    $tokens[] = ['type' => 'number', 'value' => 0];
                    $tokens[] = ['type' => 'op', 'value' => '-'];
                } else {
                    $tokens[] = ['type' => in_array($ch, ['(', ')', ','], true) ? $ch : 'op', 'value' => $ch];
                }
                $i++;
                continue;
            }

            throw NodeExecutionException::because("کاراکتر نامعتبر: $ch");
        }

        return $tokens;
    }

    private static function shuntingYard(array $tokens): array
    {
        $output = [];
        $ops = [];
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2, '%' => 2, '^' => 3];

        foreach ($tokens as $token) {
            if ($token['type'] === 'number') {
                $output[] = $token;
            } elseif ($token['type'] === 'func') {
                $ops[] = $token;
            } elseif ($token['type'] === ',') {
                while (! empty($ops) && end($ops)['value'] !== '(') {
                    $output[] = array_pop($ops);
                }
            } elseif ($token['type'] === 'op') {
                while (! empty($ops) && end($ops)['type'] === 'op' &&
                    (($precedence[end($ops)['value']] > $precedence[$token['value']]) ||
                        ($precedence[end($ops)['value']] === $precedence[$token['value']] && $token['value'] !== '^'))
                ) {
                    $output[] = array_pop($ops);
                }
                $ops[] = $token;
            } elseif ($token['type'] === '(') {
                $ops[] = $token;
            } elseif ($token['type'] === ')') {
                while (! empty($ops) && end($ops)['value'] !== '(') {
                    $output[] = array_pop($ops);
                }
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

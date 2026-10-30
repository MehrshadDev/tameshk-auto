<?php

namespace App\Workflow\Nodes\Transform;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;

class EditFieldsNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'transform.edit_fields',
            name: 'Edit Fields (Set)',
            category: NodeCategory::Transform,
            description: 'تعریف، ویرایش، تغییر نام یا ترکیب فیلدهای داده برای ارسال به نودهای بعدی.',
            icon: 'pencil',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'mode' => [
                    'type'     => 'select',
                    'label'    => 'حالت (Mode)',
                    'options'  => [
                        ['value' => 'manual', 'label' => 'نگاشت دستی (Manual Mapping)'],
                        ['value' => 'json', 'label' => 'JSON'],
                    ],
                    'default'  => 'manual',
                    'required' => true,
                ],
                'fields' => [
                    'type'       => 'set_fields',
                    'label'      => 'فیلدها (Fields to Set)',
                    'default'    => [],
                    'depends_on' => ['mode' => 'manual'],
                ],
                'json_output' => [
                    'type'        => 'code',
                    'label'       => 'JSON',
                    'default'     => "{\n  \"my_field_1\": \"value\",\n  \"my_field_2\": 1\n}",
                    'placeholder' => "{\n  \"my_field_1\": \"value\",\n  \"my_field_2\": 1\n}",
                    'depends_on'  => ['mode' => 'json'],
                ],
                'include_other_fields' => [
                    'type'    => 'boolean',
                    'label'   => 'نگه‌داشتن سایر فیلدهای ورودی (Include Other Input Fields)',
                    'default' => false,
                ],
                'support_dot_notation' => [
                    'type'     => 'boolean',
                    'label'    => 'پشتیبانی از نقطه‌گذاری (Support Dot Notation)',
                    'default'  => true,
                    'advanced' => true,
                    'help'     => 'در صورت فعال بودن، نام‌هایی مانند user.name به صورت آبجکت تودرتو ساخته می‌شوند.',
                ],
            ],
            color: 'sky',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $mode = (string) ($context->config['mode'] ?? 'manual');
        $keepInput = filter_var($context->config['include_other_fields'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dotNotation = filter_var($context->config['support_dot_notation'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $base = ($keepInput && is_array($context->input)) ? $context->input : [];

        if ($mode === 'json') {
            $rawJson = trim((string) ($context->config['json_output'] ?? ''));

            if ($rawJson === '') {
                return NodeResult::of($base);
            }

            $decoded = json_decode($rawJson, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw NodeExecutionException::because('ساختار JSON واردشده در نود Edit Fields معتبر نیست.');
            }

            $output = $base;

            foreach ($decoded as $key => $val) {
                if ($dotNotation && is_string($key) && str_contains($key, '.')) {
                    data_set($output, $key, $val);
                } else {
                    $output[$key] = $val;
                }
            }

            return NodeResult::of($output);
        }

        $fields = $context->config['fields'] ?? [];
        if (! is_array($fields)) {
            $fields = [];
        }

        $output = $base;

        if (array_is_list($fields)) {
            foreach ($fields as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $cleanKey = trim((string) ($item['name'] ?? ''));
                if ($cleanKey === '') {
                    continue;
                }

                $type = (string) ($item['type'] ?? 'string');
                $rawValue = $item['value'] ?? '';
                $casted = $this->castByType($rawValue, $type, $cleanKey);

                if ($dotNotation) {
                    data_set($output, $cleanKey, $casted);
                } else {
                    $output[$cleanKey] = $casted;
                }
            }
        } else {
            foreach ($fields as $key => $rawValue) {
                $cleanKey = trim((string) $key);
                if ($cleanKey === '') {
                    continue;
                }

                $casted = $this->castSmartValue($rawValue);

                if ($dotNotation) {
                    data_set($output, $cleanKey, $casted);
                } else {
                    $output[$cleanKey] = $casted;
                }
            }
        }

        return NodeResult::of($output);
    }

    private function castByType(mixed $value, string $type, string $fieldName): mixed
    {
        return match ($type) {
            'string' => is_array($value) || is_object($value)
                ? json_encode($value, JSON_UNESCAPED_UNICODE)
                : (string) ($value ?? ''),

            'number' => (function () use ($value, $fieldName) {
                if (is_int($value) || is_float($value)) {
                    return $value;
                }
                $trimmed = trim((string) $value);
                if ($trimmed === '') {
                    return 0;
                }
                if (! is_numeric($trimmed)) {
                    throw NodeExecutionException::because("مقدار فیلد «{$fieldName}» یک عدد معتبر نیست.");
                }
                return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
            })(),

            'boolean' => (function () use ($value) {
                if (is_bool($value)) {
                    return $value;
                }
                $trimmed = strtolower(trim((string) $value));
                return in_array($trimmed, ['true', '1', 'yes', 'on'], true);
            })(),

            'array' => (function () use ($value, $fieldName) {
                if (is_array($value)) {
                    return array_values($value);
                }
                $trimmed = trim((string) $value);
                if ($trimmed === '') {
                    return [];
                }
                $decoded = json_decode($trimmed, true);
                if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                    throw NodeExecutionException::because("مقدار فیلد «{$fieldName}» یک آرایه JSON معتبر نیست.");
                }
                return array_values($decoded);
            })(),

            'object' => (function () use ($value, $fieldName) {
                if (is_array($value)) {
                    return $value;
                }
                $trimmed = trim((string) $value);
                if ($trimmed === '') {
                    return (object) [];
                }
                $decoded = json_decode($trimmed, true);
                if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                    throw NodeExecutionException::because("مقدار فیلد «{$fieldName}» یک آبجکت JSON معتبر نیست.");
                }
                return $decoded;
            })(),

            default => $value,
        };
    }

    private function castSmartValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        if ($trimmed === 'true') {
            return true;
        }

        if ($trimmed === 'false') {
            return false;
        }

        if ($trimmed === 'null') {
            return null;
        }

        if (is_numeric($trimmed) && ! (strlen($trimmed) > 1 && str_starts_with($trimmed, '0') && ! str_starts_with($trimmed, '0.'))) {
            return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
        }

        if (
            (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) ||
            (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'))
        ) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }
}

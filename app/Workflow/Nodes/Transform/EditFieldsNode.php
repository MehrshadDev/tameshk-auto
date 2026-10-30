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
        $keepInput = filter_var($context->config['include_other_fields'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dotNotation = filter_var($context->config['support_dot_notation'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $output = ($keepInput && is_array($context->input)) ? $context->input : [];

        $values = ($context->config['mode'] ?? 'manual') === 'json'
            ? $this->jsonValues($context)
            : $this->manualValues($context);

        foreach ($values as $key => $value) {
            if ($dotNotation) {
                data_set($output, $key, $value);
            } else {
                $output[$key] = $value;
            }
        }

        return NodeResult::of($output);
    }

    private function jsonValues(NodeContext $context): array
    {
        $raw = trim((string) ($context->config['json_output'] ?? ''));

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw NodeExecutionException::because('ساختار JSON واردشده در نود Edit Fields معتبر نیست.');
        }

        return $decoded;
    }

    private function manualValues(NodeContext $context): array
    {
        $fields = $context->config['fields'] ?? [];

        if (! is_array($fields)) {
            return [];
        }

        $values = [];

        if (array_is_list($fields)) {
            foreach ($fields as $item) {
                $name = is_array($item) ? trim((string) ($item['name'] ?? '')) : '';

                if ($name !== '') {
                    $values[$name] = $this->castByType($item['value'] ?? '', (string) ($item['type'] ?? 'string'), $name);
                }
            }

            return $values;
        }

        foreach ($fields as $key => $raw) {
            $name = trim((string) $key);

            if ($name !== '') {
                $values[$name] = $this->castSmartValue($raw);
            }
        }

        return $values;
    }

    private function castByType(mixed $value, string $type, string $field): mixed
    {
        return match ($type) {
            'string'  => is_array($value) || is_object($value)
                ? json_encode($value, JSON_UNESCAPED_UNICODE)
                : (string) ($value ?? ''),
            'number'  => $this->castNumber($value, $field),
            'boolean' => is_bool($value)
                ? $value
                : in_array(strtolower(trim((string) $value)), ['true', '1', 'yes', 'on'], true),
            'array'   => array_values($this->decodeJson($value, $field, 'آرایه', [])),
            'object'  => $this->decodeJson($value, $field, 'آبجکت', (object) []),
            default   => $value,
        };
    }

    private function castNumber(mixed $value, string $field): int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return 0;
        }

        if (! is_numeric($trimmed)) {
            throw NodeExecutionException::because("مقدار فیلد «{$field}» یک عدد معتبر نیست.");
        }

        return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
    }

    private function decodeJson(mixed $value, string $field, string $label, mixed $empty): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return $empty;
        }

        $decoded = json_decode($trimmed, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw NodeExecutionException::because("مقدار فیلد «{$field}» یک $label JSON معتبر نیست.");
        }

        return $decoded;
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

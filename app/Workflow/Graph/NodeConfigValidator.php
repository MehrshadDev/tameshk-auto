<?php

namespace App\Workflow\Graph;

use App\Models\Node;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\Nodes\Triggers\ScheduleTriggerNode;
use App\Workflow\Nodes\Triggers\WebhookTriggerNode;
use App\Workflow\Support\ConditionEvaluator;
use Illuminate\Support\Collection;

final class NodeConfigValidator
{
    private const MAX_TEXT = 10000;
    private const MAX_PAIRS = 50;
    private const MAX_CONDITIONS = 50;

    public function nodeMessages(Node $node, NodeDefinition $definition, Collection $credentials): array
    {
        $config = $node->config ?? [];
        $errors = $this->errors($definition, $config, $credentials);

        if ($node->type === 'trigger.webhook') {
            $pathError = WebhookTriggerNode::pathError($config['path'] ?? null, $node->workflow_id);

            if ($pathError) {
                $errors['path'] = $pathError;
            }
        }

        if ($node->type === 'trigger.schedule') {
            $cronError = ScheduleTriggerNode::cronError($config);

            if ($cronError) {
                $errors['cron_expression'] = $cronError;
            }
        }

        return array_map(
            fn (string $key, string $message) => "نود «{$node->name}» ← فیلد «" . ($definition->configSchema[$key]['label'] ?? $key) . "»: $message",
            array_keys($errors),
            $errors,
        );
    }

    public function errors(NodeDefinition $definition, array $config, Collection $credentials, bool $complete = true): array
    {
        $errors = [];

        foreach ($config as $key => $value) {
            $field = $definition->configSchema[$key] ?? null;

            if ($field === null || $value === null) {
                continue;
            }

            if (! $this->isFieldVisible($field, $config, $definition)) {
                continue;
            }

            if (! $complete && ($value === '' || $value === [])) {
                continue;
            }

            $error = $this->check($field, $value, $credentials, $complete);

            if ($error !== null) {
                $errors[$key] = $error;
            }
        }

        if (! $complete) {
            return $errors;
        }

        foreach ($definition->configSchema as $key => $field) {
            if (! ($field['required'] ?? false)) {
                continue;
            }

            if (! $this->isFieldVisible($field, $config, $definition)) {
                continue;
            }

            $value = $config[$key] ?? $field['default'] ?? null;

            $isEmpty = $value === null
                || $value === []
                || (is_string($value) && trim($value) === '');

            if ($isEmpty) {
                $errors[$key] ??= ($field['type'] ?? null) === 'model_select'
                    ? 'مدل هوش مصنوعی انتخاب نشده است. لطفاً یک مدل را از لیست انتخاب کنید.'
                    : 'این فیلد الزامی است.';
            }
        }

        return $errors;
    }

    private function isFieldVisible(array $field, array $config, ?NodeDefinition $definition = null): bool
    {
        if (empty($field['depends_on']) || ! is_array($field['depends_on'])) {
            return true;
        }

        foreach ($field['depends_on'] as $depKey => $depValues) {
            $actual = $config[$depKey] ?? ($definition->configSchema[$depKey]['default'] ?? null);
            $allowed = is_array($depValues) ? $depValues : [$depValues];

            if (! in_array($actual, $allowed, false)) {
                return false;
            }
        }

        return true;
    }

    private function check(array $field, mixed $value, Collection $credentials, bool $complete): ?string
    {
        return match ($field['type'] ?? null) {
            'text', 'textarea', 'password', 'code', 'dynamic_select' => $this->checkText($value),
            'number'       => $this->checkNumber($field, $value, $complete),
            'boolean'      => $this->checkBoolean($value),
            'select'       => $complete ? $this->checkSelect($field, $value) : null,
            'multiselect'  => $complete ? $this->checkMultiSelect($field, $value) : null,
            'keyvalue'     => $this->checkKeyValue($value),
            'set_fields'   => $this->checkSetFields($value),
            'credential'   => $complete ? $this->checkCredential($field, $value, $credentials) : null,
            'model_select' => $this->checkModelSelect($value),
            'conditions'   => $this->checkConditions($value),
            default        => 'نوع تنظیم پشتیبانی نمی‌شود.',
        };
    }

    private function checkBoolean(mixed $value): ?string
    {
        return is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true)
            ? null
            : 'مقدار نامعتبر است.';
    }

    private function checkSetFields(mixed $value): ?string
    {
        if (! is_array($value) || count($value) > self::MAX_PAIRS) {
            return 'فهرست فیلدها نامعتبر است.';
        }

        if ($value !== [] && ! array_is_list($value)) {
            return $this->checkKeyValue($value);
        }

        $allowedTypes = ['string', 'number', 'boolean', 'array', 'object'];

        foreach ($value as $item) {
            if (! is_array($item)) {
                return 'یکی از فیلدها نامعتبر است.';
            }

            $name = (string) ($item['name'] ?? '');
            $type = (string) ($item['type'] ?? 'string');
            $val = $item['value'] ?? '';

            if (mb_strlen($name) > 200) {
                return 'نام یکی از فیلدها بیش از حد طولانی است.';
            }

            if (! in_array($type, $allowedTypes, true)) {
                return 'نوع یکی از فیلدها نامعتبر است.';
            }

            if ($val !== null && ! is_scalar($val)) {
                return 'مقدار یکی از فیلدها نامعتبر است.';
            }

            if (is_string($val) && mb_strlen($val) > 5000) {
                return 'مقدار یکی از فیلدها بیش از حد طولانی است.';
            }
        }

        return null;
    }

    private function checkMultiSelect(array $field, mixed $value): ?string
    {
        $allowed = $this->allowedValues($field);

        if (! is_array($value) || ! array_is_list($value) || count($value) > count($allowed)) {
            return 'گزینه‌های انتخاب‌شده نامعتبر است.';
        }

        foreach ($value as $item) {
            if (! is_string($item) || ! in_array($item, $allowed, true)) {
                return 'گزینه‌های انتخاب‌شده نامعتبر است.';
            }
        }

        return null;
    }

    private function checkNumber(array $field, mixed $value, bool $complete): ?string
    {
        if (! is_int($value) && ! is_float($value)) {
            if (! is_numeric($value)) {
                return 'مقدار باید عدد باشد.';
            }

            $value = $value + 0;
        }

        if (! $complete) {
            return null;
        }

        if (! empty($field['integer']) && ! is_int($value) && $value != (int) $value) {
            return 'مقدار باید عدد صحیح باشد.';
        }

        if (isset($field['min']) && $value < $field['min']) {
            return "مقدار نباید کمتر از {$field['min']} باشد.";
        }

        if (isset($field['max']) && $value > $field['max']) {
            return "مقدار نباید بیشتر از {$field['max']} باشد.";
        }

        return null;
    }

    private function checkText(mixed $value): ?string
    {
        return is_string($value) && mb_strlen($value) <= self::MAX_TEXT
            ? null
            : 'مقدار نامعتبر یا بیش از حد طولانی است.';
    }

    private function checkSelect(array $field, mixed $value): ?string
    {
        $allowed = $this->allowedValues($field);

        return is_string($value) && in_array($value, $allowed, true)
            ? null
            : 'گزینه انتخاب‌شده نامعتبر است.';
    }

    private function checkKeyValue(mixed $value): ?string
    {
        if (! is_array($value) || count($value) > self::MAX_PAIRS || ($value !== [] && array_is_list($value))) {
            return 'فهرست نامعتبر است.';
        }

        foreach ($value as $key => $item) {
            if (
                mb_strlen((string) $key) > 200
                || ($item !== null && (! is_string($item) || mb_strlen($item) > 2000))
            ) {
                return 'یکی از موارد فهرست نامعتبر است.';
            }
        }

        return null;
    }

    private function checkCredential(array $field, mixed $value, Collection $credentials): ?string
    {
        $id = is_numeric($value) ? (int) $value : null;

        return $id !== null && $credentials->get($id) === ($field['credential_type'] ?? null)
            ? null
            : 'اعتبارنامه انتخاب‌شده نامعتبر است.';
    }

    private function checkModelSelect(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '' || mb_strlen($value) > 200) {
            return 'مدل هوش مصنوعی انتخاب نشده است. لطفاً یک مدل را از لیست انتخاب کنید.';
        }

        if (preg_match('/^gemini-(1\.|2\.0)/i', trim($value))) {
            return 'این مدل منسوخ شده است. لطفاً یک مدل جدید از لیست انتخاب کنید.';
        }

        return null;
    }

    private function checkConditions(mixed $value): ?string
    {
        if (! is_array($value) || count($value) > self::MAX_CONDITIONS) {
            return 'فهرست شروط نامعتبر است.';
        }

        foreach ($value as $rule) {
            if (! is_array($rule)) {
                return 'یکی از شروط نامعتبر است.';
            }

            $field = (string) ($rule['field'] ?? '');
            $operator = (string) ($rule['operator'] ?? '');

            if (mb_strlen($field) > 500) {
                return 'یکی از شروط نامعتبر است.';
            }

            if (! in_array($operator, ConditionEvaluator::OPERATORS, true)) {
                return 'عملگر یکی از شروط نامعتبر است.';
            }

            if (! in_array($operator, ['empty', 'not_empty'], true) && isset($rule['value']) && ! is_scalar($rule['value'])) {
                return 'مقدار یکی از شروط نامعتبر است.';
            }
        }

        return null;
    }

    private function allowedValues(array $field): array
    {
        return array_map(
            fn ($option) => is_array($option) ? ($option['value'] ?? null) : $option,
            $field['options'] ?? [],
        );
    }
}

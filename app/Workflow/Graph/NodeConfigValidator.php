<?php

namespace App\Workflow\Graph;

use App\Workflow\DTOs\NodeDefinition;
use Illuminate\Support\Collection;

final class NodeConfigValidator
{
    private const MAX_TEXT = 10000;
    private const MAX_PAIRS = 50;
    private const MAX_CONDITIONS = 50;

    public function errors(NodeDefinition $definition, array $config, Collection $credentials, bool $complete = true): array
    {
        $errors = [];

        foreach ($config as $key => $value) {
            $field = $definition->configSchema[$key] ?? null;

            if ($field === null || $value === null) {
                continue;
            }

            if (! $this->isFieldVisible($field, $config)) {
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

            if (! $this->isFieldVisible($field, $config)) {
                continue;
            }

            $value = $config[$key] ?? $field['default'] ?? null;

            $isEmpty = $value === null
                || $value === []
                || (is_string($value) && trim($value) === '');

            if ($isEmpty) {
                $errors[$key] ??= 'این فیلد الزامی است.';
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
            'number'         => $this->checkNumber($field, $value, $complete),
            'boolean'        => $this->checkBoolean($value),
            'select'         => $complete ? $this->checkSelect($field, $value) : null,
            'multiselect'    => $complete ? $this->checkMultiSelect($field, $value) : null,
            'keyvalue'       => $this->checkKeyValue($value),
            'credential'     => $complete ? $this->checkCredential($field, $value, $credentials) : null,
            'model_select'   => $this->checkModelSelect($value),
            'conditions'     => $this->checkConditions($value),
            default          => 'نوع تنظیم پشتیبانی نمی‌شود.',
        };
    }

    private function checkBoolean(mixed $value): ?string
    {
        return is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true)
            ? null
            : 'مقدار نامعتبر است.';
    }

    private function checkMultiSelect(array $field, mixed $value): ?string
    {
        $allowed = array_map(
            fn ($option) => is_array($option) ? ($option['value'] ?? null) : $option,
            $field['options'] ?? [],
        );

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
        $allowed = array_map(
            fn ($option) => is_array($option) ? ($option['value'] ?? null) : $option,
            $field['options'] ?? [],
        );

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
        return is_string($value) && $value !== '' && mb_strlen($value) <= 200
            ? null
            : 'مدل انتخاب‌شده نامعتبر است.';
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

            if (! in_array($operator, [
                'eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'contains', 'empty',
            ], true)) {
                return 'عملگر یکی از شروط نامعتبر است.';
            }

            if ($operator !== 'empty' && isset($rule['value']) && ! is_scalar($rule['value'])) {
                return 'مقدار یکی از شروط نامعتبر است.';
            }
        }

        return null;
    }
}

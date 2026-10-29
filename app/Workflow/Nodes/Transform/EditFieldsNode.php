<?php

namespace App\Workflow\Nodes\Transform;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;

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
                    'label'    => 'حالت ویرایش',
                    'options'  => [
                        ['value' => 'manual', 'label' => 'تعریف فیلدها (کلید - مقدار)'],
                        ['value' => 'json', 'label' => 'ساختار JSON سفارشی'],
                    ],
                    'default'  => 'manual',
                    'required' => true,
                ],
                'fields' => [
                    'type'       => 'keyvalue',
                    'label'      => 'فیلدها (Fields to Set)',
                    'default'    => ['' => ''],
                    'help'       => 'نام فیلد و مقدار آن را وارد کنید. می‌توانید متغیرهای ورودی را از ستون سمت راست بکشید و رها کنید.',
                    'depends_on' => ['mode' => 'manual'],
                ],
                'json_output' => [
                    'type'        => 'code',
                    'label'       => 'خروجی JSON',
                    'placeholder' => "{\n  \"fullName\": \"{{ input.first_name }} {{ input.last_name }}\",\n  \"status\": \"active\"\n}",
                    'depends_on'  => ['mode' => 'json'],
                ],
                'include_other_fields' => [
                    'type'    => 'select',
                    'label'   => 'نگه‌داشتن سایر فیلدهای ورودی',
                    'options' => [
                        ['value' => '0', 'label' => 'خیر (فقط فیلدهای تعریف‌شده در همین نود خروجی داده شوند)'],
                        ['value' => '1', 'label' => 'بله (فیلدهای جدید به داده‌های ورودی قبلی اضافه شوند)'],
                    ],
                    'default' => '0',
                ],
            ],
            color: 'sky',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $mode = (string) ($context->config['mode'] ?? 'manual');
        $keepInput = ((string) ($context->config['include_other_fields'] ?? '0')) === '1';

        $base = ($keepInput && is_array($context->input)) ? $context->input : [];

        if ($mode === 'json') {
            $rawJson = trim((string) ($context->config['json_output'] ?? ''));
            if ($rawJson !== '') {
                $decoded = json_decode($rawJson, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return NodeResult::of(array_replace_recursive($base, $decoded));
                }
            }

            return NodeResult::of($base);
        }

        $fields = $context->config['fields'] ?? [];
        if (! is_array($fields)) {
            $fields = [];
        }

        $output = $base;

        foreach ($fields as $key => $rawValue) {
            $cleanKey = trim((string) $key);
            if ($cleanKey === '') {
                continue;
            }

            data_set($output, $cleanKey, $this->parseSmartValue($rawValue));
        }

        return NodeResult::of($output);
    }

    private function parseSmartValue(mixed $value): mixed
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

        if (is_numeric($trimmed) && ! str_starts_with($trimmed, '0')) {
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

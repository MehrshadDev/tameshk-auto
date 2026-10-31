<?php

namespace App\Workflow\Nodes\Transform;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\WorkflowDataStore;

class DataStoreNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'transform.datastore',
            name: 'Data Store',
            category: NodeCategory::Transform,
            description: 'ذخیره، بازیابی، شمارش و مدیریت داده‌های کلید-مقدار دائمی بین اجراهای مختلف ورکفلو.',
            icon: 'database',
            inputs: [HandleType::Main->value],
            outputs: [HandleType::Main->value],
            configSchema: array_merge(
                WorkflowDataStore::scopeSchema(),
                [
                    'operation' => [
                        'type'     => 'select',
                        'label'    => 'عملیات',
                        'options'  => [
                            ['value' => 'get', 'label' => 'خواندن مقدار یک کلید (Get Value)'],
                            ['value' => 'set', 'label' => 'ذخیره یا بروزرسانی کلید (Set Value)'],
                            ['value' => 'increment', 'label' => 'افزایش یا کاهش شمارنده عددی (Increment)'],
                            ['value' => 'delete', 'label' => 'حذف یک کلید (Delete Key)'],
                            ['value' => 'list', 'label' => 'دریافت همه کلیدها و مقادیر (Get All)'],
                        ],
                        'default'  => 'get',
                        'required' => true,
                    ],
                    'key' => [
                        'type'        => 'text',
                        'label'       => 'نام کلید (Key)',
                        'placeholder' => 'مثلاً user_{{ input.chat_id }} یا last_order_id',
                        'required'    => true,
                        'depends_on'  => ['operation' => ['get', 'set', 'increment', 'delete']],
                    ],
                    'value' => [
                        'type'        => 'textarea',
                        'label'       => 'مقدار برای ذخیره (Value)',
                        'placeholder' => 'متن، عدد یا JSON',
                        'required'    => true,
                        'depends_on'  => ['operation' => 'set'],
                    ],
                    'default_value' => [
                        'type'        => 'text',
                        'label'       => 'مقدار پیش‌فرض (در صورت نبودن کلید)',
                        'default'     => '',
                        'depends_on'  => ['operation' => 'get'],
                    ],
                    'step' => [
                        'type'       => 'number',
                        'label'      => 'مقدار گام افزایش/کاهش',
                        'default'    => 1,
                        'required'   => true,
                        'depends_on' => ['operation' => 'increment'],
                    ],
                ],
            ),
            color: 'violet',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $storeKey = WorkflowDataStore::resolveScope($context);
        $operation = (string) ($context->config['operation'] ?? 'get');
        $scope = (string) ($context->config['scope'] ?? 'workflow');

        if ($operation === 'list') {
            $all = WorkflowDataStore::all($storeKey);

            return NodeResult::of([
                'scope' => $scope,
                'count' => count($all),
                'data'  => $all,
            ]);
        }

        $key = trim((string) ($context->config['key'] ?? ''));

        if ($key === '') {
            throw NodeExecutionException::because('نام کلید (Key) در نود Data Store الزامی است.');
        }

        return match ($operation) {
            'get' => (function () use ($storeKey, $key, $context) {
                $fallback = $context->config['default_value'] ?? null;
                $val = WorkflowDataStore::get($storeKey, $key, $fallback);
                $exists = array_key_exists($key, WorkflowDataStore::all($storeKey));

                return NodeResult::of([
                    'key'    => $key,
                    'value'  => $val,
                    'exists' => $exists,
                ]);
            })(),

            'set' => (function () use ($storeKey, $key, $context) {
                $raw = (string) ($context->config['value'] ?? '');
                $decoded = json_decode($raw, true);
                $val = (json_last_error() === JSON_ERROR_NONE && (str_starts_with(trim($raw), '{') || str_starts_with(trim($raw), '[')))
                    ? $decoded
                    : $raw;

                WorkflowDataStore::set($storeKey, $key, $val);

                return NodeResult::of([
                    'success' => true,
                    'key'     => $key,
                    'value'   => $val,
                ]);
            })(),

            'increment' => (function () use ($storeKey, $key, $context) {
                $step = is_numeric($context->config['step'] ?? null) ? ($context->config['step'] + 0) : 1;
                $next = WorkflowDataStore::increment($storeKey, $key, $step);

                return NodeResult::of([
                    'success' => true,
                    'key'     => $key,
                    'value'   => $next,
                ]);
            })(),

            'delete' => (function () use ($storeKey, $key) {
                $deleted = WorkflowDataStore::delete($storeKey, $key);

                return NodeResult::of([
                    'success' => true,
                    'key'     => $key,
                    'deleted' => $deleted,
                ]);
            })(),

            default => throw NodeExecutionException::because("عملیات نامعتبر Data Store: $operation"),
        };
    }
}

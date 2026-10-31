<?php

namespace App\Workflow\Nodes\Ai;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\ToolContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Support\WorkflowDataStore;

class DataStoreToolNode implements NodeContract, ToolContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.datastore',
            name: 'Data Store Tool',
            category: NodeCategory::Ai,
            description: 'ابزار ذخیره و بازیابی دائمی اطلاعات، یادداشت‌ها و متغیرها توسط ایجنت هوش مصنوعی.',
            icon: 'database',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: array_merge(
                [
                    'tool_name' => [
                        'type'        => 'text',
                        'label'       => 'نام ابزار (انگلیسی)',
                        'default'     => 'data_store',
                        'required'    => true,
                        'placeholder' => 'data_store',
                    ],
                    'tool_description' => [
                        'type'     => 'textarea',
                        'label'    => 'توضیحات عملکرد ابزار برای مدل',
                        'default'  => 'ذخیره، بازیابی، شمارش و حذف اطلاعات کلید-مقدار در حافظه دائمی (Data Store).',
                        'required' => true,
                    ],
                ],
                WorkflowDataStore::scopeSchema(),
            ),
            color: 'violet',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'data_store'));
    }

    public function toolDescription(array $config): string
    {
        return trim((string) ($config['tool_description'] ?? 'ذخیره و خواندن داده از مخزن کلید-مقدار'));
    }

    public function toolParameters(array $config): array
    {
        return [
            'type'       => 'OBJECT',
            'properties' => [
                'action' => [
                    'type'        => 'STRING',
                    'description' => 'نوع عملیات: "get" (خواندن یک کلید)، "set" (ذخیره مقدار در یک کلید)، "increment" (افزایش شمارنده)، "delete" (حذف کلید)، "list" (مشاهده تمام کلیدها)',
                ],
                'key' => [
                    'type'        => 'STRING',
                    'description' => 'نام کلید مورد نظر (برای get, set, increment, delete)',
                ],
                'value' => [
                    'type'        => 'STRING',
                    'description' => 'مقدار مورد نظر جهت ذخیره در عملیات set',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function executeTool(NodeContext $context, array $arguments): mixed
    {
        $storeKey = WorkflowDataStore::resolveScope($context);
        $action = strtolower(trim((string) ($arguments['action'] ?? 'list')));
        $key = trim((string) ($arguments['key'] ?? ''));

        if ($action === 'list') {
            $all = WorkflowDataStore::all($storeKey);
            return [
                'count' => count($all),
                'items' => $all,
            ];
        }

        if ($key === '') {
            return ['error' => 'برای این عملیات، ارسال پارامتر key الزامی است.'];
        }

        return match ($action) {
            'get' => [
                'key'   => $key,
                'value' => WorkflowDataStore::get($storeKey, $key, null),
            ],
            'set' => [
                'success' => true,
                'key'     => $key,
                'value'   => WorkflowDataStore::set($storeKey, $key, $arguments['value'] ?? ''),
            ],
            'increment' => [
                'success' => true,
                'key'     => $key,
                'value'   => WorkflowDataStore::increment($storeKey, $key, 1),
            ],
            'delete' => [
                'success' => true,
                'key'     => $key,
                'deleted' => WorkflowDataStore::delete($storeKey, $key),
            ],
            default => ['error' => "عملیات ناشناخته: {$action}. مقادیر مجاز: get, set, increment, delete, list"],
        };
    }
}

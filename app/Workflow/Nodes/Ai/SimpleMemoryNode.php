<?php

namespace App\Workflow\Nodes\Ai;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;

class SimpleMemoryNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.memory.simple',
            name: 'Simple Memory',
            category: NodeCategory::Ai,
            description: 'نگه‌داری تاریخچه پیام‌های اخیر برای حافظه ایجنت هوش مصنوعی.',
            icon: 'history',
            group: 'memory',
            inputs: [],
            outputs: [HandleType::Memory->value],
            configSchema: [
                'memory_size' => [
                    'type' => 'number',
                    'label' => 'تعداد پیام‌های ذخیره‌شده',
                    'default' => 5,
                    'min' => 1,
                    'max' => 50,
                    'integer' => true,
                    'required' => true,
                ],
                'session_key' => [
                    'type' => 'text',
                    'label' => 'شناسه اختصاصی نشست (اختیاری)',
                    'required' => false,
                    'placeholder' => '{{ input.chat_id }}',
                ],
            ],
            color: 'cyan',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, 'memory');
    }
}

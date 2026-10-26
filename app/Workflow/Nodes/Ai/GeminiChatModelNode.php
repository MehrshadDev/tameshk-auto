<?php

namespace App\Workflow\Nodes\Ai;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;

class GeminiChatModelNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.model.gemini',
            name: 'Gemini Chat Model',
            category: NodeCategory::Ai,
            description: 'ارائه‌دهنده مدل هوش مصنوعی Google Gemini جهت اتصال به ایجنت.',
            icon: 'brand:gemini',
            group: 'llm',
            inputs: [],
            outputs: [HandleType::ChatModel->value],
            configSchema: [
                'credential_id' => [
                    'type' => 'credential',
                    'credential_type' => 'gemini',
                    'label' => 'اعتبارنامه API',
                    'required' => false,
                ],
                'model' => [
                    'type' => 'model_select',
                    'label' => 'مدل هوش مصنوعی',
                    'default' => 'gemini-2.0-flash',
                    'required' => true,
                ],
                'temperature' => [
                    'type' => 'number',
                    'label' => 'میزان خلاقیت (Temperature)',
                    'default' => 0.4,
                    'min' => 0,
                    'max' => 2,
                ],
            ],
            color: 'indigo',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, 'chat_model');
    }
}

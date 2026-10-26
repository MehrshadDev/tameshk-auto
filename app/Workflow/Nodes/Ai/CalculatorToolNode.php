<?php

namespace App\Workflow\Nodes\Ai;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\ToolContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Support\MathEvaluator;

class CalculatorToolNode implements NodeContract, ToolContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.calculator',
            name: 'Calculator Tool',
            category: NodeCategory::Ai,
            description: 'ابزار انجام محاسبات دقیق ریاضی و فرمول‌های عددی توسط ایجنت هوش مصنوعی.',
            icon: 'calculator',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: [
                'tool_name' => [
                    'type' => 'text',
                    'label' => 'نام ابزار (انگلیسی)',
                    'default' => 'calculate',
                    'required' => true,
                ],
                'tool_description' => [
                    'type' => 'textarea',
                    'label' => 'توضیحات عملکرد ابزار برای مدل',
                    'default' => 'انجام محاسبات دقیق ریاضی نظیر جمع، تفریق، ضرب، تقسیم، توان و توابع ریاضی',
                    'required' => true,
                ],
            ],
            color: 'lime',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'calculate'));
    }

    public function toolDescription(array $config): string
    {
        return trim((string) ($config['tool_description'] ?? 'محاسبه ریاضی دقیق'));
    }

    public function toolParameters(array $config): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'expression' => [
                    'type' => 'STRING',
                    'description' => 'عبارت ریاضی استاندارد برای ارزیابی؛ مثلا: ((45 * 12) / 3) + sqrt(144)',
                ],
            ],
            'required' => ['expression'],
        ];
    }

    public function executeTool(NodeContext $context, array $arguments): array
    {
        $expression = trim((string) ($arguments['expression'] ?? ''));

        return [
            'expression' => $expression,
            'result' => MathEvaluator::evaluate($expression),
        ];
    }
}

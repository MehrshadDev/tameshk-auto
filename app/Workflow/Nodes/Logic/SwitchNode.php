<?php

namespace App\Workflow\Nodes\Logic;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Support\ConditionEvaluator;

class SwitchNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'logic.switch',
            name: 'Switch',
            category: NodeCategory::Logic,
            description: 'مسیریابی چندشاخه‌ای ورکفلو به ۴ مسیر مجزا بر اساس مقدار ورودی.',
            icon: 'git-branch',
            inputs: [HandleType::Main->value],
            outputs: [
                HandleType::Case1->value,
                HandleType::Case2->value,
                HandleType::Case3->value,
                HandleType::Default->value,
            ],
            configSchema: [
                'value_to_match' => [
                    'type'        => 'text',
                    'label'       => 'مقدار مورد بررسی (Value to Match)',
                    'required'    => true,
                    'placeholder' => 'مثلاً {{ input.text }} یا {{ input.status }}',
                ],
                'operator' => [
                    'type'    => 'select',
                    'label'   => 'نحوه مقایسه با حالت‌ها',
                    'options' => [
                        ['value' => 'eq', 'label' => 'برابر است با (Equals)'],
                        ['value' => 'contains', 'label' => 'شامل متن است (Contains)'],
                        ['value' => 'starts_with', 'label' => 'شروع می‌شود با (Starts With)'],
                        ['value' => 'ends_with', 'label' => 'پایان می‌یابد با (Ends With)'],
                        ['value' => 'regex', 'label' => 'تطابق با الگوی Regex'],
                    ],
                    'default'  => 'eq',
                    'required' => true,
                ],
                'case_1' => [
                    'type'        => 'text',
                    'label'       => 'مقدار حالت ۱ (خروجی ۱)',
                    'placeholder' => 'مثلاً /start یا paid',
                ],
                'case_2' => [
                    'type'        => 'text',
                    'label'       => 'مقدار حالت ۲ (خروجی ۲)',
                    'placeholder' => 'مثلاً /help یا pending',
                ],
                'case_3' => [
                    'type'        => 'text',
                    'label'       => 'مقدار حالت ۳ (خروجی ۳)',
                    'placeholder' => 'مثلاً /support یا canceled',
                ],
            ],
            color: 'orange',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $rawInput = trim((string) ($context->config['value_to_match'] ?? ''));
        $actual = ConditionEvaluator::resolveValue($rawInput, $context->input);
        $operator = (string) ($context->config['operator'] ?? 'eq');

        $cases = [
            HandleType::Case1->value => trim((string) ($context->config['case_1'] ?? '')),
            HandleType::Case2->value => trim((string) ($context->config['case_2'] ?? '')),
            HandleType::Case3->value => trim((string) ($context->config['case_3'] ?? '')),
        ];

        $matchedHandle = HandleType::Default->value;

        foreach ($cases as $handle => $expected) {
            if ($expected === '') {
                continue;
            }

            if (ConditionEvaluator::compare($actual, $operator, $expected)) {
                $matchedHandle = $handle;
                break;
            }
        }

        $context->log("مسیریابی Switch → $matchedHandle");

        return NodeResult::of(
            output: is_array($context->input)
                ? [...$context->input, '_matched_case' => $matchedHandle]
                : ['value' => $context->input, '_matched_case' => $matchedHandle],
            handle: $matchedHandle,
            meta: ['taken_handle' => $matchedHandle],
        );
    }
}

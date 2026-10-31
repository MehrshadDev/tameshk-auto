<?php

namespace App\Workflow\Nodes\Logic;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Support\ConditionEvaluator;
use Throwable;

class ConditionNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'logic.condition',
            name: 'If Condition',
            category: NodeCategory::Logic,
            description: 'هدایت مسیر اجرای ورکفلو بر اساس برقرار بودن یا نبودن یک یا چند شرط.',
            icon: 'arrows-split-2',
            inputs: [HandleType::Main->value],
            outputs: [HandleType::True->value, HandleType::False->value],
            configSchema: [
                'combinator' => [
                    'type'    => 'select',
                    'label'   => 'ترکیب شروط',
                    'options' => [
                        ['value' => 'and', 'label' => 'برقرار بودن همه شروط (AND)'],
                        ['value' => 'or', 'label' => 'برقرار بودن حداقل یک شرط (OR)'],
                    ],
                    'default' => 'and',
                ],
                'conditions' => [
                    'type'    => 'conditions',
                    'label'   => 'شروط مقایسه',
                    'default' => [
                        [
                            'field'    => '',
                            'operator' => 'eq',
                            'value'    => '',
                        ],
                    ],
                ],
            ],
            color: 'amber',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        try {
            $combinator = (string) ($context->config['combinator'] ?? 'and');
            $conditions = $context->config['conditions'] ?? [];

            if (empty($conditions) && isset($context->config['field'])) {
                $conditions = [
                    [
                        'field'    => $context->config['field'],
                        'operator' => $context->config['operator'] ?? 'eq',
                        'value'    => $context->config['value'] ?? '',
                    ],
                ];
            }

            if (! is_array($conditions)) {
                $conditions = [];
            }

            $finalPassed = ConditionEvaluator::evaluateRules($conditions, $combinator, $context->input);
            $handle = $finalPassed ? HandleType::True->value : HandleType::False->value;

            $context->log("ارزیابی شروط ($combinator) → " . ($finalPassed ? 'TRUE' : 'FALSE'));

            return NodeResult::of(
                output: [
                    'passed'     => $finalPassed,
                    'handle'     => $handle,
                    'input'      => $context->input,
                    'conditions' => $conditions,
                ],
                handle: $handle,
                meta: [
                    'condition_result' => $finalPassed,
                    'taken_handle'     => $handle,
                ],
            );
        } catch (Throwable) {
            return NodeResult::of(
                output: [
                    'passed'     => false,
                    'handle'     => HandleType::False->value,
                    'input'      => $context->input,
                    'conditions' => [],
                ],
                handle: HandleType::False->value,
                meta: [
                    'condition_result' => false,
                    'taken_handle'     => HandleType::False->value,
                ],
            );
        }
    }
}

<?php

namespace App\Workflow\Nodes\Logic;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
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
            $combinator = $context->config['combinator'] ?? 'and';
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

            $results = [];

            foreach ($conditions as $rule) {
                if (! is_array($rule)) {
                    continue;
                }

                $rawField = trim((string) ($rule['field'] ?? ''));
                $operator = (string) ($rule['operator'] ?? 'eq');
                $expected = $rule['value'] ?? null;

                if ($rawField === '') {
                    $actual = null;
                } else {
                    $cleanPath = preg_replace('/^\{\{\s*(?:input\.)?|\s*\}\}$/', '', $rawField);
                    $cleanPath = preg_replace('/^input\./', '', $cleanPath);

                    $actual = data_get($context->input, $cleanPath);

                    if ($actual === null && data_get($context->input, $rawField) !== null) {
                        $actual = data_get($context->input, $rawField);
                    }

                    if ($actual === null && ! str_contains($rawField, '{{')) {
                        $actual = $rawField;
                    }
                }

                if (is_numeric($actual) && is_numeric($expected)) {
                    $actual = (float) $actual;
                    $expected = (float) $expected;
                }

                $actualString = is_array($actual) || is_object($actual) ? json_encode($actual) : (string) ($actual ?? '');
                $expectedString = (string) ($expected ?? '');

                $passed = match ($operator) {
                    'eq'       => $actual == $expected,
                    'neq'      => $actual != $expected,
                    'gt'       => $actual > $expected,
                    'gte'      => $actual >= $expected,
                    'lt'       => $actual < $expected,
                    'lte'      => $actual <= $expected,
                    'contains' => str_contains($actualString, $expectedString),
                    'empty'    => empty($actual),
                    default    => false,
                };

                $results[] = $passed;
            }

            if (empty($results)) {
                $finalPassed = true;
            } elseif ($combinator === 'or') {
                $finalPassed = in_array(true, $results, true);
            } else {
                $finalPassed = ! in_array(false, $results, true);
            }

            $handle = $finalPassed ? HandleType::True->value : HandleType::False->value;

            $context->log("ارزیابی شروط شرط ($combinator) → " . ($finalPassed ? 'TRUE' : 'FALSE'));

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

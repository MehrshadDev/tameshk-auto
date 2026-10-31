<?php

namespace App\Workflow\Nodes\Transform;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Support\ConditionEvaluator;

class ItemListsNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'transform.item_lists',
            name: 'Item Lists',
            category: NodeCategory::Transform,
            description: 'فیلتر کردن، مرتب‌سازی، حذف تکراری‌ها، محدودسازی و تجمیع آیتم‌های یک آرایه یا لیست.',
            icon: 'list',
            inputs: [HandleType::Main->value],
            outputs: [HandleType::Main->value],
            configSchema: [
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات روی لیست',
                    'options'  => [
                        ['value' => 'filter', 'label' => 'فیلتر کردن آیتم‌ها (Filter Items)'],
                        ['value' => 'sort', 'label' => 'مرتب‌سازی (Sort)'],
                        ['value' => 'deduplicate', 'label' => 'حذف آیتم‌های تکراری (Remove Duplicates)'],
                        ['value' => 'limit', 'label' => 'محدود کردن تعداد (Limit / Slice)'],
                        ['value' => 'pluck', 'label' => 'استخراج یک فیلد از همه آیتم‌ها (Pluck Field)'],
                        ['value' => 'summarize', 'label' => 'محاسبه و تجمیع عددی (Summarize)'],
                    ],
                    'default'  => 'filter',
                    'required' => true,
                ],
                'source_field' => [
                    'type'        => 'text',
                    'label'       => 'فیلد آرایه ورودی',
                    'default'     => 'rows',
                    'placeholder' => 'مثلاً rows یا messages یا items',
                    'help'        => 'نام فیلدی که لیست در آن قرار دارد (اگر کل ورودی آرایه باشد، خودش تشخیص می‌دهد).',
                    'required'    => false,
                ],
                'combinator' => [
                    'type'       => 'select',
                    'label'      => 'ترکیب شروط فیلتر',
                    'options'    => [
                        ['value' => 'and', 'label' => 'برقرار بودن همه شروط (AND)'],
                        ['value' => 'or', 'label' => 'برقرار بودن حداقل یک شرط (OR)'],
                    ],
                    'default'    => 'and',
                    'depends_on' => ['operation' => 'filter'],
                ],
                'conditions' => [
                    'type'       => 'conditions',
                    'label'      => 'شروط فیلتر هر آیتم',
                    'default'    => [
                        [
                            'field'    => '',
                            'operator' => 'eq',
                            'value'    => '',
                        ],
                    ],
                    'depends_on' => ['operation' => 'filter'],
                ],
                'target_field' => [
                    'type'        => 'text',
                    'label'       => 'نام فیلد داخل هر آیتم',
                    'placeholder' => 'مثلاً price یا email یا id',
                    'required'    => false,
                    'depends_on'  => ['operation' => ['sort', 'deduplicate', 'pluck', 'summarize']],
                ],
                'sort_direction' => [
                    'type'       => 'select',
                    'label'      => 'جهت مرتب‌سازی',
                    'options'    => [
                        ['value' => 'asc', 'label' => 'صعودی (کم به زیاد / الفبایی)'],
                        ['value' => 'desc', 'label' => 'نزولی (زیاد به کم / معکوس)'],
                    ],
                    'default'    => 'asc',
                    'depends_on' => ['operation' => 'sort'],
                ],
                'max_items' => [
                    'type'       => 'number',
                    'label'      => 'حداکثر تعداد آیتم',
                    'default'    => 10,
                    'min'        => 1,
                    'max'        => 5000,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['operation' => 'limit'],
                ],
                'slice_from' => [
                    'type'       => 'select',
                    'label'      => 'انتخاب از',
                    'options'    => [
                        ['value' => 'start', 'label' => 'ابتدای لیست (First N Items)'],
                        ['value' => 'end', 'label' => 'انتهای لیست (Last N Items)'],
                    ],
                    'default'    => 'start',
                    'depends_on' => ['operation' => 'limit'],
                ],
            ],
            color: 'cyan',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $operation = (string) ($context->config['operation'] ?? 'filter');
        $list = $this->extractList($context);
        $targetField = trim((string) ($context->config['target_field'] ?? ''));

        return match ($operation) {
            'filter'      => $this->handleFilter($context, $list),
            'sort'        => $this->handleSort($context, $list, $targetField),
            'deduplicate' => $this->handleDeduplicate($list, $targetField),
            'limit'       => $this->handleLimit($context, $list),
            'pluck'       => $this->handlePluck($list, $targetField),
            'summarize'   => $this->handleSummarize($list, $targetField),
            default       => $this->result($list),
        };
    }

    private function extractList(NodeContext $context): array
    {
        $rawField = trim((string) ($context->config['source_field'] ?? 'rows'));
        $cleanPath = preg_replace('/^\{\{\s*(?:input\.)?|\s*\}\}$/', '', $rawField);
        $cleanPath = trim((string) preg_replace('/^input\./', '', (string) $cleanPath));

        if ($cleanPath !== '' && (is_array($context->input) || is_object($context->input))) {
            $candidate = data_get($context->input, $cleanPath);
            if (is_array($candidate) && array_is_list($candidate)) {
                return $candidate;
            }
        }

        if (is_array($context->input)) {
            if (array_is_list($context->input)) {
                return $context->input;
            }

            foreach (['items', 'rows', 'messages', 'results', 'data', 'values'] as $fallbackKey) {
                $val = $context->input[$fallbackKey] ?? null;
                if (is_array($val) && array_is_list($val)) {
                    return $val;
                }
            }
        }

        return [];
    }

    private function handleFilter(NodeContext $context, array $list): NodeResult
    {
        $combinator = (string) ($context->config['combinator'] ?? 'and');
        $conditions = is_array($context->config['conditions'] ?? null) ? $context->config['conditions'] : [];

        $kept = [];
        $rejected = [];

        foreach ($list as $item) {
            if (ConditionEvaluator::evaluateRules($conditions, $combinator, $item)) {
                $kept[] = $item;
            } else {
                $rejected[] = $item;
            }
        }

        return NodeResult::of([
            'items'          => $kept,
            'rows'           => $kept,
            'count'          => count($kept),
            'rejected_count' => count($rejected),
            'first'          => $kept[0] ?? null,
            'last'           => $kept !== [] ? $kept[count($kept) - 1] : null,
        ]);
    }

    private function handleSort(NodeContext $context, array $list, string $targetField): NodeResult
    {
        $desc = ((string) ($context->config['sort_direction'] ?? 'asc')) === 'desc';

        usort($list, function ($a, $b) use ($targetField, $desc) {
            $valA = ($targetField !== '' && (is_array($a) || is_object($a))) ? data_get($a, $targetField) : $a;
            $valB = ($targetField !== '' && (is_array($b) || is_object($b))) ? data_get($b, $targetField) : $b;

            if (is_numeric($valA) && is_numeric($valB)) {
                $cmp = (float) $valA <=> (float) $valB;
            } else {
                $cmp = strnatcasecmp((string) ($valA ?? ''), (string) ($valB ?? ''));
            }

            return $desc ? -$cmp : $cmp;
        });

        return $this->result(array_values($list));
    }

    private function handleDeduplicate(array $list, string $targetField): NodeResult
    {
        $seen = [];
        $unique = [];

        foreach ($list as $item) {
            $keyVal = ($targetField !== '' && (is_array($item) || is_object($item)))
                ? data_get($item, $targetField)
                : $item;

            $hash = is_scalar($keyVal)
                ? (string) $keyVal
                : md5((string) json_encode($keyVal, JSON_UNESCAPED_UNICODE));

            if (! isset($seen[$hash])) {
                $seen[$hash] = true;
                $unique[] = $item;
            }
        }

        return $this->result($unique, ['removed_duplicates' => count($list) - count($unique)]);
    }

    private function handleLimit(NodeContext $context, array $list): NodeResult
    {
        $max = max(1, (int) ($context->config['max_items'] ?? 10));
        $fromEnd = ((string) ($context->config['slice_from'] ?? 'start')) === 'end';

        $sliced = $fromEnd ? array_slice($list, -$max) : array_slice($list, 0, $max);

        return $this->result(array_values($sliced));
    }

    private function handlePluck(array $list, string $targetField): NodeResult
    {
        $values = [];

        foreach ($list as $item) {
            $val = ($targetField !== '' && (is_array($item) || is_object($item)))
                ? data_get($item, $targetField)
                : $item;

            if ($val !== null) {
                $values[] = $val;
            }
        }

        $scalars = array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE), $values);

        return NodeResult::of([
            'values' => $values,
            'items'  => $values,
            'joined' => implode(', ', $scalars),
            'count'  => count($values),
            'first'  => $values[0] ?? null,
        ]);
    }

    private function handleSummarize(array $list, string $targetField): NodeResult
    {
        $numbers = [];

        foreach ($list as $item) {
            $val = ($targetField !== '' && (is_array($item) || is_object($item)))
                ? data_get($item, $targetField)
                : $item;

            if (is_numeric($val)) {
                $numbers[] = (float) $val;
            }
        }

        $count = count($list);
        $numericCount = count($numbers);
        $sum = $numericCount > 0 ? array_sum($numbers) : 0;

        return NodeResult::of([
            'count'         => $count,
            'numeric_count' => $numericCount,
            'sum'           => $sum,
            'average'       => $numericCount > 0 ? round($sum / $numericCount, 4) : 0,
            'min'           => $numericCount > 0 ? min($numbers) : null,
            'max'           => $numericCount > 0 ? max($numbers) : null,
        ]);
    }

    private function result(array $items, array $extra = []): NodeResult
    {
        return NodeResult::of([
            'items' => $items,
            'rows'  => $items,
            'count' => count($items),
            'first' => $items[0] ?? null,
            'last'  => $items !== [] ? $items[count($items) - 1] : null,
            ...$extra,
        ]);
    }
}

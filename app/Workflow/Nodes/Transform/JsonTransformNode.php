<?php

namespace App\Workflow\Nodes\Transform;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;

class JsonTransformNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'transform.json',
            name: 'JSON Parser',
            category: NodeCategory::Transform,
            description: 'تبدیل متن یا خروجی مارک‌داون هوش مصنوعی به ساختار JSON و برعکس.',
            icon: 'braces',
            inputs: [HandleType::Main->value],
            outputs: [HandleType::Main->value],
            configSchema: [
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات',
                    'options'  => [
                        ['value' => 'parse', 'label' => 'تبدیل متن به JSON (Parse String to JSON)'],
                        ['value' => 'stringify', 'label' => 'تبدیل داده به رشته JSON (Stringify to Text)'],
                    ],
                    'default'  => 'parse',
                    'required' => true,
                ],
                'raw_json' => [
                    'type'        => 'textarea',
                    'label'       => 'متن حاوی JSON',
                    'default'     => '{{ input.response }}',
                    'placeholder' => 'مثلاً {{ input.response }} یا {{ input.text }}',
                    'help'        => 'اگر خروجی هوش مصنوعی داخل بلاک ```json باشد، به صورت خودکار استخراج و پارس می‌شود.',
                    'required'    => true,
                    'depends_on'  => ['operation' => 'parse'],
                ],
                'pretty_print' => [
                    'type'       => 'boolean',
                    'label'      => 'قالب‌بندی خوانا و چندخطی (Pretty Print)',
                    'default'    => true,
                    'depends_on' => ['operation' => 'stringify'],
                ],
            ],
            color: 'emerald',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $operation = (string) ($context->config['operation'] ?? 'parse');

        if ($operation === 'stringify') {
            $pretty = filter_var($context->config['pretty_print'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | ($pretty ? JSON_PRETTY_PRINT : 0);
            $jsonStr = (string) json_encode($context->input, $flags);

            return NodeResult::of([
                'json'     => $jsonStr,
                'text'     => $jsonStr,
                'response' => $jsonStr,
            ]);
        }

        $raw = trim((string) ($context->config['raw_json'] ?? ''));

        if ($raw === '' && is_string($context->input)) {
            $raw = trim($context->input);
        }

        if ($raw === '') {
            throw NodeExecutionException::because('متن ورودی برای تبدیل به JSON خالی است.');
        }

        $decoded = $this->extractAndDecode($raw);

        if (is_array($decoded) && ! array_is_list($decoded)) {
            return NodeResult::of([
                ...$decoded,
                '_parsed' => $decoded,
            ]);
        }

        return NodeResult::of([
            'items' => is_array($decoded) ? $decoded : [$decoded],
            'data'  => $decoded,
        ]);
    }

    private function extractAndDecode(string $raw): mixed
    {
        $direct = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $direct;
        }

        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $raw, $fenceMatch)) {
            $fenced = json_decode(trim($fenceMatch[1]), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $fenced;
            }
        }

        $firstBrace = strpos($raw, '{');
        $lastBrace = strrpos($raw, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $candidate = substr($raw, $firstBrace, $lastBrace - $firstBrace + 1);
            $parsed = json_decode($candidate, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $parsed;
            }
        }

        $firstBracket = strpos($raw, '[');
        $lastBracket = strrpos($raw, ']');
        if ($firstBracket !== false && $lastBracket !== false && $lastBracket > $firstBracket) {
            $candidate = substr($raw, $firstBracket, $lastBracket - $firstBracket + 1);
            $parsed = json_decode($candidate, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $parsed;
            }
        }

        throw NodeExecutionException::because('ساختار JSON معتبری در متن ورودی یافت نشد.');
    }
}

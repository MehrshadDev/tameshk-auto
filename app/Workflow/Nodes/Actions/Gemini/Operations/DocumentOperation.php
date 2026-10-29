<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperationContract;
use App\Workflow\Support\GeminiClient;

final class DocumentOperation implements GeminiOperationContract
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $docRaw = trim((string)($context->config['document_input'] ?? ''));
        $mime = (string)($context->config['document_mime_type'] ?? 'application/pdf');
        $prompt = trim((string)($context->config['prompt'] ?? 'این سند را بررسی و نکات مهم آن را خلاصه کن.'));
        $model = (string)($context->config['model'] ?? 'gemini-2.5-flash');
        $system = trim((string)($context->config['system_instruction'] ?? ''));

        if ($docRaw === '') {
            throw NodeExecutionException::because('ورودی سند (Document input) الزامی است.');
        }

        $context->log("تحلیل سند توسط Gemini ($model)");

        $docPart = $client->resolveInputPart($docRaw, $mime);

        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    $docPart,
                    ['text' => $prompt],
                ],
            ],
        ];

        $res = $client->generateContent(
            model: $model,
            contents: $contents,
            generationConfig: ['temperature' => 0.3],
            systemInstruction: $system !== '' ? $system : null
        );

        $text = (string)data_get($res, 'candidates.0.content.parts.0.text', '');

        return NodeResult::of([
            'text' => $text,
            'response' => $text,
            'model' => $model,
            'raw' => $res,
        ]);
    }
}

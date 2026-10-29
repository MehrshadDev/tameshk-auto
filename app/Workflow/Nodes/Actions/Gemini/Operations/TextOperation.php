<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperationContract;
use App\Workflow\Support\GeminiClient;

final class TextOperation implements GeminiOperationContract
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $model = (string)($context->config['model'] ?? 'gemini-2.5-flash');
        $prompt = trim((string)($context->config['prompt'] ?? ''));
        $system = trim((string)($context->config['system_instruction'] ?? ''));
        $temperature = (float)($context->config['temperature'] ?? 0.7);

        if ($prompt === '') {
            throw NodeExecutionException::because('پرامپت ورودی نمی‌تواند خالی باشد.');
        }

        $context->log("ارسال پرامپت متنی به Gemini ($model)");

        $contents = [
            [
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ],
        ];

        $response = $client->generateContent(
            model: $model,
            contents: $contents,
            generationConfig: ['temperature' => $temperature],
            systemInstruction: $system !== '' ? $system : null
        );

        $text = (string)data_get($response, 'candidates.0.content.parts.0.text', '');

        return NodeResult::of([
            'text' => $text,
            'response' => $text,
            'model' => $model,
            'raw' => $response,
        ]);
    }
}

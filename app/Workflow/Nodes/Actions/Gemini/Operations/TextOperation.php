<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperation;
use App\Workflow\Support\GeminiClient;

final class TextOperation extends GeminiOperation
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $model = $this->model($context, 'model');
        $prompt = $this->ensure($this->str($context, 'prompt'), 'پرامپت ورودی نمی‌تواند خالی باشد.');
        $system = $this->str($context, 'system_instruction');

        $context->log("ارسال پرامپت متنی به Gemini ($model)");

        $answer = $client->ask(
            $model,
            [['text' => $prompt]],
            ['temperature' => (float) ($context->config['temperature'] ?? 0.7)],
            $system !== '' ? $system : null,
        );

        return $this->textResult($answer, $model);
    }
}


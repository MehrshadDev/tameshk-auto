<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperation;
use App\Workflow\Support\GeminiClient;

final class DocumentOperation extends GeminiOperation
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $document = $this->ensure($this->str($context, 'document_input'), 'ورودی سند (Document input) الزامی است.');
        $model = $this->model($context);
        $prompt = $this->str($context, 'prompt', 'این سند را بررسی و نکات مهم آن را خلاصه کن.');
        $system = $this->str($context, 'system_instruction');

        $context->log("تحلیل سند توسط Gemini ($model)");

        $part = $client->resolveInputPart($document, $this->str($context, 'document_mime_type', 'application/pdf'));

        $answer = $client->ask(
            $model,
            [$part, ['text' => $prompt]],
            ['temperature' => 0.3],
            $system !== '' ? $system : null,
        );

        return $this->textResult($answer, $model);
    }
}

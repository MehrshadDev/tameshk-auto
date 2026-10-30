<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperation;
use App\Workflow\Support\GeminiClient;

final class AudioOperation extends GeminiOperation
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $model = $this->model($context, 'model_multimodal');
        $audio = $this->ensure($this->str($context, 'audio_input'), 'آدرس فایل صوتی (Audio input) الزامی است.');
        $part = $client->resolveInputPart($audio, $this->str($context, 'audio_mime_type', 'audio/mp3'));

        $prompt = $this->str($context, 'operation_audio', 'analyze_audio') === 'transcribe_audio'
            ? 'لطفاً تمام کلمات بیان‌شده در این فایل صوتی را با دقت بالا، بدون کم‌وکاست و به صورت رونوشت کلمه‌به‌کلمه پیاده‌سازی کن.'
            : $this->str($context, 'prompt', 'محتوای این فایل صوتی را تحلیل و خلاصه‌سازی کن.');

        $context->log("پردازش فایل صوتی در Gemini ($model)");

        $answer = $client->ask($model, [$part, ['text' => $prompt]], ['temperature' => 0.2]);

        return $this->textResult($answer, $model, ['transcription' => $answer['text']]);
    }
}

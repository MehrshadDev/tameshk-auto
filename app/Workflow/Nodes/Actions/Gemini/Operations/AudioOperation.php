<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperationContract;
use App\Workflow\Support\GeminiClient;

final class AudioOperation implements GeminiOperationContract
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $operation = (string)($context->config['operation_audio'] ?? 'analyze_audio');
        $audioRaw = trim((string)($context->config['audio_input'] ?? ''));
        $mime = (string)($context->config['audio_mime_type'] ?? 'audio/mp3');
        $model = (string)($context->config['model_multimodal'] ?? 'gemini-2.5-flash');

        if ($audioRaw === '') {
            throw NodeExecutionException::because('آدرس فایل صوتی (Audio input) الزامی است.');
        }

        $audioPart = $client->resolveInputPart($audioRaw, $mime);

        if ($operation === 'transcribe_audio') {
            $prompt = 'لطفاً تمام کلمات بیان‌شده در این فایل صوتی را با دقت بالا، بدون کم‌وکاست و به صورت رونوشت کلمه‌به‌کلمه پیاده‌سازی کن.';
        } else {
            $prompt = trim((string)($context->config['prompt'] ?? 'محتوای این فایل صوتی را تحلیل و خلاصه‌سازی کن.'));
        }

        $context->log("پردازش فایل صوتی در Gemini ($model)");

        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    $audioPart,
                    ['text' => $prompt],
                ],
            ],
        ];

        $res = $client->generateContent($model, $contents, ['temperature' => 0.2]);
        $text = (string)data_get($res, 'candidates.0.content.parts.0.text', '');

        return NodeResult::of([
            'transcription' => $text,
            'text' => $text,
            'response' => $text,
            'model' => $model,
            'raw' => $res,
        ]);
    }
}

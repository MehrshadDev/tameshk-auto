<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperationContract;
use App\Workflow\Support\GeminiClient;

final class VideoOperation implements GeminiOperationContract
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $operation = (string)($context->config['operation_video'] ?? 'analyze_video');

        if ($operation === 'download_video') {
            $videoUrl = trim((string)($context->config['video_input'] ?? ''));
            if ($videoUrl === '') {
                throw NodeExecutionException::because('آدرس ویدیو الزامی است.');
            }
            $context->log("دانلود فایل ویدیویی از $videoUrl");
            $b64 = $client->downloadUrl($videoUrl);

            return NodeResult::of([
                'success' => true,
                'data_url' => "data:video/mp4;base64,$b64",
                'base64' => $b64,
                'video_url' => $videoUrl,
            ]);
        }

        if ($operation === 'generate_video') {
            $prompt = trim((string)($context->config['prompt'] ?? ''));
            if ($prompt === '') {
                throw NodeExecutionException::because('پرامپت برای تولید ویدیو الزامی است.');
            }

            $context->log("تولید سناریو و ساختار ویدیویی با Gemini: $prompt");
            $model = (string)($context->config['model_multimodal'] ?? 'gemini-2.5-flash');

            $system = 'شما یک کارگردان و تهیه‌کننده متخصص تولید ویدیو و هوش مصنوعی هستید. برای پرامپت ارائه‌شده، یک استوری‌بورد و پرامپت تولید صحنه‌های ویدیویی دقیق با زمان‌بندی، زاویه دوربین، حرکت و نورپردازی تولید کنید.';
            $contents = [
                ['role' => 'user', 'parts' => [['text' => "پرامپت ویدیو:\n$prompt"]]],
            ];

            $res = $client->generateContent($model, $contents, ['temperature' => 0.7], $system);
            $storyboard = (string)data_get($res, 'candidates.0.content.parts.0.text', '');

            return NodeResult::of([
                'prompt' => $prompt,
                'storyboard' => $storyboard,
                'text' => $storyboard,
                'response' => $storyboard,
                'status' => 'ready',
            ]);
        }

        $videoRaw = trim((string)($context->config['video_input'] ?? ''));
        $mime = (string)($context->config['video_mime_type'] ?? 'video/mp4');
        $prompt = trim((string)($context->config['prompt'] ?? 'این ویدیو را تحلیل و اتفاقات رخ‌داده در آن را تشریح کن.'));
        $model = (string)($context->config['model_multimodal'] ?? 'gemini-2.5-flash');

        if ($videoRaw === '') {
            throw NodeExecutionException::because('آدرس ویدیو الزامی است.');
        }

        $context->log("تحلیل ویدیو در Gemini ($model)");
        $videoPart = $client->resolveInputPart($videoRaw, $mime);

        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    $videoPart,
                    ['text' => $prompt],
                ],
            ],
        ];

        $res = $client->generateContent($model, $contents, ['temperature' => 0.4]);
        $text = (string)data_get($res, 'candidates.0.content.parts.0.text', '');

        return NodeResult::of([
            'text' => $text,
            'response' => $text,
            'model' => $model,
            'raw' => $res,
        ]);
    }
}

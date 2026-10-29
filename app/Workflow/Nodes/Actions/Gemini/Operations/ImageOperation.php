<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperationContract;
use App\Workflow\Support\GeminiClient;

final class ImageOperation implements GeminiOperationContract
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $operation = (string)($context->config['operation_image'] ?? 'analyze_image');

        if ($operation === 'generate_image') {
            $prompt = trim((string)($context->config['prompt'] ?? ''));
            if ($prompt === '') {
                throw NodeExecutionException::because('پرامپت برای تولید تصویر الزامی است.');
            }

            $context->log("تولید تصویر با Imagen 3: $prompt");

            $options = [
                'aspect_ratio' => (string)($context->config['aspect_ratio'] ?? '1:1'),
                'number_of_images' => (int)($context->config['number_of_images'] ?? 1),
                'output_format' => (string)($context->config['output_format'] ?? 'image/jpeg'),
            ];

            $res = $client->generateImages($prompt, $options);
            $predictions = (array)data_get($res, 'predictions', []);
            $images = [];

            foreach ($predictions as $idx => $pred) {
                $b64 = (string)data_get($pred, 'bytesBase64Encoded', '');
                if ($b64 !== '') {
                    $images[] = [
                        'index' => $idx + 1,
                        'mime_type' => $options['output_format'],
                        'data_url' => "data:{$options['output_format']};base64,$b64",
                        'base64' => $b64,
                    ];
                }
            }

            return NodeResult::of([
                'success' => true,
                'count' => count($images),
                'images' => $images,
                'first' => $images[0] ?? null,
                'data_url' => $images[0]['data_url'] ?? '',
                'prompt' => $prompt,
            ]);
        }

        $imageRaw = trim((string)($context->config['image_input'] ?? ''));
        $prompt = trim((string)($context->config['prompt'] ?? ''));
        $model = (string)($context->config['model_multimodal'] ?? 'gemini-2.5-flash');

        if ($imageRaw === '') {
            throw NodeExecutionException::because('آدرس یا داده تصویر (Image input) الزامی است.');
        }

        if ($prompt === '') {
            $prompt = $operation === 'edit_image'
                ? 'لطفاً این تصویر را طبق دستورالعمل بررسی و تغییرات مشخص‌شده را شرح بده.'
                : 'این تصویر را با جزئیات کامل توصیف و تحلیل کن.';
        }

        $context->log("ارسال تصویر به Gemini ($model)");
        $imagePart = $client->resolveInputPart($imageRaw, 'image/jpeg');

        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    $imagePart,
                    ['text' => $prompt],
                ],
            ],
        ];

        $temperature = (float)($context->config['temperature'] ?? 0.4);
        $res = $client->generateContent($model, $contents, ['temperature' => $temperature]);
        $text = (string)data_get($res, 'candidates.0.content.parts.0.text', '');

        return NodeResult::of([
            'text' => $text,
            'response' => $text,
            'operation' => $operation,
            'model' => $model,
            'raw' => $res,
        ]);
    }
}

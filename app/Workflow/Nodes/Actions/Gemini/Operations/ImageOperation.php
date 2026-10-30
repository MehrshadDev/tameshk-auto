<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperation;
use App\Workflow\Support\GeminiClient;

final class ImageOperation extends GeminiOperation
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $operation = $this->str($context, 'operation_image', 'analyze_image');

        return $operation === 'generate_image'
            ? $this->generate($context, $client)
            : $this->analyze($context, $client, $operation);
    }

    private function generate(NodeContext $context, GeminiClient $client): NodeResult
    {
        $prompt = $this->ensure($this->str($context, 'prompt'), 'پرامپت برای تولید تصویر الزامی است.');

        $context->log("تولید تصویر با Imagen 3: $prompt");

        $options = [
            'aspect_ratio'     => $this->str($context, 'aspect_ratio', '1:1'),
            'number_of_images' => (int) ($context->config['number_of_images'] ?? 1),
            'output_format'    => $this->str($context, 'output_format', 'image/jpeg'),
        ];

        $images = [];

        foreach ((array) data_get($client->generateImages($prompt, $options), 'predictions', []) as $index => $prediction) {
            $base64 = (string) data_get($prediction, 'bytesBase64Encoded', '');

            if ($base64 === '') {
                continue;
            }

            $images[] = [
                'index'     => $index + 1,
                'mime_type' => $options['output_format'],
                'data_url'  => "data:{$options['output_format']};base64,$base64",
                'base64'    => $base64,
            ];
        }

        return NodeResult::of([
            'success'  => true,
            'count'    => count($images),
            'images'   => $images,
            'first'    => $images[0] ?? null,
            'data_url' => $images[0]['data_url'] ?? '',
            'prompt'   => $prompt,
        ]);
    }

    private function analyze(NodeContext $context, GeminiClient $client, string $operation): NodeResult
    {
        $image = $this->ensure($this->str($context, 'image_input'), 'آدرس یا داده تصویر (Image input) الزامی است.');
        $model = $this->model($context, 'model_multimodal');

        $prompt = $this->str($context, 'prompt') ?: ($operation === 'edit_image'
            ? 'لطفاً این تصویر را طبق دستورالعمل بررسی و تغییرات مشخص‌شده را شرح بده.'
            : 'این تصویر را با جزئیات کامل توصیف و تحلیل کن.');

        $context->log("ارسال تصویر به Gemini ($model)");

        $answer = $client->ask(
            $model,
            [$client->resolveInputPart($image, 'image/jpeg'), ['text' => $prompt]],
            ['temperature' => (float) ($context->config['temperature'] ?? 0.4)],
        );

        return $this->textResult($answer, $model, ['operation' => $operation]);
    }
}

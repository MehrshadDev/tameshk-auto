<?php

namespace App\Workflow\Nodes\Actions;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\GeminiError;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GeminiNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'action.gemini',
            name: 'Google Gemini',
            category: NodeCategory::Ai,
            description: 'ارسال پرامپت متنی به هوش مصنوعی Google Gemini و دریافت پاسخ.',
            icon: 'brand:gemini',
            group: null,
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type' => 'credential',
                    'credential_type' => 'gemini',
                    'label' => 'اعتبارنامه API',
                    'required' => true,
                ],
                'model' => [
                    'type' => 'model_select',
                    'label' => 'مدل هوش مصنوعی',
                    'default' => 'gemini-2.5-flash',
                    'required' => true,
                ],
                'prompt' => [
                    'type' => 'textarea',
                    'label' => 'پرامپت ورودی',
                    'required' => true,
                    'placeholder' => 'متن یا پرامپت خود را بنویسید؛ مثلاً: متن زیر را خلاصه کن: {{input.body}}',
                ],
                'temperature' => [
                    'type' => 'number',
                    'label' => 'میزان خلاقیت (Temperature)',
                    'default' => 0.7,
                    'min' => 0,
                    'max' => 2,
                ],
            ],
            color: 'blue',
        );
    }

    /**
     * @throws ConnectionException
     */
    public function execute(NodeContext $context): NodeResult
    {
        $credential = $context->credential();

        if (!$credential) {
            throw NodeExecutionException::because('اعتبارنامه Gemini انتخاب نشده است.');
        }

        $apiKey = $credential->get('api_key');
        $model = !empty($context->config['model']) ? (string) $context->config['model'] : $credential->get('model', 'gemini-2.5-flash');
        $prompt = $context->config['prompt'] ?? '';
        $temperature = (float)($context->config['temperature'] ?? 0.7);

        if (empty($prompt)) {
            throw NodeExecutionException::because('پرامپت ارسالی خالی است.');
        }

        $context->log("ارسال به Gemini ($model)");

        $baseUrl = rtrim(config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/');
        $endpoint = str_ends_with($baseUrl, '/v1beta')
            ? "$baseUrl/models/$model:generateContent"
            : "$baseUrl/v1beta/models/$model:generateContent";

        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
        ])->timeout(60)->post(
            $endpoint,
            [
                'contents' => [[
                    'parts' => [['text' => $prompt]],
                ]],
                'generationConfig' => [
                    'temperature' => $temperature,
                ],
            ]
        );

        if ($response->failed()) {
            throw NodeExecutionException::because(GeminiError::describe($response));
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (!$text) {
            throw NodeExecutionException::because('پاسخ دریافت شده از مدل هوش مصنوعی خالی بود.');
        }

        return NodeResult::of([
            'text' => $text,
            'model' => $model,
            'source' => $context->input,
        ]);
    }
}

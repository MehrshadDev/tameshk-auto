<?php

namespace App\Workflow\Support;

use App\Models\Credential;
use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

final class LlmChatClient
{
    /**
     * @param array<int, array{role: string, text?: string, tool_calls?: array, tool_call_id?: string, name?: string, content?: mixed}> $messages
     * @param array<int, array{name: string, description: string, parameters: array}> $tools
     * @return array{text: string, tool_calls: array<int, array{id: string, name: string, args: array}>, raw_assistant_message: array}
     */
    public static function chat(
        Credential $credential,
        string     $model,
        array      $messages,
        ?string    $systemPrompt = null,
        float      $temperature = 0.4,
        array      $tools = [],
    ): array
    {
        return match ($credential->type) {
            'gemini' => self::chatGemini($credential, $model, $messages, $systemPrompt, $temperature, $tools),
            'anthropic' => self::chatAnthropic($credential, $model, $messages, $systemPrompt, $temperature, $tools),
            'openai' => self::chatOpenAiCompatible($credential, $model, $messages, $systemPrompt, $temperature, $tools, 'https://api.openai.com/v1'),
            'deepseek' => self::chatOpenAiCompatible($credential, $model, $messages, $systemPrompt, $temperature, $tools, 'https://api.deepseek.com/v1'),
            'groq' => self::chatOpenAiCompatible($credential, $model, $messages, $systemPrompt, $temperature, $tools, 'https://api.groq.com/openai/v1'),
            'openrouter' => self::chatOpenAiCompatible($credential, $model, $messages, $systemPrompt, $temperature, $tools, 'https://openrouter.ai/api/v1'),
            default => throw NodeExecutionException::because("سرویس مدل چت پشتیبانی نمی‌شود: $credential->type"),
        };
    }

    private static function chatGemini(
        Credential $credential,
        string     $model,
        array      $messages,
        ?string    $systemPrompt,
        float      $temperature,
        array      $tools,
    ): array
    {
        $apiKey = (string)$credential->get('api_key');
        $baseUrl = rtrim((string)config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/');
        $endpoint = str_ends_with($baseUrl, '/v1beta')
            ? "$baseUrl/models/$model:generateContent"
            : "$baseUrl/v1beta/models/$model:generateContent";

        $contents = [];
        foreach ($messages as $msg) {
            if (isset($msg['gemini_raw'])) {
                $contents[] = $msg['gemini_raw'];
                continue;
            }

            if ($msg['role'] === 'tool') {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [[
                        'functionResponse' => [
                            'name' => $msg['name'],
                            'response' => is_array($msg['content']) ? $msg['content'] : ['result' => $msg['content']],
                        ],
                    ]],
                ];
                continue;
            }

            $contents[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => (string)($msg['text'] ?? '')]],
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => ['temperature' => $temperature],
        ];

        if ($systemPrompt !== null && $systemPrompt !== '') {
            $payload['system_instruction'] = ['parts' => [['text' => $systemPrompt]]];
        }

        if (!empty($tools)) {
            $payload['tools'] = [['functionDeclarations' => $tools]];
        }

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $apiKey])
                ->timeout(90)
                ->post($endpoint, $payload);
        } catch (Throwable $e) {
            throw NodeExecutionException::because("سرویس Gemini در دسترس نیست: {$e->getMessage()}");
        }

        if ($response->failed()) {
            throw NodeExecutionException::because(GeminiError::describe($response));
        }

        $candidateContent = (array)data_get($response->json(), 'candidates.0.content', []);
        $parts = (array)data_get($candidateContent, 'parts', []);

        $toolCalls = [];
        $textParts = [];

        foreach ($parts as $part) {
            if (isset($part['functionCall'])) {
                $fc = $part['functionCall'];
                $toolCalls[] = [
                    'id' => 'call_' . uniqid(),
                    'name' => (string)($fc['name'] ?? ''),
                    'args' => (array)($fc['args'] ?? []),
                ];
            }
            if (isset($part['text'])) {
                $textParts[] = $part['text'];
            }
        }

        return [
            'text' => trim(implode("\n", $textParts)),
            'tool_calls' => $toolCalls,
            'raw_assistant_message' => [
                'role' => 'assistant',
                'gemini_raw' => $candidateContent,
            ],
        ];
    }

    private static function chatOpenAiCompatible(
        Credential $credential,
        string     $model,
        array      $messages,
        ?string    $systemPrompt,
        float      $temperature,
        array      $tools,
        string     $defaultBaseUrl,
    ): array
    {
        $apiKey = trim((string)$credential->get('api_key'));
        $customBase = trim((string)$credential->get('base_url', ''));
        $baseUrl = rtrim($customBase !== '' ? $customBase : $defaultBaseUrl, '/');
        $endpoint = "$baseUrl/chat/completions";

        $formattedMessages = [];

        if ($systemPrompt !== null && $systemPrompt !== '') {
            $formattedMessages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        foreach ($messages as $msg) {
            if (isset($msg['openai_raw'])) {
                $formattedMessages[] = $msg['openai_raw'];
                continue;
            }

            if ($msg['role'] === 'tool') {
                $formattedMessages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $msg['tool_call_id'],
                    'content' => is_string($msg['content'])
                        ? $msg['content']
                        : json_encode($msg['content'], JSON_UNESCAPED_UNICODE),
                ];
                continue;
            }

            $formattedMessages[] = [
                'role' => $msg['role'] === 'model' ? 'assistant' : $msg['role'],
                'content' => (string)($msg['text'] ?? ''),
            ];
        }

        $payload = [
            'model' => $model,
            'messages' => $formattedMessages,
            'temperature' => $temperature,
        ];

        if (!empty($tools)) {
            $payload['tools'] = array_map(fn(array $tool) => [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => self::normalizeJsonSchema($tool['parameters']),
                ],
            ], $tools);
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->post($endpoint, $payload);
        } catch (Throwable $e) {
            throw NodeExecutionException::because("خطا در ارتباط با سرویس هوش مصنوعی: {$e->getMessage()}");
        }

        if ($response->failed()) {
            throw NodeExecutionException::because(self::describeHttpError(ucfirst($credential->type), $response));
        }

        $message = (array)data_get($response->json(), 'choices.0.message', []);
        $text = trim((string)($message['content'] ?? ''));
        $rawToolCalls = (array)($message['tool_calls'] ?? []);

        $toolCalls = [];
        foreach ($rawToolCalls as $tc) {
            $argsRaw = data_get($tc, 'function.arguments', '{}');
            $decodedArgs = is_string($argsRaw) ? json_decode($argsRaw, true) : $argsRaw;

            $toolCalls[] = [
                'id' => (string)($tc['id'] ?? ('call_' . uniqid())),
                'name' => (string)data_get($tc, 'function.name', ''),
                'args' => is_array($decodedArgs) ? $decodedArgs : [],
            ];
        }

        return [
            'text' => $text,
            'tool_calls' => $toolCalls,
            'raw_assistant_message' => [
                'role' => 'assistant',
                'openai_raw' => $message,
            ],
        ];
    }

    private static function chatAnthropic(
        Credential $credential,
        string     $model,
        array      $messages,
        ?string    $systemPrompt,
        float      $temperature,
        array      $tools,
    ): array
    {
        $apiKey = trim((string)$credential->get('api_key'));
        $endpoint = 'https://api.anthropic.com/v1/messages';

        $formattedMessages = [];

        foreach ($messages as $msg) {
            if (isset($msg['anthropic_raw'])) {
                $formattedMessages[] = $msg['anthropic_raw'];
                continue;
            }

            if ($msg['role'] === 'tool') {
                $formattedMessages[] = [
                    'role' => 'user',
                    'content' => [[
                        'type' => 'tool_result',
                        'tool_use_id' => $msg['tool_call_id'],
                        'content' => is_string($msg['content'])
                            ? $msg['content']
                            : json_encode($msg['content'], JSON_UNESCAPED_UNICODE),
                    ]],
                ];
                continue;
            }

            $formattedMessages[] = [
                'role' => in_array($msg['role'], ['assistant', 'model'], true) ? 'assistant' : 'user',
                'content' => (string)($msg['text'] ?? ''),
            ];
        }

        $payload = [
            'model' => $model,
            'max_tokens' => 4096,
            'messages' => $formattedMessages,
            'temperature' => min(1.0, max(0.0, $temperature)),
        ];

        if ($systemPrompt !== null && $systemPrompt !== '') {
            $payload['system'] = $systemPrompt;
        }

        if (!empty($tools)) {
            $payload['tools'] = array_map(fn(array $tool) => [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'input_schema' => self::normalizeJsonSchema($tool['parameters']),
            ], $tools);
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->timeout(90)->post($endpoint, $payload);
        } catch (Throwable $e) {
            throw NodeExecutionException::because("خطا در ارتباط با سرویس Anthropic: {$e->getMessage()}");
        }

        if ($response->failed()) {
            throw NodeExecutionException::because(self::describeHttpError('Anthropic Claude', $response));
        }

        $contentBlocks = (array)data_get($response->json(), 'content', []);
        $textParts = [];
        $toolCalls = [];

        foreach ($contentBlocks as $block) {
            $type = $block['type'] ?? '';
            if ($type === 'text') {
                $textParts[] = (string)($block['text'] ?? '');
            } elseif ($type === 'tool_use') {
                $toolCalls[] = [
                    'id' => (string)($block['id'] ?? ('toolu_' . uniqid())),
                    'name' => (string)($block['name'] ?? ''),
                    'args' => (array)($block['input'] ?? []),
                ];
            }
        }

        return [
            'text' => trim(implode("\n", $textParts)),
            'tool_calls' => $toolCalls,
            'raw_assistant_message' => [
                'role' => 'assistant',
                'anthropic_raw' => [
                    'role' => 'assistant',
                    'content' => $contentBlocks,
                ],
            ],
        ];
    }

    private static function normalizeJsonSchema(array $schema): array
    {
        $normalized = [];

        foreach ($schema as $key => $value) {
            if ($key === 'type' && is_string($value)) {
                $normalized[$key] = strtolower($value);
            } elseif (is_array($value)) {
                $normalized[$key] = self::normalizeJsonSchema($value);
            } else {
                $normalized[$key] = $value;
            }
        }

        if (($normalized['type'] ?? '') === 'object' && empty($normalized['properties'])) {
            $normalized['properties'] = (object)[];
        }

        return $normalized;
    }

    private static function describeHttpError(string $provider, Response $response): string
    {
        $status = $response->status();
        $message = (string)(
            data_get($response->json(), 'error.message')
            ?? data_get($response->json(), 'message')
            ?? strip_tags($response->body())
        );

        $hint = match ($status) {
            401, 403 => 'کلید API نامعتبر است یا دسترسی لازم را ندارد.',
            402 => 'موجودی یا اعتبار حساب شما در این سرویس کافی نیست.',
            404 => 'مدل انتخاب‌شده یافت نشد.',
            429 => 'سقف درخواست‌ها (Rate Limit) پر شده است. کمی صبر کنید.',
            default => 'درخواست توسط سرویس هوش مصنوعی پذیرفته نشد.',
        };

        return "خطای $provider (HTTP $status): $hint\n" . mb_substr(trim($message), 0, 400);
    }
}

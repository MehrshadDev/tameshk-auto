<?php

namespace App\Workflow\Support;

use App\Models\Credential;
use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
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

    private static function send(
        PendingRequest $request,
        string $endpoint,
        array $payload,
        string $connectionError,
        callable $describeFailure,
    ): array {
        try {
            $response = $request->timeout(90)->post($endpoint, $payload);
        } catch (Throwable $e) {
            throw NodeExecutionException::because("$connectionError: {$e->getMessage()}");
        }

        if ($response->failed()) {
            throw NodeExecutionException::because($describeFailure($response));
        }

        return (array) $response->json();
    }

    private static function toolContent(mixed $content): string
    {
        return is_string($content) ? $content : json_encode($content, JSON_UNESCAPED_UNICODE);
    }

    private static function result(string $text, array $toolCalls, array $rawAssistantMessage): array
    {
        return [
            'text'                  => trim($text),
            'tool_calls'            => $toolCalls,
            'raw_assistant_message' => $rawAssistantMessage,
        ];
    }

    private static function chatGemini(
        Credential $credential,
        string     $model,
        array      $messages,
        ?string    $systemPrompt,
        float      $temperature,
        array      $tools,
    ): array {
        $baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/');
        $prefix = str_ends_with($baseUrl, '/v1beta') ? $baseUrl : "$baseUrl/v1beta";

        $payload = [
            'contents' => array_map(fn (array $msg) => match (true) {
                isset($msg['gemini_raw']) => $msg['gemini_raw'],
                $msg['role'] === 'tool'   => [
                    'role'  => 'user',
                    'parts' => [[
                        'functionResponse' => [
                            'name'     => $msg['name'],
                            'response' => is_array($msg['content']) ? $msg['content'] : ['result' => $msg['content']],
                        ],
                    ]],
                ],
                default => [
                    'role'  => $msg['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => (string) ($msg['text'] ?? '')]],
                ],
            }, $messages),
            'generationConfig' => ['temperature' => $temperature],
        ];

        if (filled($systemPrompt)) {
            $payload['system_instruction'] = ['parts' => [['text' => $systemPrompt]]];
        }

        if (! empty($tools)) {
            $payload['tools'] = [['functionDeclarations' => $tools]];
        }

        $json = self::send(
            Http::withHeaders(['x-goog-api-key' => (string) $credential->get('api_key')]),
            "$prefix/models/$model:generateContent",
            $payload,
            'سرویس Gemini در دسترس نیست',
            fn (Response $response) => GeminiError::describe($response),
        );

        $content = (array) data_get($json, 'candidates.0.content', []);
        $toolCalls = [];
        $texts = [];

        foreach ((array) data_get($content, 'parts', []) as $part) {
            if (isset($part['functionCall'])) {
                $toolCalls[] = [
                    'id'   => 'call_' . uniqid(),
                    'name' => (string) ($part['functionCall']['name'] ?? ''),
                    'args' => (array) ($part['functionCall']['args'] ?? []),
                ];
            }

            if (isset($part['text'])) {
                $texts[] = $part['text'];
            }
        }

        return self::result(implode("\n", $texts), $toolCalls, [
            'role'       => 'assistant',
            'gemini_raw' => $content,
        ]);
    }

    private static function chatOpenAiCompatible(
        Credential $credential,
        string     $model,
        array      $messages,
        ?string    $systemPrompt,
        float      $temperature,
        array      $tools,
        string     $defaultBaseUrl,
    ): array {
        $customBase = trim((string) $credential->get('base_url', ''));
        $baseUrl = rtrim($customBase !== '' ? $customBase : $defaultBaseUrl, '/');

        $history = array_map(fn (array $msg) => match (true) {
            isset($msg['openai_raw'])   => $msg['openai_raw'],
            $msg['role'] === 'tool'     => [
                'role'         => 'tool',
                'tool_call_id' => $msg['tool_call_id'],
                'content'      => self::toolContent($msg['content']),
            ],
            default => [
                'role'    => $msg['role'] === 'model' ? 'assistant' : $msg['role'],
                'content' => (string) ($msg['text'] ?? ''),
            ],
        }, $messages);

        $payload = [
            'model'       => $model,
            'messages'    => filled($systemPrompt)
                ? [['role' => 'system', 'content' => $systemPrompt], ...$history]
                : $history,
            'temperature' => $temperature,
        ];

        if (! empty($tools)) {
            $payload['tools'] = array_map(fn (array $tool) => [
                'type'     => 'function',
                'function' => [
                    'name'        => $tool['name'],
                    'description' => $tool['description'],
                    'parameters'  => self::normalizeJsonSchema($tool['parameters']),
                ],
            ], $tools);
        }

        $json = self::send(
            Http::withToken(trim((string) $credential->get('api_key'))),
            "$baseUrl/chat/completions",
            $payload,
            'خطا در ارتباط با سرویس هوش مصنوعی',
            fn (Response $response) => self::describeHttpError(ucfirst($credential->type), $response),
        );

        $message = (array) data_get($json, 'choices.0.message', []);
        $toolCalls = [];

        foreach ((array) ($message['tool_calls'] ?? []) as $call) {
            $arguments = data_get($call, 'function.arguments', '{}');
            $decoded = is_string($arguments) ? json_decode($arguments, true) : $arguments;

            $toolCalls[] = [
                'id'   => (string) ($call['id'] ?? ('call_' . uniqid())),
                'name' => (string) data_get($call, 'function.name', ''),
                'args' => is_array($decoded) ? $decoded : [],
            ];
        }

        return self::result((string) ($message['content'] ?? ''), $toolCalls, [
            'role'       => 'assistant',
            'openai_raw' => $message,
        ]);
    }

    private static function chatAnthropic(
        Credential $credential,
        string     $model,
        array      $messages,
        ?string    $systemPrompt,
        float      $temperature,
        array      $tools,
    ): array {
        $payload = [
            'model'       => $model,
            'max_tokens'  => 4096,
            'temperature' => min(1.0, max(0.0, $temperature)),
            'messages'    => array_map(fn (array $msg) => match (true) {
                isset($msg['anthropic_raw']) => $msg['anthropic_raw'],
                $msg['role'] === 'tool'      => [
                    'role'    => 'user',
                    'content' => [[
                        'type'        => 'tool_result',
                        'tool_use_id' => $msg['tool_call_id'],
                        'content'     => self::toolContent($msg['content']),
                    ]],
                ],
                default => [
                    'role'    => in_array($msg['role'], ['assistant', 'model'], true) ? 'assistant' : 'user',
                    'content' => (string) ($msg['text'] ?? ''),
                ],
            }, $messages),
        ];

        if (filled($systemPrompt)) {
            $payload['system'] = $systemPrompt;
        }

        if (! empty($tools)) {
            $payload['tools'] = array_map(fn (array $tool) => [
                'name'         => $tool['name'],
                'description'  => $tool['description'],
                'input_schema' => self::normalizeJsonSchema($tool['parameters']),
            ], $tools);
        }

        $json = self::send(
            Http::withHeaders([
                'x-api-key'         => trim((string) $credential->get('api_key')),
                'anthropic-version' => '2023-06-01',
            ]),
            'https://api.anthropic.com/v1/messages',
            $payload,
            'خطا در ارتباط با سرویس Anthropic',
            fn (Response $response) => self::describeHttpError('Anthropic Claude', $response),
        );

        $blocks = (array) data_get($json, 'content', []);
        $toolCalls = [];
        $texts = [];

        foreach ($blocks as $block) {
            match ($block['type'] ?? '') {
                'text'     => $texts[] = (string) ($block['text'] ?? ''),
                'tool_use' => $toolCalls[] = [
                    'id'   => (string) ($block['id'] ?? ('toolu_' . uniqid())),
                    'name' => (string) ($block['name'] ?? ''),
                    'args' => (array) ($block['input'] ?? []),
                ],
                default    => null,
            };
        }

        return self::result(implode("\n", $texts), $toolCalls, [
            'role'          => 'assistant',
            'anthropic_raw' => ['role' => 'assistant', 'content' => $blocks],
        ]);
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

<?php

namespace App\Workflow\Nodes\Ai;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\ToolContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Support\SafeHttpClient;

class HttpToolNode implements NodeContract, ToolContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.http',
            name: 'HTTP Tool',
            category: NodeCategory::Ai,
            description: 'ابزار ارسال درخواست HTTP و فراخوانی وب‌سرویس‌ها توسط ایجنت هوش مصنوعی.',
            icon: 'world',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: [
                'tool_name' => [
                    'type' => 'text',
                    'label' => 'نام ابزار (انگلیسی و شناسه یکتا)',
                    'default' => 'fetch_api_data',
                    'required' => true,
                    'placeholder' => 'fetch_api_data',
                ],
                'tool_description' => [
                    'type' => 'textarea',
                    'label' => 'توضیحات عملکرد ابزار برای مدل',
                    'default' => 'ارسال درخواست HTTP به وب‌سرویس خارجی برای دریافت، جستجو یا ارسال اطلاعات',
                    'required' => true,
                ],
                'base_url' => [
                    'type' => 'text',
                    'label' => 'آدرس URL پایه (اختیاری)',
                    'required' => false,
                    'placeholder' => 'https://api.example.com',
                ],
                'default_method' => [
                    'type' => 'select',
                    'label' => 'متد درخواست',
                    'options' => ['DYNAMIC', 'GET', 'POST', 'PUT', 'DELETE'],
                    'default' => 'DYNAMIC',
                    'required' => true,
                ],
                'headers' => [
                    'type' => 'keyvalue',
                    'label' => 'هدرهای ثابت (Headers)',
                    'default' => [],
                ],
                'timeout' => [
                    'type' => 'number',
                    'label' => 'مهلت زمانی (ثانیه)',
                    'default' => 30,
                    'integer' => true,
                ],
            ],
            color: 'fuchsia',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'fetch_api_data'));
    }

    public function toolDescription(array $config): string
    {
        return trim((string) ($config['tool_description'] ?? 'ارسال درخواست به یک وب‌سرویس اینترنتی'));
    }

    public function toolParameters(array $config): array
    {
        $baseUrl = trim((string) ($config['base_url'] ?? ''));
        $defaultMethod = $config['default_method'] ?? 'DYNAMIC';

        $properties = [];
        $required = [];

        if ($baseUrl === '') {
            $properties['url'] = [
                'type' => 'STRING',
                'description' => 'آدرس کامل اینترنتی مقصد برای ارسال درخواست HTTP',
            ];
            $required[] = 'url';
        } else {
            $properties['endpoint'] = [
                'type' => 'STRING',
                'description' => "مسیر نسبی پس از آدرس {$baseUrl}؛ مثلا: /users یا /search?q=term",
            ];
            $required[] = 'endpoint';
        }

        if ($defaultMethod === 'DYNAMIC') {
            $properties['method'] = [
                'type' => 'STRING',
                'description' => 'متد درخواست شامل GET, POST, PUT, DELETE',
            ];
            $required[] = 'method';
        }

        $properties['body'] = [
            'type' => 'STRING',
            'description' => 'بدنه درخواست به صورت رشته یا آبجکت JSON برای متدهای ارسالی',
        ];

        return [
            'type' => 'OBJECT',
            'properties' => $properties,
            'required' => $required,
        ];
    }

    public function executeTool(NodeContext $context, array $arguments): mixed
    {
        $config = $context->config;
        $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $endpoint = trim((string) ($arguments['endpoint'] ?? ''), '/');

        if ($baseUrl !== '') {
            $url = $endpoint !== '' ? "$baseUrl/$endpoint" : $baseUrl;
        } else {
            $url = trim((string) ($arguments['url'] ?? ''));
        }

        $defaultMethod = $config['default_method'] ?? 'DYNAMIC';
        $method = $defaultMethod === 'DYNAMIC' ? ($arguments['method'] ?? 'GET') : $defaultMethod;

        $headers = $config['headers'] ?? [];
        if (! is_array($headers)) {
            $headers = [];
        }

        $body = $arguments['body'] ?? null;
        if (is_string($body)) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $body = $decoded;
            }
        }

        $timeout = (int) ($config['timeout'] ?? 30);

        $client = new SafeHttpClient();
        $response = $client->execute($method, $url, $headers, $body, $timeout);

        return $response['body'];
    }
}

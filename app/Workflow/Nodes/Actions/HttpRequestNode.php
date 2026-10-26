<?php

namespace App\Workflow\Nodes\Actions;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Support\SafeHttpClient;

class HttpRequestNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'action.http',
            name: 'HTTP Request',
            category: NodeCategory::Action,
            description: 'ارسال درخواست HTTP به هر API یا سرویس خارجی.',
            icon: 'world',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'method' => [
                    'type' => 'select',
                    'label' => 'متد درخواست',
                    'options' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
                    'default' => 'GET',
                    'required' => true,
                ],
                'url' => [
                    'type' => 'text',
                    'label' => 'آدرس URL',
                    'required' => true,
                    'placeholder' => 'https://api.example.com/data',
                ],
                'headers' => [
                    'type' => 'keyvalue',
                    'label' => 'هدرهای درخواست (Headers)',
                    'default' => [],
                ],
                'body' => [
                    'type' => 'code',
                    'label' => 'بدنه درخواست (JSON Body)',
                    'language' => 'json',
                    'default' => null,
                ],
                'timeout' => [
                    'type' => 'number',
                    'label' => 'مهلت زمانی (ثانیه)',
                    'default' => 30,
                    'integer' => true,
                ],
            ],
            color: 'orange',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $method = strtoupper($context->config['method'] ?? 'GET');
        $url = $context->config['url'] ?? '';
        $headers = $context->config['headers'] ?? [];
        $body = $context->config['body'] ?? [];
        $timeout = (int) ($context->config['timeout'] ?? 30);

        $context->log("درخواست $method به $url");

        $client = new SafeHttpClient();
        $response = $client->execute($method, $url, $headers, $body, $timeout);

        return NodeResult::of(
            output: [
                'status' => $response['status'],
                'headers' => $response['headers'],
                'body' => $response['body'],
                'data' => $response['data'],
            ],
            meta: [
                'http_status' => $response['status'],
                'content_type' => $response['content_type'],
                'is_html' => $response['is_html'],
            ],
        );
    }
}

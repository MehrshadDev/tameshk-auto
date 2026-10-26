<?php

namespace App\Workflow\Nodes\Actions;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramSendMessageNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'action.telegram.send_message',
            name: 'Send a text message',
            category: NodeCategory::Action,
            description: 'ارسال پیام متنی به کاربر، گروه یا کانال تلگرام توسط ربات.',
            icon: 'brand:telegram',
            group: 'telegram',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type' => 'credential',
                    'credential_type' => 'telegram',
                    'label' => 'اعتبارنامه ربات تلگرام',
                    'required' => true,
                ],
                'chat_id' => [
                    'type' => 'text',
                    'label' => 'شناسه چت یا کانال (Chat ID)',
                    'required' => true,
                    'placeholder' => 'مثلاً: 123456789 یا channel@ یا {{ input.chat_id }}',
                    'help' => 'شناسه عددی کاربر، گروه یا آیدی کانال تلگرام همراه با علامت @',
                ],
                'text' => [
                    'type' => 'textarea',
                    'label' => 'متن پیام',
                    'required' => true,
                    'placeholder' => 'متن پیام را وارد کنید؛ مثلاً: سلام {{ input.first_name }}!',
                ],
                'parse_mode' => [
                    'type' => 'select',
                    'label' => 'نوع قالب‌بندی متن (Parse Mode)',
                    'options' => [
                        ['value' => 'None', 'label' => 'None (متن ساده)'],
                        ['value' => 'Markdown', 'label' => 'Markdown'],
                        ['value' => 'HTML', 'label' => 'HTML (تگ‌های b, i, code, a)'],
                    ],
                    'default' => 'None',
                ],
                'disable_preview' => [
                    'type' => 'select',
                    'label' => 'پیش‌نمایش لینک‌ها (Link Preview)',
                    'options' => [
                        ['value' => '0', 'label' => 'فعال بودن پیش‌نمایش لینک‌ها'],
                        ['value' => '1', 'label' => 'غیرفعال بودن پیش‌نمایش لینک‌ها'],
                    ],
                    'default' => '0',
                ],
            ],
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $credential = $context->credential();

        if (!$credential) {
            throw NodeExecutionException::because('اعتبارنامه ربات تلگرام انتخاب نشده است.');
        }

        $botToken = trim((string)$credential->get('bot_token'));

        if ($botToken === '') {
            throw NodeExecutionException::because('توکن ربات تلگرام در اعتبارنامه خالی است.');
        }

        $chatId = trim((string)($context->config['chat_id'] ?? ''));

        if ($chatId === '') {
            throw NodeExecutionException::because('شناسه چت مقصد (Chat ID) الزامی است.');
        }

        $text = (string)($context->config['text'] ?? '');

        if (trim($text) === '') {
            throw NodeExecutionException::because('متن پیام ارسالی الزامی است.');
        }

        $parseMode = (string)($context->config['parse_mode'] ?? 'None');
        $disablePreview = ((string)($context->config['disable_preview'] ?? '0')) === '1';

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => $disablePreview,
        ];

        if ($parseMode !== 'None') {
            $payload['parse_mode'] = $parseMode;
        }

        $baseUrl = rtrim((string)config('services.telegram.base_url', 'https://api.telegram.org'), '/');
        $endpoint = "{$baseUrl}/bot{$botToken}/sendMessage";

        $context->log("ارسال پیام تلگرام به چت: {$chatId}");

        try {
            $response = Http::timeout(30)->post($endpoint, $payload);
        } catch (Throwable $e) {
            throw NodeExecutionException::because("خطا در برقراری ارتباط با سرور تلگرام: {$e->getMessage()}");
        }

        if ($response->failed()) {
            $errorDescription = data_get($response->json(), 'description', $response->body());
            throw NodeExecutionException::because("خطای تلگرام: {$errorDescription}");
        }

        $result = data_get($response->json(), 'result', []);

        return NodeResult::of([
            'success' => true,
            'message_id' => data_get($result, 'message_id'),
            'chat' => data_get($result, 'chat'),
            'date' => data_get($result, 'date'),
            'text' => data_get($result, 'text'),
            'raw' => $result,
        ]);
    }
}

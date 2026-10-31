<?php

namespace App\Workflow\Nodes\Ai;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\ToolContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\TelegramApi;
use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramToolNode implements NodeContract, ToolContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.telegram',
            name: 'Telegram Tool',
            category: NodeCategory::Ai,
            description: 'ابزار ارسال پیام، تصویر، فایل و مدیریت چت در تلگرام توسط ایجنت هوش مصنوعی.',
            icon: 'brand:telegram',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'telegram',
                    'label'           => 'اعتبارنامه ربات تلگرام',
                    'required'        => true,
                ],
                'tool_name' => [
                    'type'        => 'text',
                    'label'       => 'نام ابزار (انگلیسی)',
                    'default'     => 'telegram_bot',
                    'required'    => true,
                    'placeholder' => 'telegram_bot',
                ],
                'tool_description' => [
                    'type'     => 'textarea',
                    'label'    => 'توضیحات عملکرد ابزار برای مدل',
                    'default'  => 'ارسال پیام متنی، تصویر، سند یا پین و حذف پیام در چت، گروه یا کانال تلگرام.',
                    'required' => true,
                ],
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات مجاز ابزار',
                    'options'  => [
                        ['value' => 'auto', 'label' => 'همه عملیات‌ها (تشخیص خودکار توسط ایجنت)'],
                        ['value' => 'send_message', 'label' => 'فقط ارسال پیام متنی (Send Message)'],
                        ['value' => 'send_photo', 'label' => 'فقط ارسال تصویر (Send Photo)'],
                        ['value' => 'send_document', 'label' => 'فقط ارسال فایل و سند (Send Document)'],
                    ],
                    'default'  => 'auto',
                    'required' => true,
                ],
                'default_chat_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه چت پیش‌فرض (Chat ID - اختیاری)',
                    'required'    => false,
                    'placeholder' => 'مثلاً {{ input.chat_id }} یا @channel_name',
                    'help'        => 'در صورت پر بودن، اگر ایجنت chat_id ارسال نکند از این شناسه استفاده خواهد شد.',
                ],
                'parse_mode' => [
                    'type'     => 'select',
                    'label'    => 'فرمت متن (Parse Mode)',
                    'options'  => [
                        ['value' => 'None', 'label' => 'متن ساده (None)'],
                        ['value' => 'HTML', 'label' => 'HTML'],
                        ['value' => 'Markdown', 'label' => 'Markdown'],
                    ],
                    'default'  => 'None',
                    'advanced' => true,
                ],
            ],
            color: 'sky',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'telegram_bot'));
    }

    public function toolDescription(array $config): string
    {
        return trim((string) ($config['tool_description'] ?? 'ارسال پیام و رسانه در تلگرام'));
    }

    public function toolParameters(array $config): array
    {
        $operation = (string) ($config['operation'] ?? 'auto');
        $hasDefaultChat = trim((string) ($config['default_chat_id'] ?? '')) !== '';

        $properties = [];
        $required = [];

        if ($operation === 'auto') {
            $properties['action'] = [
                'type'        => 'STRING',
                'description' => 'نوع عملیات تلگرام: "send_message" (ارسال پیام متنی)، "send_photo" (ارسال تصویر)، "send_document" (ارسال فایل)، "pin_message" (پین کردن پیام)، "delete_message" (حذف پیام)',
            ];
            $required[] = 'action';
        }

        $properties['chat_id'] = [
            'type'        => 'STRING',
            'description' => $hasDefaultChat
                ? 'شناسه چت یا کانال تلگرام (اختیاری؛ در صورت خالی بودن به چت پیش‌فرض ارسال می‌شود)'
                : 'شناسه عددی چت یا نام کاربری کانال (مثلاً 123456789 یا @channel)',
        ];

        if (! $hasDefaultChat) {
            $required[] = 'chat_id';
        }

        if (in_array($operation, ['auto', 'send_message'], true)) {
            $properties['text'] = [
                'type'        => 'STRING',
                'description' => 'متن پیام برای ارسال در تلگرام',
            ];

            if ($operation === 'send_message') {
                $required[] = 'text';
            }
        }

        if (in_array($operation, ['auto', 'send_photo', 'send_document'], true)) {
            $properties['media_url'] = [
                'type'        => 'STRING',
                'description' => 'آدرس اینترنتی (URL) یا file_id تصویر یا فایل برای ارسال',
            ];
            $properties['caption'] = [
                'type'        => 'STRING',
                'description' => 'توضیحات (Caption) زیر تصویر یا فایل (اختیاری)',
            ];

            if (in_array($operation, ['send_photo', 'send_document'], true)) {
                $required[] = 'media_url';
            }
        }

        if ($operation === 'auto') {
            $properties['message_id'] = [
                'type'        => 'INTEGER',
                'description' => 'شناسه پیام (Message ID) برای عملیات پین کردن یا حذف پیام',
            ];
        }

        return [
            'type'       => 'OBJECT',
            'properties' => $properties,
            'required'   => $required,
        ];
    }

    public function executeTool(NodeContext $context, array $arguments): mixed
    {
        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه ربات تلگرام برای ابزار Telegram انتخاب نشده است.');
        }

        $botToken = trim((string) $credential->get('bot_token'));

        if ($botToken === '') {
            throw NodeExecutionException::because('توکن ربات تلگرام در اعتبارنامه خالی است.');
        }

        $configuredOp = (string) ($context->config['operation'] ?? 'auto');
        $action = $configuredOp === 'auto'
            ? strtolower(trim((string) ($arguments['action'] ?? 'send_message')))
            : $configuredOp;

        $chatId = trim((string) ($arguments['chat_id'] ?? ''));
        if ($chatId === '') {
            $chatId = trim((string) ($context->config['default_chat_id'] ?? ''));
        }

        if ($chatId === '') {
            throw NodeExecutionException::because('شناسه چت (chat_id) مشخص نشده است.');
        }

        $parseMode = trim((string) ($context->config['parse_mode'] ?? 'None'));
        $extra = ($parseMode !== '' && $parseMode !== 'None') ? ['parse_mode' => $parseMode] : [];

        return match ($action) {
            'send_message' => $this->send($botToken, 'sendMessage', [
                'chat_id' => $chatId,
                'text'    => $this->requireArg($arguments, 'text', 'متن پیام (text) الزامی است.'),
                ...$extra,
            ]),
            'send_photo' => $this->send($botToken, 'sendPhoto', [
                'chat_id' => $chatId,
                'photo'   => $this->requireArg($arguments, 'media_url', 'آدرس تصویر (media_url) الزامی است.'),
                'caption' => (string) ($arguments['caption'] ?? ''),
                ...$extra,
            ]),
            'send_document' => $this->send($botToken, 'sendDocument', [
                'chat_id'  => $chatId,
                'document' => $this->requireArg($arguments, 'media_url', 'آدرس فایل (media_url) الزامی است.'),
                'caption'  => (string) ($arguments['caption'] ?? ''),
                ...$extra,
            ]),
            'pin_message' => $this->send($botToken, 'pinChatMessage', [
                'chat_id'    => $chatId,
                'message_id' => (int) $this->requireArg($arguments, 'message_id', 'شناسه پیام (message_id) الزامی است.'),
            ]),
            'delete_message' => $this->send($botToken, 'deleteMessage', [
                'chat_id'    => $chatId,
                'message_id' => (int) $this->requireArg($arguments, 'message_id', 'شناسه پیام (message_id) الزامی است.'),
            ]),
            default => ['error' => "عملیات ناشناخته تلگرام: $action"],
        };
    }

    private function requireArg(array $arguments, string $key, string $message): string
    {
        $val = trim((string) ($arguments[$key] ?? ''));

        if ($val === '') {
            throw NodeExecutionException::because($message);
        }

        return $val;
    }

    private function send(string $botToken, string $method, array $payload): array
    {
        try {
            $response = Http::timeout(30)->post(TelegramApi::url($botToken, $method), $payload);
        } catch (Throwable $e) {
            throw NodeExecutionException::because("خطا در ارتباط با تلگرام: {$e->getMessage()}");
        }

        if ($response->failed()) {
            $desc = data_get($response->json(), 'description', $response->body());
            throw NodeExecutionException::because("خطای تلگرام: $desc");
        }

        $result = $response->json('result');

        return [
            'success'    => true,
            'method'     => $method,
            'message_id' => is_array($result) ? ($result['message_id'] ?? null) : null,
            'result'     => $result,
        ];
    }
}

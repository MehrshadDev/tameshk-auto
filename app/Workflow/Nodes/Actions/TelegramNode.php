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

class TelegramNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'action.telegram',
            name: 'Telegram',
            category: NodeCategory::Action,
            description: 'ارسال و ویرایش پیام، تصویر، سند، پاسخ به دکمه‌ها و مدیریت چت تلگرام.',
            icon: 'brand:telegram',
            group: 'telegram',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'telegram',
                    'label'           => 'اعتبارنامه ربات تلگرام',
                    'required'        => true,
                ],
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات',
                    'options'  => [
                        ['value' => 'send_message', 'label' => 'ارسال پیام متنی (Send text message)'],
                        ['value' => 'send_photo', 'label' => 'ارسال تصویر (Send photo)'],
                        ['value' => 'send_document', 'label' => 'ارسال فایل یا سند (Send document)'],
                        ['value' => 'edit_message', 'label' => 'ویرایش پیام متنی (Edit text message)'],
                        ['value' => 'delete_message', 'label' => 'حذف پیام (Delete message)'],
                        ['value' => 'answer_callback', 'label' => 'پاسخ به کلیک دکمه (Answer callback query)'],
                        ['value' => 'send_action', 'label' => 'ارسال وضعیت چت (Send chat action)'],
                        ['value' => 'pin_message', 'label' => 'پین کردن پیام (Pin message)'],
                    ],
                    'default'  => 'send_message',
                    'required' => true,
                ],

                'chat_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه چت یا کانال (Chat ID)',
                    'required'    => true,
                    'placeholder' => 'مثلاً: 123456789 یا channel@ یا {{ input.chat_id }}',
                    'depends_on'  => [
                        'operation' => ['send_message', 'send_photo', 'send_document', 'edit_message', 'delete_message', 'send_action', 'pin_message'],
                    ],
                ],
                'text' => [
                    'type'        => 'textarea',
                    'label'       => 'متن پیام',
                    'required'    => true,
                    'placeholder' => 'متن پیام را وارد کنید',
                    'depends_on'  => ['operation' => ['send_message', 'edit_message']],
                ],
                'photo' => [
                    'type'        => 'text',
                    'label'       => 'آدرس یا شناسه تصویر (Photo URL / File ID)',
                    'required'    => true,
                    'placeholder' => 'https://example.com/image.jpg یا file_id تلگرام',
                    'depends_on'  => ['operation' => 'send_photo'],
                ],
                'document' => [
                    'type'        => 'text',
                    'label'       => 'آدرس یا شناسه فایل (Document URL / File ID)',
                    'required'    => true,
                    'placeholder' => 'https://example.com/file.pdf یا file_id تلگرام',
                    'depends_on'  => ['operation' => 'send_document'],
                ],
                'caption' => [
                    'type'        => 'textarea',
                    'label'       => 'توضیحات (Caption)',
                    'placeholder' => 'متن توضیحات همراه با تصویر یا فایل',
                    'depends_on'  => ['operation' => ['send_photo', 'send_document']],
                ],
                'message_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه پیام (Message ID)',
                    'required'    => true,
                    'placeholder' => 'مثلاً: {{ input.message_id }}',
                    'depends_on'  => ['operation' => ['edit_message', 'delete_message', 'pin_message']],
                ],
                'callback_query_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه رویداد کلیک (Callback Query ID)',
                    'required'    => true,
                    'placeholder' => '{{ input.callback_query_id }}',
                    'depends_on'  => ['operation' => 'answer_callback'],
                ],
                'chat_action' => [
                    'type'       => 'select',
                    'label'      => 'نوع وضعیت',
                    'options'    => [
                        ['value' => 'typing', 'label' => 'در حال نوشتن (Typing)'],
                        ['value' => 'upload_photo', 'label' => 'در حال ارسال عکس (Upload Photo)'],
                        ['value' => 'upload_document', 'label' => 'در حال ارسال فایل (Upload Document)'],
                        ['value' => 'record_video', 'label' => 'در حال ضبط ویدیو (Record Video)'],
                        ['value' => 'upload_video', 'label' => 'در حال ارسال ویدیو (Upload Video)'],
                        ['value' => 'record_voice', 'label' => 'در حال ضبط صدا (Record Voice)'],
                    ],
                    'default'    => 'typing',
                    'required'   => true,
                    'depends_on' => ['operation' => 'send_action'],
                ],

                'parse_mode' => [
                    'type'       => 'select',
                    'label'      => 'نوع قالب‌بندی (Parse Mode)',
                    'options'    => [
                        ['value' => 'None', 'label' => 'متن ساده (None)'],
                        ['value' => 'HTML', 'label' => 'HTML'],
                        ['value' => 'Markdown', 'label' => 'Markdown'],
                    ],
                    'default'    => 'None',
                    'advanced'   => true,
                    'depends_on' => ['operation' => ['send_message', 'send_photo', 'send_document', 'edit_message']],
                ],
                'disable_preview' => [
                    'type'       => 'select',
                    'label'      => 'پیش‌نمایش لینک‌ها (Link Preview)',
                    'options'    => [
                        ['value' => '0', 'label' => 'فعال بودن پیش‌نمایش'],
                        ['value' => '1', 'label' => 'غیرفعال بودن پیش‌نمایش'],
                    ],
                    'default'    => '0',
                    'advanced'   => true,
                    'depends_on' => ['operation' => ['send_message', 'edit_message']],
                ],
                'reply_to_message_id' => [
                    'type'        => 'text',
                    'label'       => 'پاسخ به پیام (Reply to Message ID)',
                    'placeholder' => 'شناسه پیامی که می‌خواهید به آن ریپلای شود',
                    'advanced'    => true,
                    'depends_on'  => ['operation' => ['send_message', 'send_photo', 'send_document']],
                ],
                'disable_notification' => [
                    'type'       => 'select',
                    'label'      => 'ارسال بی‌صدا (Silent Notification)',
                    'options'    => [
                        ['value' => '0', 'label' => 'خیر (با اعلان و صدا)'],
                        ['value' => '1', 'label' => 'بله (بدون صدا)'],
                    ],
                    'default'    => '0',
                    'advanced'   => true,
                    'depends_on' => ['operation' => ['send_message', 'send_photo', 'send_document', 'pin_message']],
                ],
                'callback_text' => [
                    'type'        => 'text',
                    'label'       => 'متن پاسخ دکمه (Alert Text)',
                    'placeholder' => 'پیامی که به کاربر نمایش داده شود',
                    'advanced'    => true,
                    'depends_on'  => ['operation' => 'answer_callback'],
                ],
                'show_alert' => [
                    'type'       => 'select',
                    'label'      => 'نمایش به صورت پاپ‌آپ (Show Alert)',
                    'options'    => [
                        ['value' => '0', 'label' => 'خیر (اعلان کوچک بالای صفحه)'],
                        ['value' => '1', 'label' => 'بله (پنجره پاپ‌آپ با دکمه OK)'],
                    ],
                    'default'    => '0',
                    'advanced'   => true,
                    'depends_on' => ['operation' => 'answer_callback'],
                ],
            ],
            presets: [
                [
                    'key'         => 'send_message',
                    'name'        => 'Send a text message',
                    'description' => 'ارسال پیام متنی به کاربر، گروه یا کانال.',
                    'config'      => ['operation' => 'send_message'],
                ],
                [
                    'key'         => 'send_photo',
                    'name'        => 'Send a photo',
                    'description' => 'ارسال تصویر به همراه کپشن به تلگرام.',
                    'config'      => ['operation' => 'send_photo'],
                ],
                [
                    'key'         => 'send_document',
                    'name'        => 'Send a document',
                    'description' => 'ارسال فایل، پی‌دی‌اف یا سند به تلگرام.',
                    'config'      => ['operation' => 'send_document'],
                ],
                [
                    'key'         => 'edit_message',
                    'name'        => 'Edit a message',
                    'description' => 'ویرایش متن پیام قبلی ربات در چت.',
                    'config'      => ['operation' => 'edit_message'],
                ],
                [
                    'key'         => 'delete_message',
                    'name'        => 'Delete a message',
                    'description' => 'حذف پیام از چت تلگرام.',
                    'config'      => ['operation' => 'delete_message'],
                ],
                [
                    'key'         => 'answer_callback',
                    'name'        => 'Answer callback query',
                    'description' => 'پاسخ به کلیک کاربر روی دکمه‌های شیشه‌ای ربات.',
                    'config'      => ['operation' => 'answer_callback'],
                ],
                [
                    'key'         => 'send_action',
                    'name'        => 'Send chat action',
                    'description' => 'ارسال وضعیت تایپ کردن یا آپلود فایل در چت.',
                    'config'      => ['operation' => 'send_action'],
                ],
                [
                    'key'         => 'pin_message',
                    'name'        => 'Pin a message',
                    'description' => 'پین کردن پیام در گروه یا کانال تلگرام.',
                    'config'      => ['operation' => 'pin_message'],
                ],
            ],
            color: 'sky',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه ربات تلگرام انتخاب نشده است.');
        }

        $botToken = trim((string) $credential->get('bot_token'));

        if ($botToken === '') {
            throw NodeExecutionException::because('توکن ربات تلگرام در اعتبارنامه خالی است.');
        }

        $operation = (string) ($context->config['operation'] ?? 'send_message');

        return match ($operation) {
            'send_message'    => $this->handleSendMessage($context, $botToken),
            'send_photo'      => $this->handleSendPhoto($context, $botToken),
            'send_document'   => $this->handleSendDocument($context, $botToken),
            'edit_message'    => $this->handleEditMessage($context, $botToken),
            'delete_message'  => $this->handleDeleteMessage($context, $botToken),
            'answer_callback' => $this->handleAnswerCallback($context, $botToken),
            'send_action'     => $this->handleSendAction($context, $botToken),
            'pin_message'     => $this->handlePinMessage($context, $botToken),
            default           => throw NodeExecutionException::because("عملیات تلگرام نامعتبر است: $operation"),
        };
    }

    private function applyMessageOptions(array $payload, NodeContext $context): array
    {
        $parseMode = (string) ($context->config['parse_mode'] ?? 'None');
        if ($parseMode !== 'None') {
            $payload['parse_mode'] = $parseMode;
        }

        $replyTo = trim((string) ($context->config['reply_to_message_id'] ?? ''));
        if ($replyTo !== '') {
            $payload['reply_to_message_id'] = $replyTo;
        }

        return $payload;
    }

    private function resolveChatAndMessageId(NodeContext $context): array
    {
        $chatId = trim((string) ($context->config['chat_id'] ?? ''));
        $messageId = trim((string) ($context->config['message_id'] ?? ''));

        if ($chatId === '' || $messageId === '') {
            throw NodeExecutionException::because('شناسه چت و شناسه پیام الزامی هستند.');
        }

        return [$chatId, $messageId];
    }

    private function handleSendMessage(NodeContext $context, string $botToken): NodeResult
    {
        $chatId = trim((string) ($context->config['chat_id'] ?? ''));
        $text = (string) ($context->config['text'] ?? '');

        if ($chatId === '') {
            throw NodeExecutionException::because('شناسه چت مقصد (Chat ID) الزامی است.');
        }
        if (trim($text) === '') {
            throw NodeExecutionException::because('متن پیام الزامی است.');
        }

        $payload = [
            'chat_id'                  => $chatId,
            'text'                     => $text,
            'disable_web_page_preview' => ((string) ($context->config['disable_preview'] ?? '0')) === '1',
            'disable_notification'     => ((string) ($context->config['disable_notification'] ?? '0')) === '1',
        ];

        $payload = $this->applyMessageOptions($payload, $context);
        $result = $this->request($botToken, 'sendMessage', $payload);

        return NodeResult::of([
            'success'    => true,
            'message_id' => data_get($result, 'message_id'),
            'chat'       => data_get($result, 'chat'),
            'date'       => data_get($result, 'date'),
            'text'       => data_get($result, 'text'),
            'raw'        => $result,
        ]);
    }

    private function handleSendPhoto(NodeContext $context, string $botToken): NodeResult
    {
        $chatId = trim((string) ($context->config['chat_id'] ?? ''));
        $photo = trim((string) ($context->config['photo'] ?? ''));

        if ($chatId === '') {
            throw NodeExecutionException::because('شناسه چت مقصد الزامی است.');
        }
        if ($photo === '') {
            throw NodeExecutionException::because('آدرس یا شناسه تصویر (Photo) الزامی است.');
        }

        $payload = [
            'chat_id'              => $chatId,
            'photo'                => $photo,
            'caption'              => (string) ($context->config['caption'] ?? ''),
            'disable_notification' => ((string) ($context->config['disable_notification'] ?? '0')) === '1',
        ];

        $payload = $this->applyMessageOptions($payload, $context);
        $result = $this->request($botToken, 'sendPhoto', $payload);

        return NodeResult::of([
            'success'    => true,
            'message_id' => data_get($result, 'message_id'),
            'chat'       => data_get($result, 'chat'),
            'caption'    => data_get($result, 'caption'),
            'raw'        => $result,
        ]);
    }

    private function handleSendDocument(NodeContext $context, string $botToken): NodeResult
    {
        $chatId = trim((string) ($context->config['chat_id'] ?? ''));
        $document = trim((string) ($context->config['document'] ?? ''));

        if ($chatId === '') {
            throw NodeExecutionException::because('شناسه چت مقصد الزامی است.');
        }
        if ($document === '') {
            throw NodeExecutionException::because('آدرس یا شناسه فایل (Document) الزامی است.');
        }

        $payload = [
            'chat_id'              => $chatId,
            'document'             => $document,
            'caption'              => (string) ($context->config['caption'] ?? ''),
            'disable_notification' => ((string) ($context->config['disable_notification'] ?? '0')) === '1',
        ];

        $payload = $this->applyMessageOptions($payload, $context);
        $result = $this->request($botToken, 'sendDocument', $payload);

        return NodeResult::of([
            'success'    => true,
            'message_id' => data_get($result, 'message_id'),
            'chat'       => data_get($result, 'chat'),
            'document'   => data_get($result, 'document'),
            'raw'        => $result,
        ]);
    }

    private function handleEditMessage(NodeContext $context, string $botToken): NodeResult
    {
        [$chatId, $messageId] = $this->resolveChatAndMessageId($context);
        $text = (string) ($context->config['text'] ?? '');

        if (trim($text) === '') {
            throw NodeExecutionException::because('متن جدید پیام الزامی است.');
        }

        $payload = [
            'chat_id'                  => $chatId,
            'message_id'               => $messageId,
            'text'                     => $text,
            'disable_web_page_preview' => ((string) ($context->config['disable_preview'] ?? '0')) === '1',
        ];

        $parseMode = (string) ($context->config['parse_mode'] ?? 'None');
        if ($parseMode !== 'None') {
            $payload['parse_mode'] = $parseMode;
        }

        $result = $this->request($botToken, 'editMessageText', $payload);

        return NodeResult::of([
            'success'    => true,
            'message_id' => data_get($result, 'message_id'),
            'text'       => data_get($result, 'text'),
            'raw'        => $result,
        ]);
    }

    private function handleDeleteMessage(NodeContext $context, string $botToken): NodeResult
    {
        [$chatId, $messageId] = $this->resolveChatAndMessageId($context);

        $this->request($botToken, 'deleteMessage', [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
        ]);

        return NodeResult::of([
            'success'    => true,
            'chat_id'    => $chatId,
            'message_id' => $messageId,
        ]);
    }

    private function handleAnswerCallback(NodeContext $context, string $botToken): NodeResult
    {
        $callbackId = trim((string) ($context->config['callback_query_id'] ?? ''));

        if ($callbackId === '') {
            throw NodeExecutionException::because('شناسه رویداد کلیک (Callback Query ID) الزامی است.');
        }

        $payload = [
            'callback_query_id' => $callbackId,
            'text'              => (string) ($context->config['callback_text'] ?? ''),
            'show_alert'        => ((string) ($context->config['show_alert'] ?? '0')) === '1',
        ];

        $this->request($botToken, 'answerCallbackQuery', $payload);

        return NodeResult::of([
            'success'           => true,
            'callback_query_id' => $callbackId,
        ]);
    }

    private function handleSendAction(NodeContext $context, string $botToken): NodeResult
    {
        $chatId = trim((string) ($context->config['chat_id'] ?? ''));
        $action = (string) ($context->config['chat_action'] ?? 'typing');

        if ($chatId === '') {
            throw NodeExecutionException::because('شناسه چت الزامی است.');
        }

        $this->request($botToken, 'sendChatAction', [
            'chat_id' => $chatId,
            'action'  => $action,
        ]);

        return NodeResult::of([
            'success' => true,
            'chat_id' => $chatId,
            'action'  => $action,
        ]);
    }

    private function handlePinMessage(NodeContext $context, string $botToken): NodeResult
    {
        [$chatId, $messageId] = $this->resolveChatAndMessageId($context);

        $this->request($botToken, 'pinChatMessage', [
            'chat_id'              => $chatId,
            'message_id'           => $messageId,
            'disable_notification' => ((string) ($context->config['disable_notification'] ?? '0')) === '1',
        ]);

        return NodeResult::of([
            'success'    => true,
            'chat_id'    => $chatId,
            'message_id' => $messageId,
        ]);
    }

    private function request(string $botToken, string $method, array $payload): array
    {
        $baseUrl = rtrim((string) config('services.telegram.base_url', 'https://api.telegram.org'), '/');
        $endpoint = "$baseUrl/bot$botToken/$method";

        try {
            $response = Http::timeout(30)->post($endpoint, $payload);
        } catch (Throwable $e) {
            throw NodeExecutionException::because("خطا در ارتباط با سرور تلگرام: {$e->getMessage()}");
        }

        if ($response->failed()) {
            $errorDescription = data_get($response->json(), 'description', $response->body());
            throw NodeExecutionException::because("خطای تلگرام: $errorDescription");
        }

        return (array) data_get($response->json(), 'result', []);
    }
}

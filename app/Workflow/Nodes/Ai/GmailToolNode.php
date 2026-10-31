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
use App\Workflow\Support\GmailClient;
use Throwable;

class GmailToolNode implements NodeContract, ToolContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.gmail',
            name: 'Gmail Tool',
            category: NodeCategory::Ai,
            description: 'ابزار جستجو، خواندن، ارسال و پاسخ به ایمیل‌های جیمیل توسط ایجنت هوش مصنوعی.',
            icon: 'brand:gmail',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'gmail',
                    'label'           => 'حساب جیمیل',
                    'required'        => true,
                ],
                'tool_name' => [
                    'type'        => 'text',
                    'label'       => 'نام ابزار (انگلیسی)',
                    'default'     => 'gmail',
                    'required'    => true,
                    'placeholder' => 'gmail',
                ],
                'tool_description' => [
                    'type'     => 'textarea',
                    'label'    => 'توضیحات عملکرد ابزار برای مدل',
                    'default'  => 'جستجو در صندوق ایمیل، خواندن متن ایمیل‌ها، ارسال ایمیل جدید، پاسخ به ایمیل و تغییر وضعیت ایمیل‌ها در Gmail.',
                    'required' => true,
                ],
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات مجاز ابزار',
                    'options'  => [
                        ['value' => 'auto', 'label' => 'همه عملیات‌ها (تشخیص خودکار توسط ایجنت)'],
                        ['value' => 'search', 'label' => 'فقط جستجو و خواندن ایمیل‌ها (Search Emails)'],
                        ['value' => 'get', 'label' => 'فقط دریافت یک ایمیل با شناسه (Get Email)'],
                        ['value' => 'send', 'label' => 'فقط ارسال ایمیل جدید (Send Email)'],
                        ['value' => 'reply', 'label' => 'فقط پاسخ به ایمیل (Reply to Email)'],
                        ['value' => 'modify', 'label' => 'فقط تغییر وضعیت یا حذف ایمیل (Modify / Trash)'],
                    ],
                    'default'  => 'auto',
                    'required' => true,
                ],
                'limit' => [
                    'type'     => 'number',
                    'label'    => 'حداکثر تعداد نتایج جستجو',
                    'default'  => 5,
                    'min'      => 1,
                    'max'      => 20,
                    'integer'  => true,
                    'advanced' => true,
                ],
                'is_html' => [
                    'type'     => 'select',
                    'label'    => 'قالب ارسال ایمیل',
                    'options'  => [
                        ['value' => '0', 'label' => 'متن ساده (Plain Text)'],
                        ['value' => '1', 'label' => 'کد HTML'],
                    ],
                    'default'  => '0',
                    'advanced' => true,
                ],
            ],
            color: 'rose',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'gmail'));
    }

    public function toolDescription(array $config): string
    {
        return trim((string) ($config['tool_description'] ?? 'مدیریت، جستجو و ارسال ایمیل در Gmail'));
    }

    public function toolParameters(array $config): array
    {
        $operation = (string) ($config['operation'] ?? 'auto');
        $properties = [];
        $required = [];

        if ($operation === 'auto') {
            $properties['action'] = [
                'type'        => 'STRING',
                'description' => 'نوع عملیات: "search" (جستجوی ایمیل‌ها)، "get" (دریافت کامل یک ایمیل)، "send" (ارسال ایمیل جدید)، "reply" (پاسخ به یک ایمیل)، "modify" (تغییر برچسب یا انتقال به سطل زباله)',
            ];
            $required[] = 'action';
        }

        if (in_array($operation, ['auto', 'search'], true)) {
            $properties['query'] = [
                'type'        => 'STRING',
                'description' => 'عبارت جستجوی استاندارد جیمیل (مثلاً "is:unread" یا "from:ali@gmail.com" یا "subject:فاکتور")',
            ];
        }

        if (in_array($operation, ['auto', 'get', 'reply', 'modify'], true)) {
            $properties['message_id'] = [
                'type'        => 'STRING',
                'description' => 'شناسه یکتای ایمیل (Message ID) برای خواندن، پاسخ دادن یا تغییر وضعیت',
            ];

            if (in_array($operation, ['get', 'reply', 'modify'], true)) {
                $required[] = 'message_id';
            }
        }

        if (in_array($operation, ['auto', 'send'], true)) {
            $properties['to'] = [
                'type'        => 'STRING',
                'description' => 'آدرس ایمیل گیرنده (To)',
            ];
            $properties['subject'] = [
                'type'        => 'STRING',
                'description' => 'موضوع ایمیل (Subject)',
            ];
            $properties['cc'] = [
                'type'        => 'STRING',
                'description' => 'آدرس‌های رونوشت (CC) جداشده با کاما (اختیاری)',
            ];

            if ($operation === 'send') {
                $required[] = 'to';
                $required[] = 'subject';
            }
        }

        if (in_array($operation, ['auto', 'send', 'reply'], true)) {
            $properties['body'] = [
                'type'        => 'STRING',
                'description' => 'متن کامل ایمیل یا پاسخ ارسالی',
            ];

            if (in_array($operation, ['send', 'reply'], true)) {
                $required[] = 'body';
            }
        }

        if (in_array($operation, ['auto', 'modify'], true)) {
            $properties['modify_action'] = [
                'type'        => 'STRING',
                'description' => 'نوع تغییر وضعیت ایمیل: "mark_read" (خوانده‌شده)، "mark_unread" (خوانده‌نشده)، "star" (ستاره‌دار)، "unstar" (حذف ستاره)، "trash" (انتقال به سطل زباله)',
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
            throw NodeExecutionException::because('اعتبارنامه حساب جیمیل برای ابزار Gmail انتخاب نشده است.');
        }

        $client = new GmailClient($credential);
        $configuredOp = (string) ($context->config['operation'] ?? 'auto');
        $action = $configuredOp === 'auto'
            ? strtolower(trim((string) ($arguments['action'] ?? 'search')))
            : $configuredOp;

        return match ($action) {
            'search', 'list' => $this->handleSearch($context, $client, $arguments),
            'get', 'read'    => $this->handleGet($client, $arguments),
            'send'           => $this->handleSend($context, $client, $arguments),
            'reply'          => $this->handleReply($context, $client, $arguments),
            'modify', 'trash'=> $this->handleModify($client, $arguments, $action),
            default          => ['error' => "عملیات ناشناخته: $action. مقادیر مجاز: search, get, send, reply, modify"],
        };
    }

    private function handleSearch(NodeContext $context, GmailClient $client, array $arguments): array
    {
        $query = trim((string) ($arguments['query'] ?? 'is:unread'));
        $limit = max(1, min(20, (int) ($context->config['limit'] ?? 5)));

        $response = $client->listMessages($query, $limit);
        $rawMessages = (array) ($response['messages'] ?? []);
        $items = [];

        foreach (array_slice($rawMessages, 0, $limit) as $item) {
            $id = (string) ($item['id'] ?? '');
            if ($id === '') {
                continue;
            }

            try {
                $full = $client->getMessage($id);
                $items[] = [
                    'id'       => $full['id'] ?? $id,
                    'threadId' => $full['threadId'] ?? '',
                    'from'     => $full['from'] ?? '',
                    'to'       => $full['to'] ?? '',
                    'subject'  => $full['subject'] ?? '',
                    'date'     => $full['date'] ?? '',
                    'snippet'  => $full['snippet'] ?? '',
                    'body'     => mb_substr((string) ($full['text'] ?? ''), 0, 1500),
                ];
            } catch (Throwable) {
                $items[] = $item;
            }
        }

        return [
            'query'    => $query,
            'count'    => count($items),
            'messages' => $items,
        ];
    }

    private function handleGet(GmailClient $client, array $arguments): array
    {
        $messageId = trim((string) ($arguments['message_id'] ?? ''));

        if ($messageId === '') {
            throw NodeExecutionException::because('شناسه ایمیل (message_id) الزامی است.');
        }

        $msg = $client->getMessage($messageId);

        return [
            'id'       => $msg['id'] ?? $messageId,
            'threadId' => $msg['threadId'] ?? '',
            'from'     => $msg['from'] ?? '',
            'to'       => $msg['to'] ?? '',
            'cc'       => $msg['cc'] ?? '',
            'subject'  => $msg['subject'] ?? '',
            'date'     => $msg['date'] ?? '',
            'text'     => mb_substr((string) ($msg['text'] ?? ''), 0, 8000),
        ];
    }

    private function handleSend(NodeContext $context, GmailClient $client, array $arguments): array
    {
        $to = trim((string) ($arguments['to'] ?? ''));
        $subject = trim((string) ($arguments['subject'] ?? ''));
        $body = (string) ($arguments['body'] ?? '');
        $isHtml = ((string) ($context->config['is_html'] ?? '0')) === '1';

        if ($to === '' || $subject === '') {
            throw NodeExecutionException::because('فیلدهای گیرنده (to) و موضوع (subject) برای ارسال ایمیل الزامی هستند.');
        }

        $ccRaw = trim((string) ($arguments['cc'] ?? ''));
        $cc = $ccRaw !== '' ? array_map('trim', explode(',', $ccRaw)) : [];

        $result = $client->sendMessage(
            to: $to,
            subject: $subject,
            body: $body,
            isHtml: $isHtml,
            cc: $cc,
        );

        return [
            'success'  => true,
            'id'       => $result['id'] ?? '',
            'threadId' => $result['threadId'] ?? '',
            'to'       => $to,
            'subject'  => $subject,
        ];
    }

    private function handleReply(NodeContext $context, GmailClient $client, array $arguments): array
    {
        $messageId = trim((string) ($arguments['message_id'] ?? ''));
        $body = (string) ($arguments['body'] ?? '');
        $isHtml = ((string) ($context->config['is_html'] ?? '0')) === '1';

        if ($messageId === '' || trim($body) === '') {
            throw NodeExecutionException::because('برای پاسخ به ایمیل، فیلدهای message_id و body الزامی هستند.');
        }

        $original = $client->getMessage($messageId);
        $to = (string) ($original['from'] ?? '');

        if ($to === '') {
            throw NodeExecutionException::because('آدرس فرستنده ایمیل مرجع یافت نشد.');
        }

        $subject = (string) ($original['subject'] ?? '');
        if (! str_starts_with(strtolower($subject), 're:')) {
            $subject = 'Re: ' . $subject;
        }

        $origMsgId = (string) ($original['messageId'] ?? '');
        $origRef = (string) ($original['references'] ?? '');
        $references = trim(($origRef !== '' ? $origRef . ' ' : '') . $origMsgId);

        $result = $client->sendMessage(
            to: $to,
            subject: $subject,
            body: $body,
            isHtml: $isHtml,
            threadId: $original['threadId'] ?? null,
            inReplyTo: $origMsgId !== '' ? $origMsgId : null,
            references: $references !== '' ? $references : null,
        );

        return [
            'success'             => true,
            'id'                  => $result['id'] ?? '',
            'threadId'            => $result['threadId'] ?? '',
            'to'                  => $to,
            'subject'             => $subject,
            'original_message_id' => $messageId,
        ];
    }

    private function handleModify(GmailClient $client, array $arguments, string $fallbackAction): array
    {
        $messageId = trim((string) ($arguments['message_id'] ?? ''));

        if ($messageId === '') {
            throw NodeExecutionException::because('شناسه ایمیل (message_id) الزامی است.');
        }

        $modifyAction = strtolower(trim((string) ($arguments['modify_action'] ?? ($fallbackAction === 'trash' ? 'trash' : 'mark_read'))));

        if ($modifyAction === 'trash') {
            $res = $client->trashMessage($messageId);
            return [
                'success' => true,
                'id'      => $res['id'] ?? $messageId,
                'action'  => 'trash',
            ];
        }

        $add = [];
        $remove = [];

        match ($modifyAction) {
            'mark_read'   => $remove[] = 'UNREAD',
            'mark_unread' => $add[] = 'UNREAD',
            'star'        => $add[] = 'STARRED',
            'unstar'      => $remove[] = 'STARRED',
            default       => throw NodeExecutionException::because("عملیات تغییر وضعیت نامعتبر است: $modifyAction"),
        };

        $res = $client->modifyMessage($messageId, $add, $remove);

        return [
            'success' => true,
            'id'      => $res['id'] ?? $messageId,
            'action'  => $modifyAction,
        ];
    }
}

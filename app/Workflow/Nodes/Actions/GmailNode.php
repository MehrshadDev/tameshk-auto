<?php

namespace App\Workflow\Nodes\Actions;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\GmailClient;
use Throwable;

class GmailNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'action.gmail',
            name: 'Gmail',
            category: NodeCategory::Action,
            description: 'ارسال، پاسخ، جستجو و مدیریت ایمیل‌های حساب گوگل جیمیل.',
            icon: 'brand:gmail',
            group: 'gmail',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'gmail',
                    'label'           => 'حساب جیمیل',
                    'required'        => true,
                ],
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات',
                    'options'  => [
                        ['value' => 'send', 'label' => 'ارسال ایمیل (Send Message)'],
                        ['value' => 'reply', 'label' => 'پاسخ به ایمیل (Reply to Message)'],
                        ['value' => 'get', 'label' => 'دریافت ایمیل (Get Message)'],
                        ['value' => 'list', 'label' => 'جستجو در ایمیل‌ها (Search Messages)'],
                        ['value' => 'modify', 'label' => 'تغییر برچسب‌ها (Modify Labels)'],
                        ['value' => 'trash', 'label' => 'انتقال به سطل زباله (Trash Message)'],
                    ],
                    'default'  => 'send',
                    'required' => true,
                ],

                'to' => [
                    'type'        => 'text',
                    'label'       => 'گیرنده (To)',
                    'required'    => true,
                    'placeholder' => 'example@gmail.com یا {{ input.email }}',
                    'depends_on'  => ['operation' => 'send'],
                ],
                'subject' => [
                    'type'        => 'text',
                    'label'       => 'موضوع ایمیل (Subject)',
                    'required'    => true,
                    'placeholder' => 'موضوع را وارد کنید',
                    'depends_on'  => ['operation' => 'send'],
                ],
                'body' => [
                    'type'        => 'textarea',
                    'label'       => 'متن ایمیل (Body)',
                    'required'    => true,
                    'placeholder' => 'متن پیام را وارد کنید',
                    'depends_on'  => ['operation' => ['send', 'reply']],
                ],

                'message_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه ایمیل (Message ID)',
                    'required'    => true,
                    'placeholder' => 'مثلاً {{ input.id }}',
                    'depends_on'  => ['operation' => ['reply', 'get', 'modify', 'trash']],
                ],

                'query' => [
                    'type'        => 'text',
                    'label'       => 'عبارت جستجو (Query)',
                    'default'     => 'is:unread',
                    'placeholder' => 'مثلاً is:unread label:INBOX',
                    'depends_on'  => ['operation' => 'list'],
                ],

                'modify_action' => [
                    'type'       => 'select',
                    'label'      => 'عملیات تغییر وضعیت',
                    'options'    => [
                        ['value' => 'mark_read', 'label' => 'علامت‌گذاری به عنوان خوانده‌شده'],
                        ['value' => 'mark_unread', 'label' => 'علامت‌گذاری به عنوان خوانده‌نشده'],
                        ['value' => 'star', 'label' => 'افزودن ستاره (Star)'],
                        ['value' => 'unstar', 'label' => 'حذف ستاره (Unstar)'],
                    ],
                    'default'    => 'mark_read',
                    'depends_on' => ['operation' => 'modify'],
                ],

                'is_html' => [
                    'type'       => 'select',
                    'label'      => 'قالب‌بندی به صورت HTML',
                    'options'    => [
                        ['value' => '0', 'label' => 'متن ساده (Plain Text)'],
                        ['value' => '1', 'label' => 'کد HTML'],
                    ],
                    'default'    => '0',
                    'advanced'   => true,
                    'depends_on' => ['operation' => ['send', 'reply']],
                ],
                'cc' => [
                    'type'        => 'text',
                    'label'       => 'رونوشت (CC)',
                    'placeholder' => 'cc@example.com (با کاما جدا کنید)',
                    'advanced'    => true,
                    'depends_on'  => ['operation' => 'send'],
                ],
                'bcc' => [
                    'type'        => 'text',
                    'label'       => 'رونوشت مخفی (BCC)',
                    'placeholder' => 'bcc@example.com (با کاما جدا کنید)',
                    'advanced'    => true,
                    'depends_on'  => ['operation' => 'send'],
                ],
                'limit' => [
                    'type'       => 'number',
                    'label'      => 'حداکثر تعداد نتایج',
                    'default'    => 5,
                    'min'        => 1,
                    'max'        => 50,
                    'integer'    => true,
                    'advanced'   => true,
                    'depends_on' => ['operation' => 'list'],
                ],
            ],
            presets: [
                [
                    'key'         => 'send_email',
                    'name'        => 'Send an email',
                    'description' => 'ارسال ایمیل جدید از حساب جیمیل به گیرندگان.',
                    'config'      => ['operation' => 'send'],
                ],
                [
                    'key'         => 'reply_email',
                    'name'        => 'Reply to email',
                    'description' => 'ارسال پاسخ خودکار به ایمیل با حفظ زنجیره گفتگو (Thread).',
                    'config'      => ['operation' => 'reply'],
                ],
                [
                    'key'         => 'get_email',
                    'name'        => 'Get an email',
                    'description' => 'دریافت متن و جزئیات یک ایمیل با شناسه آن.',
                    'config'      => ['operation' => 'get'],
                ],
                [
                    'key'         => 'search_emails',
                    'name'        => 'Search emails',
                    'description' => 'جستجو در صندوق ایمیل بر اساس فیلترها و کلمات کلیدی.',
                    'config'      => ['operation' => 'list'],
                ],
                [
                    'key'         => 'modify_email',
                    'name'        => 'Modify labels',
                    'description' => 'تغییر وضعیت خوانده‌شده یا برچسب‌های ایمیل.',
                    'config'      => ['operation' => 'modify'],
                ],
                [
                    'key'         => 'trash_email',
                    'name'        => 'Move to trash',
                    'description' => 'انتقال ایمیل مشخص‌شده به سطل زباله.',
                    'config'      => ['operation' => 'trash'],
                ],
            ],
            color: 'rose',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه حساب جیمیل انتخاب نشده است.');
        }

        $operation = (string) ($context->config['operation'] ?? 'send');
        $client = new GmailClient($credential);

        return match ($operation) {
            'send'   => $this->handleSend($context, $client),
            'reply'  => $this->handleReply($context, $client),
            'get'    => $this->handleGet($context, $client),
            'list'   => $this->handleList($context, $client),
            'modify' => $this->handleModify($context, $client),
            'trash'  => $this->handleTrash($context, $client),
            default  => throw NodeExecutionException::because("عملیات نامعتبر است: $operation"),
        };
    }

    private function handleSend(NodeContext $context, GmailClient $client): NodeResult
    {
        $to = trim((string) ($context->config['to'] ?? ''));
        $subject = trim((string) ($context->config['subject'] ?? ''));
        $body = (string) ($context->config['body'] ?? '');
        $isHtml = ((string) ($context->config['is_html'] ?? '0')) === '1';

        if ($to === '') {
            throw NodeExecutionException::because('ایمیل گیرنده (To) الزامی است.');
        }

        if ($subject === '') {
            throw NodeExecutionException::because('موضوع ایمیل (Subject) الزامی است.');
        }

        $ccRaw = trim((string) ($context->config['cc'] ?? ''));
        $cc = $ccRaw !== '' ? array_map('trim', explode(',', $ccRaw)) : [];

        $bccRaw = trim((string) ($context->config['bcc'] ?? ''));
        $bcc = $bccRaw !== '' ? array_map('trim', explode(',', $bccRaw)) : [];

        $context->log("ارسال ایمیل به: $to");

        $result = $client->sendMessage(
            to: $to,
            subject: $subject,
            body: $body,
            isHtml: $isHtml,
            cc: $cc,
            bcc: $bcc
        );

        return NodeResult::of([
            'id'       => $result['id'] ?? '',
            'threadId' => $result['threadId'] ?? '',
            'to'       => $to,
            'subject'  => $subject,
        ]);
    }

    private function handleReply(NodeContext $context, GmailClient $client): NodeResult
    {
        $messageId = trim((string) ($context->config['message_id'] ?? ''));
        $body = (string) ($context->config['body'] ?? '');
        $isHtml = ((string) ($context->config['is_html'] ?? '0')) === '1';

        if ($messageId === '') {
            throw NodeExecutionException::because('شناسه ایمیل مرجع (Message ID) الزامی است.');
        }

        if (trim($body) === '') {
            throw NodeExecutionException::because('متن پاسخ نمی‌تواند خالی باشد.');
        }

        $original = $client->getMessage($messageId);
        $to = $original['from'];

        if (empty($to)) {
            throw NodeExecutionException::because('فرستنده ایمیل مرجع برای ارسال پاسخ پیدا نشد.');
        }

        $subject = $original['subject'];
        if (! str_starts_with(strtolower($subject), 're:')) {
            $subject = 'Re: ' . $subject;
        }

        $threadId = $original['threadId'];
        $origMsgId = $original['messageId'];
        $origRef = $original['references'];
        $references = trim(($origRef ? $origRef . ' ' : '') . $origMsgId);

        $context->log("ارسال پاسخ به: $to");

        $result = $client->sendMessage(
            to: $to,
            subject: $subject,
            body: $body,
            isHtml: $isHtml,
            threadId: $threadId,
            inReplyTo: $origMsgId,
            references: $references
        );

        return NodeResult::of([
            'id'                  => $result['id'] ?? '',
            'threadId'            => $result['threadId'] ?? '',
            'to'                  => $to,
            'subject'             => $subject,
            'original_message_id' => $messageId,
        ]);
    }

    private function handleGet(NodeContext $context, GmailClient $client): NodeResult
    {
        $messageId = trim((string) ($context->config['message_id'] ?? ''));

        if ($messageId === '') {
            throw NodeExecutionException::because('شناسه ایمیل (Message ID) الزامی است.');
        }

        $context->log("دریافت ایمیل: $messageId");

        return NodeResult::of($client->getMessage($messageId));
    }

    private function handleList(NodeContext $context, GmailClient $client): NodeResult
    {
        $query = (string) ($context->config['query'] ?? 'is:unread');
        $limit = (int) ($context->config['limit'] ?? 5);

        $context->log("جستجوی ایمیل‌ها: $query");

        $response = $client->listMessages($query, $limit);
        $rawMessages = (array) ($response['messages'] ?? []);
        $items = [];

        foreach (array_slice($rawMessages, 0, $limit) as $item) {
            $id = (string) ($item['id'] ?? '');
            if ($id === '') {
                continue;
            }

            try {
                $items[] = $client->getMessage($id);
            } catch (Throwable) {
                $items[] = $item;
            }
        }

        return NodeResult::of([
            'count'    => count($items),
            'query'    => $query,
            'messages' => $items,
            'first'    => $items[0] ?? null,
        ]);
    }

    private function handleModify(NodeContext $context, GmailClient $client): NodeResult
    {
        $messageId = trim((string) ($context->config['message_id'] ?? ''));
        $action = (string) ($context->config['modify_action'] ?? 'mark_read');

        if ($messageId === '') {
            throw NodeExecutionException::because('شناسه ایمیل الزامی است.');
        }

        $add = [];
        $remove = [];

        match ($action) {
            'mark_read'   => $remove[] = 'UNREAD',
            'mark_unread' => $add[] = 'UNREAD',
            'star'        => $add[] = 'STARRED',
            'unstar'      => $remove[] = 'STARRED',
            default       => null,
        };

        $context->log("تغییر برچسب ایمیل: $messageId");
        $result = $client->modifyMessage($messageId, $add, $remove);

        return NodeResult::of([
            'id'       => $result['id'] ?? $messageId,
            'threadId' => $result['threadId'] ?? '',
            'action'   => $action,
        ]);
    }

    private function handleTrash(NodeContext $context, GmailClient $client): NodeResult
    {
        $messageId = trim((string) ($context->config['message_id'] ?? ''));

        if ($messageId === '') {
            throw NodeExecutionException::because('شناسه ایمیل الزامی است.');
        }

        $context->log("انتقال ایمیل به سطل زباله: $messageId");
        $result = $client->trashMessage($messageId);

        return NodeResult::of([
            'success'  => true,
            'id'       => $result['id'] ?? $messageId,
            'threadId' => $result['threadId'] ?? '',
        ]);
    }
}

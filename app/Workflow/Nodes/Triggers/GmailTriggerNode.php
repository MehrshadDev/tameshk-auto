<?php

namespace App\Workflow\Nodes\Triggers;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\PublishableTriggerContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\GmailClient;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GmailTriggerNode implements NodeContract, PublishableTriggerContract
{
    private const LISTEN_SECONDS = 60;
    private const POLL_INTERVAL = 3;

    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'trigger.gmail',
            name: 'On email received',
            category: NodeCategory::Trigger,
            description: 'آغاز خودکار ورکفلو با دریافت ایمیل جدید در صندوق پستی جیمیل.',
            icon: 'brand:gmail',
            group: 'gmail',
            inputs: [],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'gmail',
                    'label'           => 'حساب جیمیل',
                    'required'        => true,
                ],
                'query' => [
                    'type'        => 'text',
                    'label'       => 'فیلتر پیام‌ها (Query)',
                    'default'     => 'is:unread label:INBOX',
                    'placeholder' => 'مثلاً is:unread label:INBOX from:google.com',
                    'required'    => true,
                ],
                'mark_read' => [
                    'type'       => 'select',
                    'label'      => 'علامت‌گذاری خودکار به عنوان خوانده‌شده',
                    'options'    => [
                        ['value' => '0', 'label' => 'خیر (دست‌نخورده باقی بماند)'],
                        ['value' => '1', 'label' => 'بله (پس از پردازش، خوانده‌شده شود)'],
                    ],
                    'default'    => '0',
                    'advanced'   => true,
                ],
            ],
            color: 'rose',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $input = is_array($context->input) ? $context->input : [];

        if (isset($input['id']) && isset($input['subject'])) {
            return $this->formatResult($input);
        }

        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('برای تست این مرحله، انتخاب اعتبارنامه حساب جیمیل الزامی است.');
        }

        $query = (string) ($context->config['query'] ?? 'is:unread label:INBOX');
        $autoMarkRead = ((string) ($context->config['mark_read'] ?? '0')) === '1';

        $client = new GmailClient($credential);
        $cancelKey = "cancel_workflow_run_{$context->run->id}";
        $deadline = microtime(true) + self::LISTEN_SECONDS;

        $context->log("در حال بررسی و انتظار برای دریافت ایمیل جدید (فیلتر: {$query})...");

        try {
            while (microtime(true) < $deadline) {
                if (Cache::get($cancelKey)) {
                    throw NodeExecutionException::because('انتظار برای دریافت ایمیل متوقف شد.');
                }

                $list = $client->listMessages($query, 1);
                $messages = (array) ($list['messages'] ?? []);

                if (! empty($messages[0]['id'])) {
                    $messageId = (string) $messages[0]['id'];
                    $fullMessage = $client->getMessage($messageId);

                    if ($autoMarkRead) {
                        try {
                            $client->modifyMessage($messageId, [], ['UNREAD']);
                        } catch (Throwable) {
                        }
                    }

                    return $this->formatResult($fullMessage);
                }

                sleep(self::POLL_INTERVAL);
            }

            throw NodeExecutionException::because('در مدت ۶۰ ثانیه هیچ ایمیلی متناسب با فیلتر یافت نشد (Timeout). روی دکمه تست کلیک کرده و یک ایمیل جدید به این حساب ارسال کنید.');
        } catch (NodeExecutionException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw NodeExecutionException::because('خطا در بررسی جیمیل: ' . $e->getMessage());
        }
    }

    private function formatResult(array $msg): NodeResult
    {
        $text = (string) ($msg['text'] ?? $msg['snippet'] ?? '');

        return NodeResult::of([
            'id'        => $msg['id'] ?? '',
            'threadId'  => $msg['threadId'] ?? '',
            'from'      => $msg['from'] ?? '',
            'to'        => $msg['to'] ?? '',
            'subject'   => $msg['subject'] ?? '',
            'date'      => $msg['date'] ?? '',
            'snippet'   => $msg['snippet'] ?? '',
            'text'      => $text,
            'html'      => $msg['html'] ?? '',
            'chatInput' => $text,
            'raw'       => $msg['raw'] ?? $msg,
        ]);
    }
}

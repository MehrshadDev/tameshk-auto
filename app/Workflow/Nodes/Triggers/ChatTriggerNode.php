<?php

namespace App\Workflow\Nodes\Triggers;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use Illuminate\Support\Str;
use App\Workflow\Contracts\PublishableTriggerContract;

class ChatTriggerNode implements NodeContract, PublishableTriggerContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'trigger.chat',
            name: 'On Chat Message',
            category: NodeCategory::Trigger,
            description: 'اجرای ورکفلو هنگام ارسال پیام در چت. پیام کاربر به عنوان خروجی این نود در اختیار نودهای بعدی قرار می‌گیرد.',
            icon: 'message-circle',
            inputs: [],
            outputs: ['main'],
            configSchema: [
                'title' => [
                    'type'     => 'text',
                    'label'    => 'عنوان چت',
                    'default'  => 'گفتگو',
                    'required' => false,
                ],
                'input_placeholder' => [
                    'type'     => 'text',
                    'label'    => 'متن راهنمای ورودی پیام',
                    'default'  => 'پیام خود را بنویسید...',
                    'required' => false,
                ],
                'initial_message' => [
                    'type'     => 'textarea',
                    'label'    => 'پیام خوشامدگویی',
                    'default'  => 'سلام! چطور می‌تونم کمکتون کنم؟',
                    'help'     => 'این پیام هنگام باز شدن صفحه‌ی چت عمومی به کاربر نمایش داده می‌شود.',
                    'required' => false,
                ],
            ],
            color: 'teal',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $input = is_array($context->input) ? $context->input : [];

        $chatInput = trim((string) ($input['chatInput'] ?? ''));
        $sessionId = trim((string) ($input['sessionId'] ?? ''));

        if ($sessionId === '') {
            $sessionId = (string) Str::uuid();
        }

        $context->log('دریافت پیام چت');

        return NodeResult::of([
            'sessionId' => mb_substr($sessionId, 0, 100),
            'action'    => 'sendMessage',
            'chatInput' => mb_substr($chatInput, 0, 10000),
        ]);
    }
}

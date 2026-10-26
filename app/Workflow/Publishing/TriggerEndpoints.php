<?php

namespace App\Workflow\Publishing;

use App\Models\Workflow;

final class TriggerEndpoints
{
    public function base(): string
    {
        return rtrim((string) (config('workflow.webhook_base_url') ?: config('app.url')), '/');
    }

    public function chat(Workflow $workflow): string
    {
        return $this->base() . route('webhooks.chat.show', ['token' => $workflow->webhook_token], false);
    }

    public function telegram(Workflow $workflow): string
    {
        return $this->base() . route('webhooks.telegram', ['token' => $workflow->webhook_token], false);
    }

    public function forWorkflow(Workflow $workflow): array
    {
        return [
            'trigger.chat' => [
                [
                    'key' => 'chat',
                    'label' => 'آدرس چت عمومی',
                    'mode' => 'production',
                    'url' => $this->chat($workflow),
                    'hint' => 'این آدرس را می‌توانید در سایت، اپلیکیشن یا هر جای دیگری استفاده کنید.',
                ],
            ],
        ];
    }
}

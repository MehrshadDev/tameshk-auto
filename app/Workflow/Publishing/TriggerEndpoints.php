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

    public function webhookTest(Workflow $workflow): string
    {
        return $this->base() . route('webhooks.custom.test', ['path' => $workflow->webhook_token], false);
    }

    public function webhookProduction(Workflow $workflow): string
    {
        return $this->base() . route('webhooks.custom.production', ['path' => $workflow->webhook_token], false);
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
            'trigger.webhook' => [
                [
                    'key' => 'webhook_test',
                    'label' => 'Test URL',
                    'mode' => 'test',
                    'url' => $this->webhookTest($workflow),
                    'hint' => 'روی دکمه «گوش دادن به رویداد تستی» کلیک کنید و درخواست خود را به این آدرس بفرستید.',
                ],
                [
                    'key' => 'webhook_production',
                    'label' => 'Production URL',
                    'mode' => 'production',
                    'url' => $this->webhookProduction($workflow),
                    'hint' => 'پس از انتشار ورکفلو، درخواست‌های واقعی را بدون نیاز به کلیک روی دکمه تست به این آدرس بفرستید.',
                ],
            ],
        ];
    }
}

<?php

namespace App\Workflow\Contracts;

use App\Models\Credential;

interface WebhookTriggerContract extends PublishableTriggerContract
{
    public function registerWebhook(Credential $credential, string $url, string $secret, array $config = []): void;

    public function unregisterWebhook(Credential $credential): void;
}

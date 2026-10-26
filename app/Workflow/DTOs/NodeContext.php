<?php

namespace App\Workflow\DTOs;

use App\Credentials\CredentialResolver;
use App\Models\Credential;
use App\Models\Node;
use App\Models\NodeRun;
use App\Models\WorkflowRun;
use Illuminate\Support\Facades\Log;

final class NodeContext
{
    /** @var callable|null */
    public readonly mixed $onProgress;

    public function __construct(
        public readonly Node $node,
        public readonly array $config,
        public readonly mixed $input,
        public readonly WorkflowRun $run,
        public readonly array $vars = [],
        private readonly ?CredentialResolver $credentials = null,
        ?callable $onProgress = null,
    ) {
        $this->onProgress = $onProgress;
    }

    public function log(string $message, array $context = []): void
    {
        $payload = [
            ...$context,
            'run_id' => $this->run->id,
        ];

        if (config()->has('logging.channels.workflow')) {
            Log::channel('workflow')->info("[node:{$this->node->id}] {$message}", $payload);
        } else {
            Log::info("[node:{$this->node->id}] {$message}", $payload);
        }
    }

    public function credential(?int $id = null): ?Credential
    {
        if (! $this->credentials) {
            return null;
        }

        $id ??= $this->config['credential_id'] ?? null;

        if (! $id) {
            return null;
        }

        $credential = $this->credentials->find((int) $id, $this->run->workflow->user_id);

        $credential?->markAsUsed();

        return $credential;
    }

    public function emitStatus(int $nodeId, string $status, ?NodeRun $nodeRun = null): void
    {
        if (is_callable($this->onProgress)) {
            ($this->onProgress)($nodeId, $status, $nodeRun);
        }
    }
}

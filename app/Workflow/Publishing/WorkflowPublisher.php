<?php

namespace App\Workflow\Publishing;

use App\Models\Credential;
use App\Models\Node;
use App\Models\Workflow;
use App\Workflow\Contracts\PublishableTriggerContract;
use App\Workflow\Contracts\WebhookTriggerContract;
use App\Workflow\Enums\HandleType;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Exceptions\WorkflowPublishException;
use App\Workflow\Graph\NodeConfigValidator;
use App\Workflow\Registry\NodeRegistry;
use Throwable;

final class WorkflowPublisher
{
    public function __construct(
        private readonly NodeRegistry $registry,
        private readonly NodeConfigValidator $validator,
        private readonly TriggerEndpoints $endpoints,
    ) {}

    public static function secretFor(Workflow $workflow): string
    {
        return hash_hmac('sha256', (string) $workflow->webhook_token, (string) config('app.key'));
    }

    public function publish(Workflow $workflow): void
    {
        $this->activateTrigger($workflow);

        $workflow->update([
            'published_at' => $workflow->published_at ?? now(),
            'is_active' => true,
        ]);
    }

    public function unpublish(Workflow $workflow): void
    {
        $this->deactivateTrigger($workflow);

        $workflow->update([
            'published_at' => null,
            'is_active' => false,
        ]);
    }

    public function setActive(Workflow $workflow, bool $active): void
    {
        if (! $workflow->isPublished()) {
            throw WorkflowPublishException::because('این ورکفلو هنوز منتشر نشده است.');
        }

        if ($active) {
            $this->activateTrigger($workflow);
        } else {
            $this->deactivateTrigger($workflow);
        }

        $workflow->timestamps = false;
        $workflow->update(['is_active' => $active]);
        $workflow->timestamps = true;
    }

    public function sync(Workflow $workflow): void
    {
        if ($workflow->isPublished() && $workflow->is_active) {
            $this->activateTrigger($workflow);
        }
    }

    private function findTrigger(Workflow $workflow): ?Node
    {
        return $workflow->nodes->first(fn (Node $node) => str_starts_with($node->type, 'trigger.'));
    }

    private function resolveTrigger(Workflow $workflow): Node
    {
        $trigger = $this->findTrigger($workflow);

        if (! $trigger) {
            throw WorkflowPublishException::because('ورکفلو نود شروع‌کننده ندارد. یک شروع‌کننده اضافه کنید.');
        }

        if (! $this->registry->has($trigger->type) || ! $this->registry->make($trigger->type) instanceof PublishableTriggerContract) {
            throw WorkflowPublishException::because(
                "شروع‌کننده «{$trigger->name}» فقط در محیط تست کار می‌کند و قابل انتشار نیست."
            );
        }

        return $trigger;
    }

    private function activateTrigger(Workflow $workflow): void
    {
        $workflow->load('nodes', 'edges');

        $trigger = $this->resolveTrigger($workflow);
        $executor = $this->registry->make($trigger->type);

        $this->assertValid($workflow, $trigger);

        if (! $executor instanceof WebhookTriggerContract) {
            return;
        }

        $this->assertCredentialFree($workflow, $trigger);

        $credential = $this->credentialFor($workflow, $trigger);

        if (! $credential) {
            throw WorkflowPublishException::because("نود «{$trigger->name}»: اعتبارنامه انتخاب‌شده پیدا نشد.");
        }

        try {
            $executor->registerWebhook(
                $credential,
                $this->endpoints->telegram($workflow),
                self::secretFor($workflow),
                $trigger->config ?? [],
            );
        } catch (NodeExecutionException $e) {
            throw WorkflowPublishException::because($e->getMessage());
        } catch (Throwable $e) {
            report($e);

            throw WorkflowPublishException::because('ثبت وبهوک با یک خطای غیرمنتظره روبه‌رو شد.');
        }
    }

    private function deactivateTrigger(Workflow $workflow): void
    {
        $workflow->load('nodes');

        $trigger = $this->findTrigger($workflow);

        if (! $trigger || ! $this->registry->has($trigger->type)) {
            return;
        }

        $executor = $this->registry->make($trigger->type);

        if (! $executor instanceof WebhookTriggerContract) {
            return;
        }

        $credential = $this->credentialFor($workflow, $trigger);

        if (! $credential) {
            return;
        }

        try {
            $executor->unregisterWebhook($credential);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function assertValid(Workflow $workflow, Node $trigger): void
    {
        $definitions = $this->registry->definitions();
        $credentials = $workflow->user->credentials()->pluck('type', 'id');
        $errors = [];

        foreach ($workflow->nodes as $node) {
            if ($node->type === 'sticky_note' || ! empty($node->config['disabled'])) {
                continue;
            }

            $definition = $definitions[$node->type] ?? null;

            if (! $definition) {
                $errors[] = "نود «{$node->name}»: نوع نود نامعتبر است.";
                continue;
            }

            array_push($errors, ...$this->validator->nodeMessages($node, $definition, $credentials));
        }

        if (! $workflow->edges->contains(fn ($edge) => $edge->source_node_id === $trigger->id)) {
            $errors[] = "شروع‌کننده «{$trigger->name}» به هیچ نودی متصل نیست.";
        }

        foreach ($workflow->nodes->where('type', 'ai.agent') as $agent) {
            if (! empty($agent->config['disabled'])) {
                continue;
            }

            if (! $this->supplyNode($workflow, $agent, HandleType::ChatModel->value)) {
                $errors[] = "نود «{$agent->name}»: به هیچ مدل چتی متصل نیست.";
            }
        }

        if ($errors !== []) {
            throw WorkflowPublishException::because($errors);
        }
    }

    private function assertCredentialFree(Workflow $workflow, Node $trigger): void
    {
        $credentialId = (int) ($trigger->config['credential_id'] ?? 0);

        $taken = Node::query()
            ->where('type', $trigger->type)
            ->where('config->credential_id', $credentialId)
            ->whereHas('workflow', fn ($query) => $query
                ->whereKeyNot($workflow->id)
                ->whereNotNull('published_at')
                ->where('is_active', true))
            ->exists();

        if ($taken) {
            throw WorkflowPublishException::because(
                'این ربات هم‌اکنون در ورکفلوی منتشرشده‌ی دیگری استفاده می‌شود. هر ربات تلگرام فقط یک وبهوک می‌تواند داشته باشد.'
            );
        }
    }

    private function supplyNode(Workflow $workflow, Node $host, string $handle): ?Node
    {
        $edge = $workflow->edges->first(
            fn ($edge) => $edge->target_node_id === $host->id && $edge->target_handle === $handle
        );

        $node = $edge ? $workflow->nodes->firstWhere('id', $edge->source_node_id) : null;

        return $node && empty($node->config['disabled']) ? $node : null;
    }

    private function credentialFor(Workflow $workflow, Node $trigger): ?Credential
    {
        $id = (int) ($trigger->config['credential_id'] ?? 0);

        return $id > 0 ? $workflow->user->credentials()->find($id) : null;
    }
}

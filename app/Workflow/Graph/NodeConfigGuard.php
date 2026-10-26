<?php

namespace App\Workflow\Graph;

use App\Models\Node;
use App\Models\WorkflowRun;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Registry\NodeRegistry;

final class NodeConfigGuard
{
    public function __construct(
        private readonly NodeRegistry $registry,
        private readonly NodeConfigValidator $validator,
    ) {}

    public function assertComplete(Node $node, WorkflowRun $run): void
    {
        $definition = $this->registry->resolve($node->type)::definition();
        $credentials = $run->workflow->user->credentials()->pluck('type', 'id');
        $errors = $this->validator->errors($definition, $node->config ?? [], $credentials);

        if ($errors === []) {
            return;
        }

        $lines = [];

        foreach ($errors as $key => $message) {
            $label = $definition->configSchema[$key]['label'] ?? $key;
            $lines[] = "نود «{$node->name}» ← فیلد «{$label}»: {$message}";
        }

        throw NodeExecutionException::because(implode("\n", $lines));
    }
}

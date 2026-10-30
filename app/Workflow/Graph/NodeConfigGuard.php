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
        $messages = $this->validator->nodeMessages($node, $definition, $credentials);

        if ($messages !== []) {
            throw NodeExecutionException::because(implode("\n", $messages));
        }
    }
}

<?php

namespace App\Workflow\Runner;

use App\Models\Node;
use App\Models\Workflow;
use Illuminate\Support\Collection;

class WorkflowGraph
{
    public function __construct(
        private readonly Workflow $workflow,
    ) {}

    /**
     * @return Collection<int, Node>
     */
    public function triggerNodes(): Collection
    {
        return $this->workflow->nodes
            ->filter(function (Node $node) {
                return str_starts_with($node->type, 'trigger.');
            })
            ->values();
    }

    public function outgoingEdges(int $nodeId): Collection
    {
        return $this->workflow->edges
            ->where('source_node_id', $nodeId)
            ->values();
    }

    public function incomingEdges(int $nodeId): Collection
    {
        return $this->workflow->edges
            ->where('target_node_id', $nodeId)
            ->values();
    }
}

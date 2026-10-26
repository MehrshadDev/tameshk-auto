<?php

namespace App\Workflow\Runner;

use App\Models\Node;
use App\Models\NodeRun;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Registry\NodeRegistry;
use App\Workflow\Support\TemplateEngine;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use App\Credentials\CredentialResolver;
use App\Workflow\Graph\NodeConfigGuard;

class WorkflowRunner
{
    private const MAX_STEPS = 500;
    private const MAX_NODE_ITERATIONS = 50;

    private ?NodeRun $lastExecutedNodeRun = null;


    public function __construct(
        private readonly NodeRegistry $registry,
        private readonly CredentialResolver $credentials,
        private readonly NodeConfigGuard $guard,
    ) {}

    public function run(Workflow $workflow, array $triggerData = [], ?callable $onNodeStatus = null): WorkflowRun
    {
        $workflow->load('nodes', 'edges');

        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'trigger_type' => $workflow->trigger_type,
            'trigger_data' => $triggerData,
            'started_at' => now(),
        ]);

        try {
            $this->executeGraph($workflow, $run, $triggerData, $onNodeStatus);

            $run->refresh();

            if ($run->status === 'running') {
                $run->update([
                    'status' => 'success',
                    'finished_at' => now(),
                ]);
            }
        } catch (Throwable $e) {
            $run->refresh();

            if ($run->status === 'canceled') {
                return $run->fresh(['nodeRuns']);
            }

            $run->update([
                'status' => 'failed',
                'error' => $this->resolveSafeErrorMessage($e),
                'finished_at' => now(),
            ]);

            Log::error('Workflow execution aborted: ' . $e->getMessage(), [
                'workflow_id' => $workflow->id,
                'run_id' => $run->id,
                'exception' => get_class($e),
            ]);

            throw $e;
        }

        return $run->fresh(['nodeRuns']);
    }

    public function runSingleNode(Workflow $workflow, Node $node): NodeRun
    {
        $workflow->load('nodes', 'edges');

        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'trigger_type' => 'single_node',
            'started_at' => now(),
        ]);

        $this->lastExecutedNodeRun = null;
        $graph = new WorkflowGraph($workflow);
        $input = $this->resolveInputForNode($workflow, $graph, $node);

        try {
            $this->executeNode($node, $input, $run);
            $run->update(['status' => 'success', 'finished_at' => now()]);
        } catch (Throwable $e) {
            $run->refresh();

            if ($run->status !== 'canceled') {
                $run->update([
                    'status' => 'failed',
                    'error' => $this->resolveSafeErrorMessage($e),
                    'finished_at' => now(),
                ]);
            }

            Log::error("Single node [{$node->id}] execution failed: " . $e->getMessage(), [
                'exception' => get_class($e),
            ]);
        }

        if ($this->lastExecutedNodeRun) {
            return $this->lastExecutedNodeRun;
        }

        return NodeRun::create([
            'workflow_run_id' => $run->id,
            'node_id' => $node->id,
            'status' => 'failed',
            'error' => 'Node execution failed unexpectedly.',
            'duration_ms' => 0,
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }

    private function resolveInputForNode(Workflow $workflow, WorkflowGraph $graph, Node $node): mixed
    {
        try {
            $incomingEdge = $graph->incomingEdges($node->id)->first();

            if (! $incomingEdge) {
                return [];
            }

            $sourceNode = $workflow->nodes->firstWhere('id', $incomingEdge->source_node_id);

            if (! $sourceNode) {
                return [];
            }

            $lastRunId = NodeRun::query()
                ->where('node_id', $sourceNode->id)
                ->where('status', 'success')
                ->max('id');

            if (! $lastRunId) {
                return [];
            }

            $lastSourceRun = NodeRun::query()->find($lastRunId);

            return $lastSourceRun?->output_data ?? [];
        } catch (Throwable) {
            return [];
        }
    }

    private function executeGraph(Workflow $workflow, WorkflowRun $run, array $input, ?callable $onNodeStatus = null): void
    {
        $graph = new WorkflowGraph($workflow);

        $triggerNodes = $graph->triggerNodes();

        if ($triggerNodes->isEmpty()) {
            throw new RuntimeException('Workflow has no trigger node.');
        }

        $queue = $triggerNodes
            ->map(fn (Node $node) => [$node, $input])
            ->all();

        $nodeIterations = [];
        $steps = 0;

        while (! empty($queue)) {
            if (Cache::get("cancel_workflow_run_{$run->id}")) {
                $run->update(['status' => 'canceled', 'finished_at' => now()]);
                return;
            }

            if (++$steps > self::MAX_STEPS) {
                throw new RuntimeException('Workflow step limit exceeded (500 steps).');
            }

            [$node, $inputData] = array_shift($queue);

            $nodeIterations[$node->id] = ($nodeIterations[$node->id] ?? 0) + 1;

            if ($nodeIterations[$node->id] > self::MAX_NODE_ITERATIONS) {
                throw new RuntimeException("Node [{$node->name}] exceeded loop iteration limit (" . self::MAX_NODE_ITERATIONS . ").");
            }

            if ($onNodeStatus) {
                $onNodeStatus($node->id, 'running');
            }

            try {
                $result = $this->executeNode($node, $inputData, $run, $onNodeStatus);

                if ($onNodeStatus && $this->lastExecutedNodeRun) {
                    $onNodeStatus($node->id, 'node_success', $this->lastExecutedNodeRun);
                }
            } catch (Throwable $e) {
                if ($onNodeStatus) {
                    $onNodeStatus($node->id, 'node_failed', $this->lastExecutedNodeRun);
                }
                throw $e;
            }

            $nextEdges = $graph->outgoingEdges($node->id)->filter(
                fn ($edge) => ($edge->source_handle ?? HandleType::Main->value) === $result->handle
            );

            foreach ($nextEdges as $edge) {
                $target = $workflow->nodes->firstWhere('id', $edge->target_node_id);

                if ($target) {
                    $queue[] = [$target, $result->output];
                }
            }
        }
    }

    private function executeNode(Node $node, mixed $input, WorkflowRun $run, ?callable $onNodeStatus = null): NodeResult
    {
        if (! empty($node->config['disabled']) && ! str_starts_with($node->type, 'trigger.')) {
            $nodeRun = NodeRun::create([
                'workflow_run_id' => $run->id,
                'node_id' => $node->id,
                'status' => 'success',
                'input_data' => $input,
                'output_data' => $input,
                'duration_ms' => 0,
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            $this->lastExecutedNodeRun = $nodeRun;

            return NodeResult::of($input);
        }

        $nodeRun = NodeRun::create([
            'workflow_run_id' => $run->id,
            'node_id' => $node->id,
            'status' => 'running',
            'input_data' => $input,
            'started_at' => now(),
        ]);

        $start = microtime(true);

        try {
            $this->guard->assertComplete($node, $run);
            $executor = $this->registry->make($node->type);

            $resolvedConfig = TemplateEngine::render($node->config ?? [], [
                'input' => $input,
            ]);

            $context = new NodeContext(
                node: $node,
                config: $resolvedConfig,
                input: $input,
                run: $run,
                credentials: $this->credentials,
                onProgress: $onNodeStatus,
            );

            $result = $executor->execute($context);

            $duration = max(0, (int) round((microtime(true) - $start) * 1000));

            $nodeRun->update([
                'status' => 'success',
                'output_data' => $result->output,
                'duration_ms' => $duration,
                'finished_at' => now(),
            ]);

            $this->lastExecutedNodeRun = $nodeRun->fresh();

            return $result;
        } catch (Throwable $e) {
            $duration = max(0, (int) round((microtime(true) - $start) * 1000));

            $nodeRun->update([
                'status' => 'failed',
                'error' => $this->resolveSafeErrorMessage($e),
                'duration_ms' => $duration,
                'finished_at' => now(),
            ]);

            $this->lastExecutedNodeRun = $nodeRun->fresh();

            if (! $e instanceof NodeExecutionException) {
                report($e);
            }

            throw $e;
        }
    }

    private function resolveSafeErrorMessage(Throwable $e): string
    {
        if ($e instanceof NodeExecutionException) {
            return strip_tags($e->getMessage());
        }

        if (config('app.debug')) {
            return 'System error: ' . strip_tags($e->getMessage());
        }

        return 'خطای داخلی سیستم رخ داد. لطفاً دوباره تلاش کنید.';
    }
}

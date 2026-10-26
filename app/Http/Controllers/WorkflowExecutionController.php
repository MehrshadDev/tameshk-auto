<?php

namespace App\Http\Controllers;

use App\Models\Node;
use App\Models\NodeRun;
use App\Models\Workflow;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Runner\WorkflowRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class WorkflowExecutionController extends Controller
{
    public function run(Request $request, Workflow $workflow, WorkflowRunner $runner): JsonResponse|StreamedResponse
    {
        Gate::authorize('update', $workflow);

        if ($request->boolean('stream')) {
            return response()->stream(function () use ($workflow, $runner, $request) {
                try {
                    $run = $runner->run($workflow, $request->input('data', []), function ($nodeId, $status, $nodeRun = null) {
                        $payload = [
                            'event' => $status,
                            'node_id' => $nodeId,
                        ];

                        if ($nodeRun) {
                            $payload['node_run'] = $nodeRun;
                        }

                        echo "data: " . json_encode($payload) . "\n\n";

                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    });

                    echo "data: " . json_encode(['event' => 'completed', 'run' => $run->load('nodeRuns')]) . "\n\n";
                } catch (Throwable $e) {
                    $latest = $workflow->runs()->latest()->with('nodeRuns')->first();
                    echo "data: " . json_encode(['event' => 'failed', 'error' => $this->safeErrorMessage($e), 'run' => $latest]) . "\n\n";
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        try {
            $run = $runner->run($workflow, $request->input('data', []));
        } catch (Throwable) {
            $run = $workflow->runs()->latest()->with('nodeRuns')->first();
        }

        return response()->json([
            'run' => $run?->load('nodeRuns'),
        ]);
    }

    public function runNode(Workflow $workflow, Node $node, WorkflowRunner $runner): JsonResponse
    {
        Gate::authorize('update', $workflow);

        abort_if($node->workflow_id !== $workflow->id, 404);

        try {
            $nodeRun = $runner->runSingleNode($workflow, $node);
        } catch (Throwable $e) {
            Log::error("Workflow single node execution failed: " . $e->getMessage(), [
                'node_id' => $node->id,
            ]);

            $lastRunId = $workflow->runs()->latest()->value('id');

            if (! $lastRunId) {
                $createdRun = $workflow->runs()->create([
                    'status' => 'failed',
                    'trigger_type' => 'single_node',
                    'started_at' => now(),
                    'finished_at' => now(),
                ]);
                $lastRunId = $createdRun->id;
            }

            $nodeRun = NodeRun::create([
                'workflow_run_id' => $lastRunId,
                'node_id' => $node->id,
                'status' => 'failed',
                'error' => $this->safeErrorMessage($e),
                'duration_ms' => 0,
                'started_at' => now(),
                'finished_at' => now(),
            ]);
        }

        $workflowRun = $nodeRun?->workflowRun?->load('nodeRuns');

        return response()->json([
            'node_run' => $nodeRun,
            'run' => $workflowRun,
        ]);
    }

    public function cancel(Workflow $workflow): JsonResponse
    {
        Gate::authorize('update', $workflow);

        $runningRun = $workflow->runs()->where('status', 'running')->latest()->first();

        if ($runningRun) {
            Cache::put("cancel_workflow_run_$runningRun->id", true, 120);
            $runningRun->update([
                'status' => 'canceled',
                'finished_at' => now(),
            ]);
        }

        return response()->json(['status' => 'canceled']);
    }

    private function safeErrorMessage(Throwable $e): string
    {
        if ($e instanceof NodeExecutionException) {
            return strip_tags($e->getMessage());
        }

        if (config('app.debug') && $e->getMessage()) {
            return strip_tags($e->getMessage());
        }

        return 'خطای داخلی سیستم رخ داد. لطفاً دوباره تلاش کنید.';
    }
}

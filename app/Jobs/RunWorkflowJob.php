<?php

namespace App\Jobs;

use App\Models\Workflow;
use App\Workflow\Runner\WorkflowRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RunWorkflowJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public readonly int $workflowId,
        public readonly array $triggerData,
    ) {}

    public static function dispatchSmart(int $workflowId, array $triggerData): void
    {
        if (config('queue.default') === 'sync') {
            self::dispatchAfterResponse($workflowId, $triggerData);

            return;
        }

        self::dispatch($workflowId, $triggerData);
    }

    public function handle(WorkflowRunner $runner): void
    {
        $workflow = Workflow::query()->find($this->workflowId);

        if (! $workflow || ! $workflow->isPublished() || ! $workflow->is_active) {
            return;
        }

        try {
            $runner->run($workflow, $this->triggerData);
        } catch (Throwable) {
        }
    }
}

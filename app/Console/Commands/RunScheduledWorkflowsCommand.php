<?php

namespace App\Console\Commands;

use App\Jobs\RunWorkflowJob;
use App\Models\Node;
use App\Workflow\Nodes\Triggers\ScheduleTriggerNode;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RunScheduledWorkflowsCommand extends Command
{
    protected $signature = 'workflow:run-scheduled';

    protected $description = 'Check and dispatch published workflows with Schedule Trigger that are due.';

    public function handle(): int
    {
        $now = Carbon::now();
        $minuteKey = $now->format('YmdHi');

        $triggers = Node::query()
            ->where('type', 'trigger.schedule')
            ->whereHas('workflow', fn ($query) => $query
                ->whereNotNull('published_at')
                ->where('is_active', true))
            ->with('workflow')
            ->get();

        $dispatched = 0;

        foreach ($triggers as $trigger) {
            $workflow = $trigger->workflow;

            if (! $workflow) {
                continue;
            }

            $config = $trigger->config ?? [];

            if (! ScheduleTriggerNode::isDue($config, $now)) {
                continue;
            }

            $lockKey = "schedule_trigger_ran_{$workflow->id}_$minuteKey";

            if (! Cache::add($lockKey, true, now()->addMinutes(2))) {
                continue;
            }

            RunWorkflowJob::dispatch(
                $workflow->id,
                ScheduleTriggerNode::buildPayload($config),
            );

            $dispatched++;
        }

        $this->info("Dispatched $dispatched scheduled workflows.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Workflow;
use App\Workflow\Runner\WorkflowRunner;
use Illuminate\Console\Command;

class RunWorkflowCommand extends Command
{
    protected $signature = 'workflow:run {id : Workflow ID} {--payload= : Input JSON}';

    protected $description = 'Run a workflow manually';

    public function handle(WorkflowRunner $runner): int
    {
        $workflow = Workflow::with('nodes', 'edges')->find($this->argument('id'));

        if (! $workflow) {
            $this->error("Workflow #{$this->argument('id')} was not found.");
            return self::FAILURE;
        }

        $payload = json_decode($this->option('payload') ?? '{}', true) ?? [];

        $this->info("🚀 Executing workflow: $workflow->name");
        $this->line('   Number of nodes: ' . $workflow->nodes->count());
        $this->line('   Number of edges: ' . $workflow->edges->count());
        $this->newLine();

        try {
            $run = $runner->run($workflow, $payload);

            $this->info("✅ Execution succeeded. Run #$run->id");
            $this->line("   Status: $run->status");
            $this->line("   Duration: {$run->durationInSeconds()} seconds");

            foreach ($run->nodeRuns as $nodeRun) {
                $icon = $nodeRun->status === 'success' ? '✓' : '✗';
                $this->line("   $icon node#$nodeRun->node_id ($nodeRun->status) {$nodeRun->duration_ms}ms");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Execution failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}

<?php

namespace App\Models;

use App\Workflow\Enums\HandleType;
use App\Workflow\Registry\NodeRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'status',
        'trigger_type',
        'trigger_data',
        'output',
        'error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'trigger_data' => 'array',
            'output'       => 'array',
            'started_at'   => 'datetime',
            'finished_at'  => 'datetime',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function nodeRuns(): HasMany
    {
        return $this->hasMany(NodeRun::class);
    }

    public function lastFlowNodeRun(?Workflow $workflow = null): ?NodeRun
    {
        $workflow ??= $this->workflow()->with('nodes')->first();
        $definitions = app(NodeRegistry::class)->definitions();

        $supplyNodeIds = $workflow
            ? $workflow->nodes
                ->filter(function (Node $node) use ($definitions) {
                    $definition = $definitions[$node->type] ?? null;

                    return $definition
                        && $definition->inputs === []
                        && $definition->outputs !== []
                        && collect($definition->outputs)->every(fn ($handle) => HandleType::isSpecial($handle));
                })
                ->pluck('id')
                ->all()
            : [];

        $lastId = $this->nodeRuns()
            ->whereNotIn('node_id', $supplyNodeIds)
            ->max('id');

        return $lastId ? NodeRun::query()->find($lastId) : null;
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['success', 'failed', 'canceled']);
    }

    public function durationInSeconds(): ?float
    {
        if (! $this->started_at || ! $this->finished_at) {
            return null;
        }

        return round(abs($this->finished_at->diffInMilliseconds($this->started_at)) / 1000, 2);
    }
}

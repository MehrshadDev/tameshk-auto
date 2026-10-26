<?php

namespace App\Models;

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

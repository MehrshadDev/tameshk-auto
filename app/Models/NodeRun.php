<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NodeRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_run_id',
        'node_id',
        'status',
        'input_data',
        'output_data',
        'error',
        'duration_ms',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'input_data'  => 'array',
            'output_data' => 'array',
            'started_at'  => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function workflowRun(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Node extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'type',
        'name',
        'position_x',
        'position_y',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'position_x' => 'float',
            'position_y' => 'float',
            'config'     => 'array',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function outgoingEdges(): HasMany
    {
        return $this->hasMany(Edge::class, 'source_node_id');
    }

    public function incomingEdges(): HasMany
    {
        return $this->hasMany(Edge::class, 'target_node_id');
    }
}

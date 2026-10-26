<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Workflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_active',
        'published_at',
        'webhook_token',
        'trigger_type',
        'settings',
        'status',
        'started_at',
        'finished_at',
    ];

    protected $hidden = [
        'webhook_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Workflow $workflow) {
            $workflow->public_id ??= Str::random(16);
            $workflow->webhook_token ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    public function edges(): HasMany
    {
        return $this->hasMany(Edge::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function latestRun(): HasOne
    {
        return $this->hasOne(WorkflowRun::class)->latestOfMany();
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function editorSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string)$term);

        return $query->when($term !== '', function (Builder $query) use ($term) {
            $like = '%' . addcslashes(mb_substr($term, 0, 100), '%_\\') . '%';

            $query->where(function (Builder $query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        });
    }
}

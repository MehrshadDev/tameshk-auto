<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'data',
        'last_used_at',
    ];

    protected $hidden = [
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'encrypted:array',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->data;

        if (! is_array($data)) {
            return $default;
        }

        return $data[$key] ?? $default;
    }

    public function markAsUsed(): void
    {
        $this->updateQuietly(['last_used_at' => now()]);
    }
}

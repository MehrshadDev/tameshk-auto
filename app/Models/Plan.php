<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'monthly_price',
        'yearly_price',
        'max_monthly_runs',
        'max_active_workflows',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'integer',
            'yearly_price' => 'integer',
            'max_monthly_runs' => 'integer',
            'max_active_workflows' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getFeaturesAttribute(mixed $value): array
    {
        $configured = collect(config('saas.plans', []))->firstWhere('slug', $this->slug);

        if (is_array($configured['features'] ?? null)) {
            return $configured['features'];
        }

        if (is_array($value)) {
            return $value;
        }

        return is_string($value) ? (json_decode($value, true) ?: []) : [];
    }

    public function getDescriptionAttribute(?string $value): ?string
    {
        $configured = collect(config('saas.plans', []))->firstWhere('slug', $this->slug);

        return $configured['description'] ?? $value;
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function isFree(): bool
    {
        return $this->slug === 'free' || ($this->monthly_price === 0 && $this->yearly_price === 0);
    }

    public function priceFor(string $billingCycle): int
    {
        return $billingCycle === 'yearly' ? $this->yearly_price : $this->monthly_price;
    }

    public function hasUnlimitedActiveWorkflows(): bool
    {
        return $this->max_active_workflows === null;
    }

    public function hasUnlimitedMonthlyRuns(): bool
    {
        return $this->max_monthly_runs === null;
    }
}

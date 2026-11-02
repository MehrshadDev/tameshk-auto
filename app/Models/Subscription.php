<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'billing_cycle',
        'status',
        'starts_at',
        'ends_at',
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public static function graceDays(): int
    {
        return max(0, (int) config('saas.grace_period_days', 3));
    }

    public static function renewalWindowDays(): int
    {
        return max(1, (int) config('saas.renewal_window_days', 10));
    }

    public function totalCycleDays(): int
    {
        return $this->billing_cycle === 'yearly' ? 365 : 30;
    }

    public function graceEndsAt(): ?Carbon
    {
        if (! $this->ends_at) {
            return null;
        }

        return $this->ends_at->copy()->addDays(self::graceDays());
    }

    public function scopeValid(Builder $query): Builder
    {
        $cutoff = now()->subDays(self::graceDays());

        return $query
            ->where('status', 'active')
            ->where(function (Builder $q) use ($cutoff) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', $cutoff);
            });
    }

    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->ends_at === null) {
            return true;
        }

        return $this->graceEndsAt()->isFuture();
    }

    public function isInGracePeriod(): bool
    {
        if ($this->status !== 'active' || ! $this->ends_at) {
            return false;
        }

        return $this->ends_at->isPast() && $this->graceEndsAt()->isFuture();
    }

    public function remainingDays(): ?int
    {
        if (! $this->ends_at) {
            return null;
        }

        if ($this->ends_at->isPast()) {
            return 0;
        }

        $today = now()->setTimezone('Asia/Tehran')->startOfDay();
        $endDay = $this->ends_at->copy()->setTimezone('Asia/Tehran')->startOfDay();

        return max(0, (int) $today->diffInDays($endDay, false));
    }

    public function elapsedDays(): int
    {
        if (! $this->starts_at) {
            return 0;
        }

        $startDay = $this->starts_at->copy()->setTimezone('Asia/Tehran')->startOfDay();
        $today = now()->setTimezone('Asia/Tehran')->startOfDay();

        return max(0, (int) $startDay->diffInDays($today, false));
    }

    public function unusedDays(): int
    {
        if (! $this->ends_at || $this->ends_at->isPast()) {
            return 0;
        }

        $total = $this->totalCycleDays();
        $elapsed = $this->elapsedDays();

        return max(0, min($total, $total - $elapsed));
    }

    public function graceRemainingDays(): int
    {
        if (! $this->isInGracePeriod()) {
            return 0;
        }

        return max(1, (int) ceil(now()->diffInSeconds($this->graceEndsAt()) / 86400));
    }

    public function isWithinRenewalWindow(): bool
    {
        if ($this->isInGracePeriod()) {
            return true;
        }

        $remaining = $this->remainingDays();

        return $remaining !== null && $remaining <= self::renewalWindowDays();
    }
}

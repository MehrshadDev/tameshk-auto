<?php

namespace App\Subscriptions;

use App\Models\Node;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workflow;
use App\Workflow\Contracts\WebhookTriggerContract;
use App\Workflow\Exceptions\QuotaExceededException;
use App\Workflow\Exceptions\WorkflowPublishException;
use App\Workflow\Registry\NodeRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SubscriptionManager
{
    public function isSaasEnabled(): bool
    {
        return (bool) config('saas.enabled', false);
    }

    public function resolvePlan(User $user): Plan
    {
        $this->expireOverdueSubscriptions($user);

        $activeSub = $user->activeSubscription()->with('plan')->first();

        if ($activeSub && $activeSub->plan) {
            return $activeSub->plan;
        }

        return $this->defaultPlan();
    }

    public function defaultPlan(): Plan
    {
        $slug = (string) config('saas.default_plan_slug', 'free');

        $plan = Plan::query()->where('slug', $slug)->first()
            ?? Plan::query()->orderBy('monthly_price')->first();

        if ($plan) {
            return $plan;
        }

        $fallback = config('saas.plans.0', [
            'slug' => 'free',
            'name' => 'رایگان',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'max_monthly_runs' => 500,
            'max_active_workflows' => 2,
            'features' => [],
        ]);

        return Plan::query()->firstOrCreate(
            ['slug' => $fallback['slug']],
            [
                'name' => $fallback['name'],
                'description' => $fallback['description'] ?? null,
                'monthly_price' => $fallback['monthly_price'],
                'yearly_price' => $fallback['yearly_price'],
                'max_monthly_runs' => $fallback['max_monthly_runs'],
                'max_active_workflows' => $fallback['max_active_workflows'],
                'features' => $fallback['features'] ?? [],
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }

    public function visiblePlansFor(User $user): Collection
    {
        $this->expireOverdueSubscriptions($user);

        $activeSub = $user->activeSubscription()->with('plan')->first();
        $currentPlan = ($activeSub && $activeSub->plan) ? $activeSub->plan : $this->defaultPlan();
        $inRenewalWindow = $activeSub ? $activeSub->isWithinRenewalWindow() : false;

        return Plan::query()->active()->get()->filter(function (Plan $plan) use ($currentPlan, $inRenewalWindow) {
            if ($currentPlan->slug === 'business') {
                if ($inRenewalWindow) {
                    return in_array($plan->slug, ['pro', 'business'], true);
                }

                return $plan->slug === 'business';
            }

            if ($currentPlan->slug === 'pro') {
                return in_array($plan->slug, ['pro', 'business'], true);
            }

            return true;
        })->values();
    }

    public function canPurchase(User $user, Plan $targetPlan, string $billingCycle): array
    {
        if ($targetPlan->isFree() || ! $targetPlan->is_active) {
            return ['allowed' => false, 'reason' => 'این پلن قابل خریداری نیست.'];
        }

        if (! in_array($billingCycle, ['monthly', 'yearly'], true)) {
            return ['allowed' => false, 'reason' => 'دوره پرداخت انتخاب‌شده معتبر نیست.'];
        }

        $this->expireOverdueSubscriptions($user);

        $activeSub = $user->activeSubscription()->with('plan')->first();

        if (! $activeSub || ! $activeSub->plan || $activeSub->plan->isFree()) {
            return ['allowed' => true, 'reason' => null];
        }

        $currentPlan = $activeSub->plan;

        if ($targetPlan->sort_order < $currentPlan->sort_order) {
            if ($activeSub->isWithinRenewalWindow()) {
                return ['allowed' => true, 'reason' => null];
            }

            $window = Subscription::renewalWindowDays();

            return [
                'allowed' => false,
                'reason' => "امکان تغییر به پلن پایین‌تر تنها در $window روز پایانی اشتراک فعلی فعال می‌شود.",
            ];
        }

        if ($targetPlan->sort_order > $currentPlan->sort_order) {
            if ($activeSub->billing_cycle === 'yearly' && $billingCycle === 'monthly' && ! $activeSub->isWithinRenewalWindow()) {
                return [
                    'allowed' => false,
                    'reason' => 'به دلیل فعال بودن دوره سالانه، ارتقا به پلن بالاتر فقط به صورت سالانه امکان‌پذیر است.',
                ];
            }

            return ['allowed' => true, 'reason' => null];
        }

        if ($activeSub->billing_cycle === 'monthly' && $billingCycle === 'yearly') {
            return ['allowed' => true, 'reason' => null];
        }

        if ($activeSub->billing_cycle === 'yearly' && $billingCycle === 'monthly' && ! $activeSub->isWithinRenewalWindow()) {
            return [
                'allowed' => false,
                'reason' => 'تغییر دوره از سالانه به ماهانه فقط در ۱۰ روز پایانی اشتراک امکان‌پذیر است.',
            ];
        }

        if (! $activeSub->isWithinRenewalWindow()) {
            $window = Subscription::renewalWindowDays();

            return [
                'allowed' => false,
                'reason' => "امکان تمدید مجدد این دوره تنها در $window روز پایانی اشتراک فعال می‌شود.",
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    public function calculateQuote(User $user, Plan $targetPlan, string $billingCycle): array
    {
        $basePrice = $targetPlan->priceFor($billingCycle);
        $activeSub = $user->activeSubscription()->with('plan')->first();

        $defaultQuote = [
            'base_price' => $basePrice,
            'credit_amount' => 0,
            'payable_amount' => $basePrice,
            'unused_days' => 0,
            'is_prorated' => false,
        ];

        if (! $activeSub || ! $activeSub->plan || $activeSub->plan->isFree() || $activeSub->isInGracePeriod()) {
            return $defaultQuote;
        }

        $currentPlan = $activeSub->plan;

        $isUpgradeTier = $targetPlan->sort_order > $currentPlan->sort_order;
        $isUpgradeCycle = $targetPlan->id === $currentPlan->id
            && $activeSub->billing_cycle === 'monthly'
            && $billingCycle === 'yearly';

        if (! $isUpgradeTier && ! $isUpgradeCycle) {
            return $defaultQuote;
        }

        $unusedDays = $activeSub->unusedDays();
        $totalDays = $activeSub->totalCycleDays();

        if ($unusedDays <= 0 || $totalDays <= 0) {
            return $defaultQuote;
        }

        $paidRecordAmount = (int) ($activeSub->payments()
            ->where('status', 'paid')
            ->latest('id')
            ->value('amount') ?? 0);

        $cycleValue = max($paidRecordAmount, $currentPlan->priceFor($activeSub->billing_cycle));

        if ($cycleValue <= 0) {
            return $defaultQuote;
        }

        if ($unusedDays >= $totalDays) {
            $credit = $cycleValue;
        } else {
            $rawCredit = ($cycleValue * $unusedDays) / $totalDays;
            $credit = min($cycleValue, (int) (ceil($rawCredit / 1000) * 1000));
        }

        if ($credit <= 0 || $credit >= $basePrice) {
            return $defaultQuote;
        }

        $payable = max(1000, $basePrice - $credit);

        return [
            'base_price' => $basePrice,
            'credit_amount' => $credit,
            'payable_amount' => $payable,
            'unused_days' => $unusedDays,
            'is_prorated' => true,
        ];
    }

    public function maxActiveWorkflows(User $user): ?int
    {
        if (! $this->isSaasEnabled()) {
            return null;
        }

        $plan = $this->resolvePlan($user);

        if ($plan->slug === 'business' || $plan->hasUnlimitedActiveWorkflows()) {
            return null;
        }

        return $plan->max_active_workflows;
    }

    public function maxTotalWorkflows(User $user): ?int
    {
        if (! $this->isSaasEnabled()) {
            $max = config('workflow.max_per_user');

            return is_numeric($max) ? (int) $max : null;
        }

        $plan = $this->resolvePlan($user);

        if ($plan->slug === 'business' || $plan->hasUnlimitedActiveWorkflows()) {
            return null;
        }

        return max(1, (int) $plan->max_active_workflows) * 5;
    }

    public function maxMonthlyRuns(User $user): ?int
    {
        if (! $this->isSaasEnabled()) {
            return null;
        }

        $plan = $this->resolvePlan($user);

        if ($plan->slug === 'business' || $plan->hasUnlimitedMonthlyRuns()) {
            return null;
        }

        return $plan->max_monthly_runs;
    }

    public function syncUsageCycle(User $user): User
    {
        if ($user->runs_reset_at === null || $user->runs_reset_at->isPast()) {
            $user->forceFill([
                'runs_used' => 0,
                'runs_reset_at' => now()->addDays(30),
            ])->saveQuietly();
        }

        return $user;
    }

    public function hasRunQuota(User $user): bool
    {
        if (! $this->isSaasEnabled()) {
            return true;
        }

        $this->syncUsageCycle($user);
        $max = $this->maxMonthlyRuns($user);

        if ($max === null) {
            return true;
        }

        return $user->runs_used < $max;
    }

    public function consumeRunQuota(User $user): void
    {
        if (! $this->isSaasEnabled()) {
            return;
        }

        DB::transaction(function () use ($user) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $this->expireOverdueSubscriptions($lockedUser);

            if ($lockedUser->runs_reset_at === null || $lockedUser->runs_reset_at->isPast()) {
                $lockedUser->runs_used = 0;
                $lockedUser->runs_reset_at = now()->addDays(30);
            }

            $plan = $this->resolvePlan($lockedUser);
            $maxRuns = ($plan->slug === 'business' || $plan->hasUnlimitedMonthlyRuns()) ? null : $plan->max_monthly_runs;

            if ($maxRuns !== null && $lockedUser->runs_used >= $maxRuns) {
                $lockedUser->saveQuietly();
                throw QuotaExceededException::monthlyRuns($plan->name, $maxRuns);
            }

            $lockedUser->runs_used = $lockedUser->runs_used + 1;
            $lockedUser->saveQuietly();

            $user->runs_used = $lockedUser->runs_used;
            $user->runs_reset_at = $lockedUser->runs_reset_at;
        });
    }

    public function assertCanActivateWorkflow(Workflow $workflow): void
    {
        if (! $this->isSaasEnabled()) {
            return;
        }

        $workflow->loadMissing('user');
        $user = $workflow->user;

        if (! $user) {
            return;
        }

        DB::transaction(function () use ($user, $workflow) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $plan = $this->resolvePlan($lockedUser);
            $maxActive = ($plan->slug === 'business' || $plan->hasUnlimitedActiveWorkflows()) ? null : $plan->max_active_workflows;

            if ($maxActive === null) {
                return;
            }

            $currentActiveCount = $lockedUser->workflows()
                ->whereKeyNot($workflow->id)
                ->whereNotNull('published_at')
                ->where('is_active', true)
                ->count();

            if ($currentActiveCount >= $maxActive) {
                throw WorkflowPublishException::because(
                    "سقف ورک‌فلوهای فعال در پلن «{$plan->name}» ($maxActive ورک‌فلو) پر شده است. برای فعال‌سازی این ورک‌فلو، یکی از ورک‌فلوهای فعال قبلی را متوقف کنید یا اشتراک خود را ارتقا دهید."
                );
            }
        });
    }

    public function activateSubscription(User $user, Plan $plan, string $billingCycle): Subscription
    {
        $days = $billingCycle === 'yearly' ? 365 : 30;
        $maxCapDays = $billingCycle === 'yearly' ? 375 : 60;

        return DB::transaction(function () use ($user, $plan, $billingCycle, $days, $maxCapDays) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $currentSub = $lockedUser->subscriptions()
                ->with('plan')
                ->valid()
                ->latest('id')
                ->first();

            $startsAt = now();
            $endsAt = now()->addDays($days);

            if ($currentSub) {
                $isRenewalOrDowngrade = $currentSub->plan
                    && $plan->sort_order <= $currentSub->plan->sort_order
                    && ! ($currentSub->plan_id === $plan->id && $currentSub->billing_cycle === 'monthly' && $billingCycle === 'yearly');

                if ($isRenewalOrDowngrade && $currentSub->ends_at && $currentSub->ends_at->isFuture()) {
                    $stacked = $currentSub->ends_at->copy()->addDays($days);
                    $maxAllowedEnd = now()->addDays($maxCapDays);
                    $endsAt = $stacked->lessThanOrEqualTo($maxAllowedEnd) ? $stacked : $maxAllowedEnd;
                }

                $currentSub->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                ]);
            }

            $subscription = $lockedUser->subscriptions()->create([
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'status' => 'active',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $lockedUser->forceFill([
                'runs_used' => 0,
                'runs_reset_at' => now()->addDays(30),
            ])->saveQuietly();

            $this->enforcePlanWorkflowLimits($lockedUser, $plan);

            return $subscription;
        });
    }

    public function enforcePlanWorkflowLimits(User $user, Plan $plan): int
    {
        if (! $this->isSaasEnabled() || $plan->slug === 'business' || $plan->max_active_workflows === null) {
            return 0;
        }

        $activeWorkflows = $user->workflows()
            ->with('nodes')
            ->whereNotNull('published_at')
            ->where('is_active', true)
            ->latest('updated_at')
            ->get();

        if ($activeWorkflows->count() <= $plan->max_active_workflows) {
            return 0;
        }

        $excess = $activeWorkflows->slice($plan->max_active_workflows);

        return $this->pauseWorkflowsCollection($user, $excess);
    }

    public function expireOverdueSubscriptions(?User $user = null): int
    {
        $cutoff = now()->subDays(Subscription::graceDays());

        $query = Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $cutoff);

        if ($user) {
            $query->where('user_id', $user->id);
        }

        $expiredSubs = $query->get();

        if ($expiredSubs->isEmpty()) {
            return 0;
        }

        $affectedUserIds = $expiredSubs->pluck('user_id')->unique()->all();

        Subscription::query()
            ->whereIn('id', $expiredSubs->pluck('id')->all())
            ->update(['status' => 'expired']);

        foreach (User::query()->whereIn('id', $affectedUserIds)->get() as $affectedUser) {
            $affectedUser->forceFill([
                'runs_used' => 0,
                'runs_reset_at' => now()->addDays(30),
            ])->saveQuietly();

            $this->pauseAllActiveWorkflowsOnExpiry($affectedUser);
        }

        return $expiredSubs->count();
    }

    public function pauseAllActiveWorkflowsOnExpiry(User $user): int
    {
        if (! $this->isSaasEnabled()) {
            return 0;
        }

        $activeSub = $user->activeSubscription()->first();

        if ($activeSub) {
            return 0;
        }

        $activeWorkflows = $user->workflows()
            ->with('nodes')
            ->whereNotNull('published_at')
            ->where('is_active', true)
            ->get();

        if ($activeWorkflows->isEmpty()) {
            return 0;
        }

        return $this->pauseWorkflowsCollection($user, $activeWorkflows);
    }

    private function pauseWorkflowsCollection(User $user, Collection $workflows): int
    {
        $registry = app(NodeRegistry::class);
        $paused = 0;

        foreach ($workflows as $workflow) {
            $trigger = $workflow->nodes->first(fn (Node $n) => str_starts_with($n->type, 'trigger.'));

            if ($trigger && $registry->has($trigger->type)) {
                $executor = $registry->make($trigger->type);

                if ($executor instanceof WebhookTriggerContract) {
                    $credId = (int) ($trigger->config['credential_id'] ?? 0);
                    $credential = $credId > 0 ? $user->credentials()->find($credId) : null;

                    if ($credential) {
                        try {
                            $executor->unregisterWebhook($credential);
                        } catch (Throwable $e) {
                            report($e);
                        }
                    }
                }
            }

            $workflow->timestamps = false;
            $workflow->update(['is_active' => false]);
            $workflow->timestamps = true;
            $paused++;
        }

        return $paused;
    }

    public function usageSummary(User $user): array
    {
        $this->syncUsageCycle($user);

        $plan = $this->resolvePlan($user);
        $activeSub = $user->activeSubscription()->with('plan')->first();

        $runsUsed = (int) $user->runs_used;
        $maxRuns = $this->maxMonthlyRuns($user);

        if ($runsUsed <= 0) {
            $runsPercent = 0;
        } elseif ($maxRuns && $maxRuns > 0) {
            $runsPercent = min(100, (int) round(($runsUsed / $maxRuns) * 100));
        } else {
            $runsPercent = min(100, (int) round(($runsUsed / 50000) * 100));
        }

        $activeWorkflows = $user->workflows()
            ->whereNotNull('published_at')
            ->where('is_active', true)
            ->count();

        $maxActiveWorkflows = $this->maxActiveWorkflows($user);

        if ($activeWorkflows <= 0) {
            $activePercent = 0;
        } elseif ($maxActiveWorkflows && $maxActiveWorkflows > 0) {
            $activePercent = min(100, (int) round(($activeWorkflows / $maxActiveWorkflows) * 100));
        } else {
            $activePercent = min(100, $activeWorkflows * 5);
        }

        $inGracePeriod = $activeSub ? $activeSub->isInGracePeriod() : false;
        $graceRemainingDays = $activeSub ? $activeSub->graceRemainingDays() : 0;
        $canRenewCurrent = $activeSub ? $activeSub->isWithinRenewalWindow() : false;

        return [
            'saas_enabled' => $this->isSaasEnabled(),
            'plan' => $plan,
            'subscription' => $activeSub,
            'in_grace_period' => $inGracePeriod,
            'grace_remaining_days' => $graceRemainingDays,
            'can_renew_current' => $canRenewCurrent,
            'runs_used' => $runsUsed,
            'max_runs' => $maxRuns,
            'runs_percent' => $runsPercent,
            'runs_reset_at' => $user->runs_reset_at,
            'active_workflows' => $activeWorkflows,
            'max_active_workflows' => $maxActiveWorkflows,
            'active_percent' => $activePercent,
            'total_workflows' => $user->workflows()->count(),
            'max_total_workflows' => $this->maxTotalWorkflows($user),
        ];
    }
}

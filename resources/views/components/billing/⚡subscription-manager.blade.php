<?php

use App\Subscriptions\SubscriptionManager;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $billingCycle = 'monthly';

    public function mount(SubscriptionManager $subscriptions): void
    {
        $summary = $subscriptions->usageSummary(auth()->user());
        $sub = $summary['subscription'];

        if ($sub && $sub->billing_cycle === 'yearly') {
            $this->billingCycle = 'yearly';
        }
    }

    public function setBillingCycle(string $cycle): void
    {
        if (! in_array($cycle, ['monthly', 'yearly'], true)) {
            return;
        }

        if ($cycle === 'monthly' && ! $this->canSelectMonthly) {
            return;
        }

        $this->billingCycle = $cycle;
    }

    #[Computed]
    public function summary(): array
    {
        return app(SubscriptionManager::class)->usageSummary(auth()->user());
    }

    #[Computed]
    public function canSelectMonthly(): bool
    {
        $sub = $this->summary['subscription'];

        if (! $sub || $sub->billing_cycle !== 'yearly') {
            return true;
        }

        return $sub->isWithinRenewalWindow();
    }

    #[Computed]
    public function visiblePlans()
    {
        return app(SubscriptionManager::class)->visiblePlansFor(auth()->user());
    }

    #[Computed]
    public function payments()
    {
        return auth()->user()
            ->payments()
            ->where('status', '!=', 'pending')
            ->with('plan:id,name,slug')
            ->latest('id')
            ->paginate(8);
    }
};
?>

<div class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    @php
        $paymentResult = session('payment_result');
        $summary = $this->summary;
        $effectiveCycle = (! $this->canSelectMonthly) ? 'yearly' : $billingCycle;
    @endphp

    @if (is_array($paymentResult) && ! empty($paymentResult['message']))
        <div x-data="{ show: true }"
             x-show="show"
             x-transition.opacity
            @class([
                'flex items-start justify-between gap-3 rounded-2xl border p-4 shadow-2xs',
                'border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' => ($paymentResult['status'] ?? '') === 'success',
                'border-primary/30 bg-primary/10 text-primary' => ($paymentResult['status'] ?? '') !== 'success',
            ])>
            <div class="flex items-start gap-3">
                <div @class([
                    'flex size-10 shrink-0 items-center justify-center rounded-xl',
                    'bg-emerald-500/20 text-emerald-500' => ($paymentResult['status'] ?? '') === 'success',
                    'bg-primary/20 text-primary' => ($paymentResult['status'] ?? '') !== 'success',
                ])>
                    <x-ui.icon :name="($paymentResult['status'] ?? '') === 'success' ? 'check' : 'alert-triangle'" class="size-5"/>
                </div>

                <div class="space-y-1 text-xs">
                    <p class="font-bold leading-6">{{ $paymentResult['message'] }}</p>
                    @if (! empty($paymentResult['ref_id']))
                        <p class="font-mono text-[11px] opacity-90">
                            کد رهگیری زرین‌پال:
                            <span class="font-bold select-all" dir="ltr">{{ $paymentResult['ref_id'] }}</span>
                        </p>
                    @endif
                </div>
            </div>

            <button type="button"
                    @click="show = false"
                    aria-label="بستن"
                    class="flex size-7 shrink-0 items-center justify-center rounded-lg opacity-70 transition hover:opacity-100 cursor-pointer">
                <x-ui.icon name="x" class="size-4"/>
            </button>
        </div>
    @endif

    <x-billing.overview :summary="$summary" />

    <x-billing.plans
        :plans="$this->visiblePlans"
        :current-plan="$summary['plan']"
        :active-subscription="$summary['subscription']"
        :can-renew-current="$summary['can_renew_current']"
        :can-select-monthly="$this->canSelectMonthly"
        :billing-cycle="$effectiveCycle"
    />

    <x-billing.payments :payments="$this->payments" />
</div>

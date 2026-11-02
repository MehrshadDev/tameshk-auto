<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Subscriptions\Exceptions\PaymentException;
use App\Subscriptions\Gateways\ZarinpalGateway;
use App\Subscriptions\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class BillingController extends Controller
{
    public function __construct(
        private readonly SubscriptionManager $subscriptions,
        private readonly ZarinpalGateway $gateway,
    ) {}

    public function index(): View
    {
        abort_unless($this->subscriptions->isSaasEnabled(), 404);

        return view('pages.billing.index');
    }

    public function checkout(Request $request): RedirectResponse
    {
        abort_unless($this->subscriptions->isSaasEnabled(), 404);

        $user = $request->user();
        $rateKey = 'billing-checkout:' . $user->id;

        if (RateLimiter::tooManyAttempts($rateKey, 8)) {
            return redirect()
                ->route('app.billing.index')
                ->with('payment_result', [
                    'status' => 'error',
                    'message' => 'تعداد درخواست‌های پرداخت شما بیش از حد مجاز است. لطفاً یک دقیقه دیگر تلاش کنید.',
                ]);
        }

        RateLimiter::hit($rateKey, 60);

        $validated = $request->validate([
            'plan' => ['required', 'string', 'max:50'],
            'billing_cycle' => ['required', 'string', Rule::in(['monthly', 'yearly'])],
        ]);

        $plan = Plan::query()
            ->where('slug', $validated['plan'])
            ->where('is_active', true)
            ->first();

        if (! $plan || $plan->isFree()) {
            return redirect()
                ->route('app.billing.index')
                ->with('payment_result', [
                    'status' => 'error',
                    'message' => 'پلن انتخاب‌شده برای خرید معتبر نیست.',
                ]);
        }

        $billingCycle = $validated['billing_cycle'];
        $check = $this->subscriptions->canPurchase($user, $plan, $billingCycle);

        if (! $check['allowed']) {
            return redirect()
                ->route('app.billing.index')
                ->with('payment_result', [
                    'status' => 'error',
                    'message' => $check['reason'],
                ]);
        }

        $quote = $this->subscriptions->calculateQuote($user, $plan, $billingCycle);
        $amount = $quote['payable_amount'];

        if ($amount <= 0) {
            return redirect()
                ->route('app.billing.index')
                ->with('payment_result', [
                    'status' => 'error',
                    'message' => 'مبلغ پلن انتخاب‌شده نامعتبر است.',
                ]);
        }

        Payment::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->delete();

        $payment = Payment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'gateway' => 'zarinpal',
            'status' => 'pending',
            'ip_address' => $request->ip(),
        ]);

        $cycleLabel = $billingCycle === 'yearly' ? 'سالانه' : 'ماهانه';
        $description = "اشتراک $plan->name ({$cycleLabel}) - " . config('app.name');

        try {
            $result = $this->gateway->request(
                amountInTomans: $amount,
                description: $description,
                callbackUrl: route('billing.callback'),
                metadata: [
                    'email' => $user->email,
                    'order_id' => $payment->public_id,
                ]
            );

            $payment->update([
                'authority' => $result['authority'],
                'raw_response' => $result['raw'],
            ]);

            return redirect()->away($result['redirect_url']);
        } catch (PaymentException $e) {
            $payment->delete();

            return redirect()
                ->route('app.billing.index')
                ->with('payment_result', [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ]);
        } catch (Throwable $e) {
            report($e);
            $payment->delete();

            return redirect()
                ->route('app.billing.index')
                ->with('payment_result', [
                    'status' => 'error',
                    'message' => 'خطایی در اتصال به درگاه پرداخت رخ داد. لطفاً دوباره تلاش کنید.',
                ]);
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless($this->subscriptions->isSaasEnabled(), 404);

        $authority = trim((string) $request->query('Authority', ''));
        $status = strtoupper(trim((string) $request->query('Status', '')));

        if ($authority === '' || strlen($authority) > 64 || ! preg_match('/^[A-Za-z0-9\-_]+$/', $authority)) {
            return $this->redirectAfterCallback('error', 'شناسه پرداخت بازگشتی از درگاه معتبر نیست.');
        }

        try {
            return DB::transaction(function () use ($authority, $status, $request) {
                $payment = Payment::query()
                    ->with(['user', 'plan'])
                    ->where('authority', $authority)
                    ->lockForUpdate()
                    ->first();

                if (! $payment || ! $payment->user || ! $payment->plan) {
                    return $this->redirectAfterCallback('error', 'تراکنش مورد نظر در سیستم یافت نشد.');
                }

                if ($request->user() && $payment->user_id !== $request->user()->id) {
                    abort(403);
                }

                if ($payment->isPaid()) {
                    return $this->redirectAfterCallback(
                        'success',
                        "این پرداخت قبلاً تایید شده و اشتراک «{$payment->plan->name}» شما فعال است.",
                        $payment->ref_id
                    );
                }

                if (! $payment->isPending()) {
                    return $this->redirectAfterCallback('error', 'این تراکنش قبلاً منقضی یا لغو شده است.');
                }

                if ($status !== 'OK') {
                    $payment->delete();

                    return $this->redirectAfterCallback('error', 'فرآیند پرداخت توسط شما لغو شد.');
                }

                try {
                    $verified = $this->gateway->verify($payment->amount, $authority);
                } catch (PaymentException $e) {
                    $payment->update(['status' => 'failed']);

                    return $this->redirectAfterCallback('error', $e->getMessage());
                }

                $refId = $verified['ref_id'];

                if ($refId !== '' && Payment::query()->where('ref_id', $refId)->where('status', 'paid')->whereKeyNot($payment->id)->exists()) {
                    $payment->update(['status' => 'failed']);

                    return $this->redirectAfterCallback('error', 'شماره پیگیری این تراکنش تکراری است.');
                }

                $subscription = $this->subscriptions->activateSubscription(
                    $payment->user,
                    $payment->plan,
                    $payment->billing_cycle
                );

                $payment->update([
                    'subscription_id' => $subscription->id,
                    'status' => 'paid',
                    'ref_id' => $refId !== '' ? $refId : null,
                    'card_pan' => $verified['card_pan'] ?: null,
                    'card_hash' => $verified['card_hash'] ?: null,
                    'paid_at' => now(),
                    'raw_response' => $verified['raw'],
                ]);

                if (auth()->check()) {
                    return redirect()
                        ->route('app.workflows.index')
                        ->with('subscription_celebration', [
                            'plan_name' => $payment->plan->name,
                            'plan_slug' => $payment->plan->slug,
                            'billing_cycle' => $payment->billing_cycle === 'yearly' ? 'سالانه' : 'ماهانه',
                            'max_runs' => $payment->plan->max_monthly_runs !== null
                                ? number_format($payment->plan->max_monthly_runs)
                                : 'نامحدود',
                            'max_active' => $payment->plan->max_active_workflows !== null
                                ? $payment->plan->max_active_workflows . ' ورک‌فلو'
                                : 'نامحدود',
                            'remaining_days' => $subscription->remainingDays(),
                            'ref_id' => $refId,
                        ]);
                }

                return $this->redirectAfterCallback(
                    'success',
                    "پرداخت با موفقیت انجام شد و اشتراک «{$payment->plan->name}» برای حساب شما فعال گردید!",
                    $refId
                );
            });
        } catch (Throwable $e) {
            report($e);

            return $this->redirectAfterCallback('error', 'خطایی در بررسی نهایی تراکنش رخ داد. لطفاً با پشتیبانی تماس بگیرید.');
        }
    }

    private function redirectAfterCallback(string $status, string $message, ?string $refId = null): RedirectResponse
    {
        $targetRoute = auth()->check() ? 'app.billing.index' : 'login';

        return redirect()
            ->route($targetRoute)
            ->with('payment_result', [
                'status' => $status,
                'message' => $message,
                'ref_id' => $refId,
            ]);
    }
}

@props([
    'payments',
])

@php
    use App\Workflow\Support\DateTimeHelper;
@endphp

<div class="space-y-4">
    <div class="flex items-center justify-between px-1">
        <div class="flex items-center gap-2">
            <x-ui.icon name="receipt" class="size-5 text-primary"/>
            <h3 class="text-sm font-extrabold text-content-main">تاریخچه تراکنش‌ها و صورتحساب‌ها</h3>
        </div>
    </div>

    @if ($payments->isEmpty())
        <x-ui.card :padding="false">
            <x-ui.empty-state
                icon="receipt"
                title="هنوز تراکنشی ثبت نشده است"
                description="پس از خرید یا تمدید هر یک از پلن‌های اشتراکی، رسید و جزئیات پرداخت شما در این بخش نمایش داده می‌شود."
            />
        </x-ui.card>
    @else
        <div class="overflow-hidden rounded-2xl border border-line-border bg-surface-card shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs">
                    <thead>
                    <tr class="border-b border-line-border bg-surface-raised/60 text-content-muted">
                        <th class="px-4 py-3 text-start font-bold">شناسه سفارش</th>
                        <th class="px-4 py-3 text-start font-bold">پلن و دوره</th>
                        <th class="px-4 py-3 text-start font-bold">مبلغ</th>
                        <th class="px-4 py-3 text-start font-bold">کد رهگیری (Ref ID)</th>
                        <th class="px-4 py-3 text-start font-bold">تاریخ</th>
                        <th class="px-4 py-3 text-start font-bold">وضعیت</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-line-border/60">
                    @foreach ($payments as $payment)
                        @php
                            $statusMeta = match ($payment->status) {
                                'paid' => ['variant' => 'success', 'label' => 'پرداخت موفق'],
                                'canceled' => ['variant' => 'neutral', 'label' => 'لغو شده'],
                                default => ['variant' => 'danger', 'label' => 'ناموفق'],
                            };
                            $dateObj = ($payment->paid_at ?? $payment->created_at)->copy()->setTimezone('Asia/Tehran');
                            $persianDate = DateTimeHelper::toPersianDate($dateObj, 'yyyy/MM/dd HH:mm');
                        @endphp
                        <tr class="transition hover:bg-surface-raised/40">
                            <td class="px-4 py-3.5 font-mono text-[11px] font-semibold text-content-muted" dir="ltr">
                                #{{ substr($payment->public_id, 0, 8) }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-bold text-content-main">پلن {{ $payment->plan?->name ?? '—' }}</span>
                                <span class="ms-1 rounded-md border border-line-border bg-surface-base px-1.5 py-0.5 text-[10px] text-content-muted">
                                        {{ $payment->billing_cycle === 'yearly' ? 'سالانه' : 'ماهانه' }}
                                    </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-mono font-bold tabular-nums text-content-main">{{ number_format($payment->amount) }}</span>
                                <span class="text-[11px] text-content-muted">تومان</span>
                            </td>
                            <td class="px-4 py-3.5 font-mono text-xs text-content-main" dir="ltr">
                                {{ $payment->ref_id ?: '—' }}
                            </td>
                            <td class="px-4 py-3.5 font-mono text-xs text-content-muted" dir="ltr">
                                {{ $persianDate }}
                            </td>
                            <td class="px-4 py-3.5">
                                <x-ui.badge :variant="$statusMeta['variant']" dot>{{ $statusMeta['label'] }}</x-ui.badge>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <x-ui.pagination :paginator="$payments" />
    @endif
</div>

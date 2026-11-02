@php
    use App\Models\Plan;

    $plans = Plan::query()->active()->get();
@endphp

@if (config('saas.enabled') && $plans->isNotEmpty())
    <section id="pricing" x-data="{ cycle: 'monthly' }" class="w-full border-t border-line-border bg-surface-base py-20 font-vazirmatn-rd">
        <div class="mx-auto max-w-5xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-2xl font-extrabold text-content-main sm:text-3xl">
                    پلن‌های اشتراک شفاف و منعطف
                </h2>
                <p class="mt-3 text-sm leading-7 text-content-muted">
                    با پلن رایگان شروع کنید و هر زمان که کسب‌وکارتان رشد کرد، ظرفیت اجرا و ورک‌فلوهای خود را ارتقا دهید.
                </p>

                <div class="mt-6 inline-flex items-center rounded-xl border border-line-border bg-surface-card p-1">
                    <button type="button"
                            @click="cycle = 'monthly'"
                            :class="cycle === 'monthly' ? 'bg-primary text-white font-bold shadow-2xs' : 'text-content-muted hover:text-content-main font-medium'"
                            class="rounded-lg px-4 py-1.5 text-xs transition cursor-pointer">
                        پرداخت ماهانه
                    </button>
                    <button type="button"
                            @click="cycle = 'yearly'"
                            :class="cycle === 'yearly' ? 'bg-primary text-white font-bold shadow-2xs' : 'text-content-muted hover:text-content-main font-medium'"
                            class="inline-flex items-center gap-1.5 rounded-lg px-4 py-1.5 text-xs transition cursor-pointer">
                        <span>پرداخت سالانه</span>
                        <span class="rounded-md bg-emerald-500/15 px-1.5 py-0.5 text-[10px] font-bold text-emerald-500"
                              :class="cycle === 'yearly' && 'bg-white/20 text-white'">
                            ۲ ماه رایگان
                        </span>
                    </button>
                </div>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
                @foreach ($plans as $plan)
                    @php
                        $isPopular = $plan->slug === 'pro';
                        $features = is_array($plan->features) ? $plan->features : [];
                    @endphp
                    <div @class([
                        'relative flex flex-col justify-between rounded-2xl border p-6 transition',
                        'border-primary bg-surface-card ring-2 ring-primary/15 shadow-xl' => $isPopular,
                        'border-line-border bg-surface-card shadow-sm' => ! $isPopular,
                    ])>
                        @if ($isPopular)
                            <span class="absolute -top-3 left-6 rounded-full bg-primary px-3 py-0.5 text-[11px] font-bold text-white">
                                محبوب‌ترین
                            </span>
                        @endif

                        <div>
                            <h3 class="text-lg font-extrabold text-content-main">{{ $plan->name }}</h3>
                            <p class="mt-1.5 min-h-10 text-xs leading-5 text-content-muted">{{ $plan->description }}</p>

                            <div class="my-5 border-y border-line-border/70 py-4">
                                @if ($plan->isFree())
                                    <span class="text-2xl font-extrabold text-content-main">۰ تومان</span>
                                    <span class="text-xs text-content-muted">/ همیشگی</span>
                                @else
                                    <span x-show="cycle === 'monthly'" class="font-mono text-2xl font-extrabold text-content-main">
                                        {{ number_format($plan->monthly_price) }}
                                    </span>
                                    <span x-show="cycle === 'yearly'" x-cloak class="font-mono text-2xl font-extrabold text-content-main">
                                        {{ number_format($plan->yearly_price) }}
                                    </span>
                                    <span class="text-xs font-semibold text-content-muted" x-text="cycle === 'yearly' ? 'تومان / سالانه' : 'تومان / ماهانه'">
                                        تومان / ماهانه
                                    </span>
                                @endif
                            </div>

                            <ul class="space-y-2.5">
                                @foreach ($features as $feature)
                                    <li class="flex items-start gap-2 text-xs text-content-muted">
                                        <x-ui.icon name="check" class="mt-0.5 size-4 shrink-0 text-emerald-500"/>
                                        <span class="leading-5">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="mt-8">
                            <x-ui.button href="{{ route('register') }}" :variant="$isPopular ? 'primary' : 'secondary'" class="w-full">
                                {{ $plan->isFree() ? 'شروع رایگان' : 'انتخاب پلن ' . $plan->name }}
                            </x-ui.button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

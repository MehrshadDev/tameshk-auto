@php
    $models = [
        ['name' => 'Gemini', 'icon' => 'brand:gemini'],
        ['name' => 'ChatGPT', 'icon' => 'brand:chatgpt'],
        ['name' => 'Claude', 'icon' => 'brand:claude'],
        ['name' => 'DeepSeek', 'icon' => 'brand:deepseek'],
        ['name' => 'Groq', 'icon' => 'brand:groq'],
    ];

    $features = [
        [
            'icon' => 'brand:ai-agent',
            'tone' => 'bg-purple-500/10 text-purple-500',
            'title' => 'ایجنتی که خودش تصمیم می‌گیرد',
            'text' => 'مدل، حافظه و ابزارها را وصل کنید؛ ایجنت در هر پیام خودش ابزار مناسب را انتخاب می‌کند.',
        ],
        [
            'icon' => 'brackets',
            'tone' => 'bg-primary/10 text-primary',
            'title' => 'داده را بکشید و رها کنید',
            'text' => 'خروجی هر نود را روی فیلد بعدی بیندازید. درستی متغیر همان لحظه بررسی می‌شود.',
        ],
        [
            'icon' => 'bolt',
            'tone' => 'bg-emerald-500/10 text-emerald-500',
            'title' => 'یک کلیک تا انتشار',
            'text' => 'وب‌هوک تلگرام خودکار ثبت می‌شود و ورک‌فلو شما شبانه‌روز آماده می‌ماند.',
        ],
        [
            'icon' => 'calendar',
            'tone' => 'bg-amber-500/10 text-amber-500',
            'title' => 'ساخته‌شده برای ایران',
            'text' => 'تقویم جلالی، اعداد فارسی، کد تایید، ذخیره‌ساز داده و کلیدهای API رمزنگاری‌شده.',
        ],
    ];
@endphp

<section id="features"
         x-data="{ selectedModel: 0, models: @js($models) }"
         class="w-full border-t border-line-border bg-surface-base py-20 font-vazirmatn-rd sm:py-28">

    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="mx-auto max-w-3xl text-center">
            <h2 class="text-4xl font-extrabold leading-[1.3] tracking-tight text-content-main sm:text-6xl sm:leading-[1.25]">
                <span class="block">اتوماسیون فارسی،</span>
                <span class="relative mt-2 inline-block">
                    <span class="bg-linear-to-l from-primary via-purple-500 to-amber-500 bg-clip-text py-1 text-transparent">بدون پیچیدگی</span>
                    <svg viewBox="0 0 300 14" preserveAspectRatio="none" class="absolute inset-x-0 -bottom-3 h-3 w-full text-primary" aria-hidden="true">
                        <path d="M3 9 C 45 2, 85 13, 130 7 S 215 3, 297 9" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
                    </svg>
                </span>
            </h2>
            <p class="mx-auto mt-8 max-w-xl text-base leading-8 text-content-muted sm:text-lg">
                هر چیزی که برای ساخت ورک‌فلو لازم دارید، از همان اول آماده است.
            </p>
        </div>

        <div class="mt-14 divide-y divide-line-border overflow-hidden rounded-3xl border border-line-border bg-surface-card lg:hidden">
            @foreach ($features as $feature)
                <div class="flex items-start gap-4 p-5">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $feature['tone'] }}">
                        <x-ui.icon :name="$feature['icon']" class="size-5"/>
                    </span>
                    <div>
                        <h3 class="text-base font-extrabold leading-7 text-content-main">{{ $feature['title'] }}</h3>
                        <p class="mt-1 text-sm leading-7 text-content-muted">{{ $feature['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-16 hidden gap-5 lg:grid lg:grid-cols-12">
            <article class="flex flex-col rounded-3xl border border-line-border bg-surface-card p-3 lg:col-span-7">
                <div class="relative flex min-h-80 flex-1 flex-col items-center justify-center gap-6 overflow-hidden rounded-2xl bg-surface-base/70 p-6">
                    <div class="pointer-events-none absolute left-1/2 top-0 size-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-purple-500/20 blur-3xl" aria-hidden="true"></div>

                    <svg viewBox="0 0 360 250" style="direction: ltr" class="relative block w-full max-w-sm" role="img" aria-label="AI Agent connected to a chat model, memory and a tool">
                        <path d="M180 100 C180 130 110 126 110 156 M180 100 V156 M180 100 C180 130 250 126 250 156" class="fill-none stroke-purple-500/40" stroke-width="2" stroke-dasharray="4 5"/>
                        <circle cx="180" cy="100" r="4" class="fill-purple-500"/>

                        <rect x="80" y="16" width="200" height="84" rx="22" class="fill-surface-card stroke-purple-500" stroke-width="2"/>
                        <foreignObject x="98" y="34" width="48" height="48">
                            <div class="flex size-12 items-center justify-center rounded-2xl bg-purple-500/10 text-purple-500">
                                <x-ui.icon name="brand:ai-agent" class="size-7"/>
                            </div>
                        </foreignObject>
                        <text x="158" y="54" text-anchor="start" class="fill-content-main text-[15px] font-extrabold">AI Agent</text>
                        <text x="158" y="75" text-anchor="start" class="fill-emerald-500 text-[13px] font-bold" x-text="models[selectedModel].name"></text>

                        <circle cx="110" cy="182" r="26" class="fill-surface-card stroke-purple-500/50" stroke-width="1.5"/>
                        <foreignObject x="96" y="168" width="28" height="28">
                            <div class="flex size-7 items-center justify-center">
                                @foreach ($models as $mIdx => $m)
                                    <span x-show="selectedModel === {{ $mIdx }}" @if ($mIdx > 0) x-cloak @endif class="flex">
                                        <x-ui.icon :name="$m['icon']" class="size-6"/>
                                    </span>
                                @endforeach
                            </div>
                        </foreignObject>
                        <text x="110" y="232" text-anchor="middle" class="fill-content-muted text-[12px] font-semibold">Model</text>

                        <circle cx="180" cy="182" r="26" class="fill-surface-card stroke-line-border" stroke-width="1.5"/>
                        <foreignObject x="166" y="168" width="28" height="28">
                            <div class="flex size-7 items-center justify-center text-cyan-500">
                                <x-ui.icon name="history" class="size-6"/>
                            </div>
                        </foreignObject>
                        <text x="180" y="232" text-anchor="middle" class="fill-content-muted text-[12px] font-semibold">Memory</text>

                        <circle cx="250" cy="182" r="26" class="fill-surface-card stroke-line-border" stroke-width="1.5"/>
                        <foreignObject x="236" y="168" width="28" height="28">
                            <div class="flex size-7 items-center justify-center">
                                <x-ui.icon name="brand:google-sheets" class="size-6"/>
                            </div>
                        </foreignObject>
                        <text x="250" y="232" text-anchor="middle" class="fill-content-muted text-[12px] font-semibold">Tool</text>
                    </svg>

                    <div dir="ltr" class="relative flex items-center gap-1.5 rounded-2xl border border-line-border bg-surface-card p-1.5">
                        @foreach ($models as $mIdx => $m)
                            <button type="button"
                                    @click="selectedModel = {{ $mIdx }}"
                                    :aria-pressed="selectedModel === {{ $mIdx }}"
                                    aria-label="{{ $m['name'] }}"
                                    :class="selectedModel === {{ $mIdx }} ? 'bg-purple-500/15 ring-1 ring-purple-500/50' : 'hover:bg-surface-base'"
                                    class="flex size-10 cursor-pointer items-center justify-center rounded-xl transition focus-visible:outline-2 focus-visible:outline-purple-500">
                                <x-ui.icon :name="$m['icon']" class="size-5"/>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="px-4 pb-4 pt-6 sm:px-6">
                    <h3 class="text-2xl font-extrabold text-content-main">{{ $features[0]['title'] }}</h3>
                    <p class="mt-2 max-w-md text-base leading-8 text-content-muted">{{ $features[0]['text'] }}</p>
                </div>
            </article>

            <article class="flex flex-col rounded-3xl border border-line-border bg-surface-card p-3 lg:col-span-5">
                <div class="relative flex min-h-80 flex-1 items-center justify-center overflow-hidden rounded-2xl bg-surface-base/70 p-6">
                    <div class="pointer-events-none absolute left-1/2 top-0 size-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary/20 blur-3xl" aria-hidden="true"></div>

                    <div x-data="{ linked: false }"
                         x-init="setInterval(() => linked = !linked, 2800)"
                         dir="ltr"
                         class="relative w-full max-w-xs space-y-3">
                        <div class="flex items-center justify-between rounded-2xl border border-line-border bg-surface-card px-4 py-3.5 font-mono text-sm shadow-sm">
                            <span class="font-bold text-content-main">chat_id</span>
                            <span class="font-bold text-purple-500">984120531</span>
                        </div>

                        <div class="flex justify-center text-content-muted">
                            <x-ui.icon name="chevron-right" class="size-5 rotate-90"/>
                        </div>

                        <div :class="linked ? 'border-emerald-500/60 bg-emerald-500/10' : 'border-dashed border-line-border bg-surface-card'"
                             class="flex h-14 items-center justify-between rounded-2xl border px-4 font-mono text-sm transition-colors duration-300">
                            <span x-show="!linked" class="text-content-muted">Chat ID</span>
                            <span x-show="linked" x-cloak class="font-bold text-emerald-500">@{{ input.chat_id }}</span>
                            <span x-show="linked" x-cloak class="text-emerald-500">
                                <x-ui.icon name="check" class="size-5"/>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="px-4 pb-4 pt-6 sm:px-6">
                    <h3 class="text-2xl font-extrabold text-content-main">{{ $features[1]['title'] }}</h3>
                    <p class="mt-2 text-base leading-8 text-content-muted">{{ $features[1]['text'] }}</p>
                </div>
            </article>

            <article class="flex flex-col rounded-3xl border border-line-border bg-surface-card p-3 lg:col-span-5">
                <div class="relative flex min-h-80 flex-1 flex-col items-center justify-center gap-4 overflow-hidden rounded-2xl bg-surface-base/70 p-6">
                    <div class="pointer-events-none absolute left-1/2 top-0 size-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-emerald-500/20 blur-3xl" aria-hidden="true"></div>

                    <div class="relative inline-flex items-center gap-2.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 py-1.5 text-sm font-bold text-emerald-600 dark:text-emerald-400">
                        <span class="relative flex size-2.5">
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-500 opacity-75 motion-reduce:animate-none"></span>
                            <span class="relative inline-flex size-2.5 rounded-full bg-emerald-500"></span>
                        </span>
                        منتشر شد
                    </div>

                    <div class="relative w-full max-w-xs space-y-3">
                        <div class="flex items-center gap-3 rounded-2xl border border-line-border bg-surface-card px-4 py-3.5 shadow-sm">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500">
                                <x-ui.icon name="brand:telegram" class="size-5"/>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-content-main">وب‌هوک تلگرام</p>
                                <p class="text-xs text-content-muted">خودکار ثبت شد</p>
                            </div>
                            <span class="text-emerald-500"><x-ui.icon name="check" class="size-5"/></span>
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl border border-line-border bg-surface-card px-4 py-3.5 shadow-sm">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
                                <x-ui.icon name="clock" class="size-5"/>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-content-main">زمان‌بندی</p>
                                <p class="text-xs text-content-muted">هر ۱۵ دقیقه، به وقت تهران</p>
                            </div>
                            <span class="text-emerald-500"><x-ui.icon name="check" class="size-5"/></span>
                        </div>
                    </div>
                </div>

                <div class="px-4 pb-4 pt-6 sm:px-6">
                    <h3 class="text-2xl font-extrabold text-content-main">{{ $features[2]['title'] }}</h3>
                    <p class="mt-2 text-base leading-8 text-content-muted">{{ $features[2]['text'] }}</p>
                </div>
            </article>

            <article class="flex flex-col rounded-3xl border border-line-border bg-surface-card p-3 lg:col-span-7">
                <div x-data="{
                         date: '',
                         day: '',
                         time: '',
                         digits: '۰۱۲۳۴۵۶۷۸۹',
                         otp: Array.from({ length: 6 }, () => Math.floor(Math.random() * 10)),
                         fmt(options) {
                             return new Intl.DateTimeFormat('fa-IR-u-ca-persian', { timeZone: 'Asia/Tehran', ...options });
                         },
                         tick() {
                             const now = new Date();
                             const p = Object.fromEntries(this.fmt({ year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(now).map((x) => [x.type, x.value]));
                             this.date = p.year + '/' + p.month + '/' + p.day;
                             this.day = this.fmt({ weekday: 'long' }).format(now).replace(/^(یک|پنج)شنبه$/, '$1\u200cشنبه') + '، ' + this.fmt({ day: 'numeric', month: 'long' }).format(now);
                             this.time = this.fmt({ hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' }).format(now);
                         },
                         init() {
                             this.tick();
                             setInterval(() => this.tick(), 1000);
                         }
                     }"
                     class="relative flex min-h-80 flex-1 flex-col items-center justify-center gap-8 overflow-hidden rounded-2xl bg-surface-base/70 p-6">
                    <div class="pointer-events-none absolute left-1/2 top-0 size-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-amber-500/20 blur-3xl" aria-hidden="true"></div>

                    <div class="relative flex flex-col items-center">
                        <p dir="ltr" x-text="date" class="h-[1.2em] text-5xl font-extrabold tracking-tight text-content-main sm:text-6xl"></p>
                        <div class="mt-4 flex items-center gap-3 text-sm font-semibold text-content-muted">
                            <span x-text="day"></span>
                            <span class="h-4 w-px bg-line-border"></span>
                            <span class="flex items-center gap-1.5 text-amber-500">
                                <x-ui.icon name="clock" class="size-4"/>
                                <span dir="ltr" x-text="time" class="min-w-16 text-start"></span>
                            </span>
                        </div>
                    </div>

                    <div dir="ltr" class="relative flex items-center gap-2">
                        <template x-for="(d, i) in otp" :key="i">
                            <span x-text="digits[d]" class="flex size-12 items-center justify-center rounded-xl border border-amber-500/40 bg-surface-card text-xl font-extrabold text-amber-500 shadow-sm"></span>
                        </template>
                    </div>
                </div>

                <div class="px-4 pb-4 pt-6 sm:px-6">
                    <h3 class="text-2xl font-extrabold text-content-main">{{ $features[3]['title'] }}</h3>
                    <p class="mt-2 max-w-lg text-base leading-8 text-content-muted">{{ $features[3]['text'] }}</p>
                </div>
            </article>
        </div>
    </div>
</section>

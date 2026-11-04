@php
    $nodes = [
        ['icon' => 'brand:telegram', 'label' => 'پیام تلگرام', 'x' => '10%', 'y' => '50%', 'delay' => '0s', 'glow' => '#229ED9'],
        ['icon' => 'brand:gmail', 'label' => 'ارسال ایمیل', 'x' => '90%', 'y' => '15%', 'delay' => '-2s', 'glow' => '#EA4335'],
        ['icon' => 'brand:google-sheets', 'label' => 'ثبت در شیت', 'x' => '90%', 'y' => '85%', 'delay' => '-4s', 'glow' => '#34A853'],
    ];

    $paths = [
        ['d' => 'M110 175 L350 175', 'dur' => '2.4s', 'begin' => '0s'],
        ['d' => 'M350 175 C500 175 520 55 690 55', 'dur' => '2.2s', 'begin' => '.8s'],
        ['d' => 'M350 175 C500 175 520 295 690 295', 'dur' => '2.2s', 'begin' => '1.6s'],
    ];
@endphp

<section id="hero-spotlight" class="relative isolate w-full overflow-hidden bg-surface-base font-vazirmatn-rd">
    <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
        <div class="absolute inset-0"
             style="background:
                radial-gradient(45% 40% at 75% 10%, color-mix(in oklab, var(--color-primary) 18%, transparent), transparent 70%),
                radial-gradient(40% 35% at 15% 85%, color-mix(in oklab, #f43f5e 10%, transparent), transparent 70%);"></div>
        <div class="hero-grid absolute inset-0 opacity-70"></div>
        <div class="hero-noise absolute inset-0 opacity-[0.35] mix-blend-overlay"></div>
        <div id="spotlight" class="absolute left-0 top-0 size-150 rounded-full opacity-0 transition-opacity duration-500 will-change-transform"
             style="background: radial-gradient(circle, color-mix(in oklab, var(--color-primary) 14%, transparent), transparent 60%);"></div>
    </div>

    <div class="mx-auto flex max-w-5xl flex-col items-center px-4 pb-20 pt-20 text-center sm:px-6 sm:pb-28 sm:pt-32">
        <h1 class="hero-title max-w-4xl text-3xl font-extrabold leading-[1.6] tracking-tight text-content-main xs:text-4xl sm:text-5xl md:text-6xl">
            اتوماسیون کارها،
            <span class="hero-gradient-shift relative inline-block bg-linear-to-l from-primary via-rose-400 to-primary bg-clip-text text-transparent bg-size-[200%_auto]">
                ساده‌تر از همیشه
            </span>
        </h1>

        <p class="mt-5 max-w-2xl px-2 text-sm leading-7 text-content-muted sm:mt-6 sm:px-0 sm:text-lg sm:leading-8">
            گردش‌کارهای خود را به‌سادگی ایجاد و مدیریت کنید. کارهای تکراری را خودکار کنید و زمان بیشتری برای کارهای مهم داشته باشید.
        </p>

        <div class="mt-8 flex flex-row flex-wrap items-center justify-center gap-3 sm:mt-10">
            <x-ui.button href="/register" size="lg" class="shadow-lg shadow-primary/25 transition-transform hover:scale-[1.03]">
                شروع رایگان
            </x-ui.button>
            <x-ui.button href="#" variant="secondary" size="lg" class="backdrop-blur transition hover:bg-surface-card">
                مشاهده مستندات
            </x-ui.button>
        </div>

        <div class="mt-14 w-full max-w-3xl px-2 sm:mt-16 sm:px-0">
            <div dir="ltr" class="relative mx-auto aspect-video w-full sm:aspect-16/7">
                <svg viewBox="0 0 800 350" class="absolute inset-0 size-full" fill="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="hero-line" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0" stop-color="var(--color-primary)" stop-opacity=".1"/>
                            <stop offset=".5" stop-color="var(--color-primary)" stop-opacity=".55"/>
                            <stop offset="1" stop-color="var(--color-primary)" stop-opacity=".1"/>
                        </linearGradient>
                        <filter id="hero-glow" x="-50%" y="-50%" width="200%" height="200%">
                            <feGaussianBlur stdDeviation="4" result="b"/>
                            <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                    </defs>

                    @foreach ($paths as $path)
                        <path d="{{ $path['d'] }}" stroke="url(#hero-line)" stroke-width="2" stroke-linecap="round" stroke-dasharray="3 9" class="hero-flow"/>
                        <circle r="4" fill="var(--color-primary)" filter="url(#hero-glow)" class="hero-dot">
                            <animateMotion dur="{{ $path['dur'] }}" begin="{{ $path['begin'] }}" repeatCount="indefinite" path="{{ $path['d'] }}"/>
                        </circle>
                    @endforeach
                </svg>

                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <div class="hero-pulse absolute -inset-3 rounded-full bg-primary/25 blur-xl sm:-inset-4"></div>
                    <div class="hero-orbit absolute -inset-2 rounded-full border border-primary/30 sm:-inset-3"></div>
                    <div class="hero-orbit-reverse absolute -inset-4 rounded-full border border-dashed border-primary/15 sm:-inset-6"></div>
                    <div class="relative flex size-16 items-center justify-center rounded-full border border-primary/40 bg-surface-card shadow-2xl shadow-primary/25 transition-transform duration-300 hover:scale-105 sm:size-24">
                        <div class="absolute inset-0 rounded-full bg-linear-to-tr from-primary/10 to-transparent"></div>
                        <x-ui.icon name="brand:ai-agent" class="relative size-7 text-primary sm:size-11"/>
                    </div>
                    <span class="absolute -bottom-7 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full border border-primary/25 bg-surface-card/80 px-2.5 py-0.5 text-[10px] font-bold text-primary backdrop-blur sm:-bottom-8 sm:px-3 sm:text-xs">ایجنت هوشمند</span>
                </div>

                @foreach ($nodes as $node)
                    <div class="hero-float group absolute -translate-x-1/2 -translate-y-1/2"
                         style="left: {{ $node['x'] }}; top: {{ $node['y'] }}; animation-delay: {{ $node['delay'] }};">
                        <div class="relative flex size-12 items-center justify-center rounded-xl border border-line-border bg-surface-card/80 shadow-lg backdrop-blur transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-xl sm:size-18 sm:rounded-2xl">
                            <div class="absolute inset-0 rounded-xl opacity-0 transition-opacity duration-300 group-hover:opacity-100 sm:rounded-2xl"
                                 style="background: radial-gradient(80% 80% at 50% 50%, color-mix(in oklab, {{ $node['glow'] }} 25%, transparent), transparent);"></div>
                            <x-ui.icon :name="$node['icon']" class="relative size-6 transition-transform duration-300 group-hover:scale-110 sm:size-9"/>
                        </div>
                        <span class="absolute -bottom-5 left-1/2 -translate-x-1/2 whitespace-nowrap text-[10px] font-medium text-content-muted sm:-bottom-6 sm:text-xs">{{ $node['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

@once
    <style>
        @keyframes hero-dash { to { stroke-dashoffset: -48; } }
        @keyframes hero-float { 0%, 100% { translate: 0 0; } 50% { translate: 0 -10px; } }
        @keyframes hero-pulse { 0% { transform: scale(.85); opacity: .6; } 100% { transform: scale(1.6); opacity: 0; } }
        @keyframes hero-orbit { to { transform: rotate(360deg); } }
        @keyframes hero-gradient { to { background-position: 200% center; } }

        .hero-flow { animation: hero-dash 2.4s linear infinite; }
        .hero-float { animation: hero-float 7s ease-in-out infinite; will-change: translate; }
        .hero-pulse { animation: hero-pulse 3s ease-out infinite; will-change: transform, opacity; }
        .hero-gradient-shift { animation: hero-gradient 4s linear infinite; }

        .hero-orbit {
            animation: hero-orbit 12s linear infinite;
            border-top-color: transparent;
            border-bottom-color: transparent;
            will-change: transform;
        }
        .hero-orbit-reverse {
            animation: hero-orbit 20s linear infinite reverse;
            border-left-color: transparent;
            border-right-color: transparent;
            will-change: transform;
        }

        .hero-grid {
            background-image:
                linear-gradient(color-mix(in oklab, var(--color-line-border) 60%, transparent) 1px, transparent 1px),
                linear-gradient(90deg, color-mix(in oklab, var(--color-line-border) 60%, transparent) 1px, transparent 1px);
            background-size: 32px 32px;
            -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, #000 20%, transparent 100%);
            mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, #000 20%, transparent 100%);
        }

        .hero-noise {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='120' height='120' filter='url(%23n)' opacity='0.4'/%3E%3C/svg%3E");
        }

        @media (prefers-reduced-motion: reduce) {
            .hero-flow, .hero-float, .hero-pulse, .hero-orbit, .hero-orbit-reverse, .hero-gradient-shift { animation: none; will-change: auto; }
            .hero-dot { display: none; }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const section = document.getElementById('hero-spotlight');
            const spot = document.getElementById('spotlight');
            if (!section || !spot || matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            let rafId = null;

            section.addEventListener('pointermove', e => {
                if (rafId) return;
                rafId = requestAnimationFrame(() => {
                    const r = section.getBoundingClientRect();
                    spot.style.opacity = '1';
                    spot.style.transform = `translate3d(${e.clientX - r.left - 300}px, ${e.clientY - r.top - 300}px, 0)`;
                    rafId = null;
                });
            }, { passive: true });

            section.addEventListener('pointerleave', () => {
                spot.style.opacity = '0';
            }, { passive: true });
        });
    </script>
@endonce

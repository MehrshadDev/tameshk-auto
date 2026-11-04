<section id="what-is-tameshk" class="scroll-mt-24 space-y-3">
    <h2 class="text-lg font-extrabold text-content-main">تمشک چیست؟</h2>
    <p class="text-content-muted">
        <strong class="text-content-main">تمشک (Tameshk)</strong> یک پلتفرم بصری برای اتوماسیون گردش‌کار و ساخت ایجنت‌های هوشمند است. با تمشک می‌توانید بدون درگیری با کدنویسی‌های پیچیده، سرویس‌های مختلف مثل <strong class="text-content-main">تلگرام، جیمیل، گوگل شیت، وب‌سرویس‌ها (HTTP) و مدل‌های هوش مصنوعی</strong> (OpenAI، Gemini، Claude، DeepSeek، Groq و OpenRouter) را به یکدیگر متصل کنید.
    </p>
</section>

<section id="core-concepts" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">اجزای اصلی یک ورکفلو</h2>
    <p class="text-content-muted">
        هر ورکفلو در تمشک از دو بخش اصلی تشکیل شده است:
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li>
            <strong class="text-content-main">نودها (Nodes):</strong> هر بلوک روی بوم یک «نود» است که کار مشخصی (مثل دریافت پیام، پردازش با هوش مصنوعی، بررسی شرط یا ذخیره در شیت) انجام می‌دهد.
        </li>
        <li>
            <strong class="text-content-main">اتصالات (Edges):</strong> خطوطی که خروجی یک نود را به ورودی نود بعدی وصل می‌کنند و مسیر حرکت داده‌ها را نشان می‌دهند.
        </li>
    </ul>
</section>

<section id="node-categories" class="scroll-mt-24 space-y-4 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">دسته‌بندی نودها در تمشک</h2>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border border-line-border bg-surface-base/60 p-4">
            <div class="flex items-center gap-2.5">
                <span class="flex size-8 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
                    <x-ui.icon name="player-play" class="size-4.5"/>
                </span>
                <h3 class="text-xs font-extrabold text-content-main">۱. شروع‌کننده‌ها (Triggers)</h3>
            </div>
            <p class="mt-2 text-xs leading-6 text-content-muted">
                نقطه آغاز هر ورکفلو هستند؛ مثل دریافت پیام در تلگرام، دریافت ایمیل در جیمیل، تغییر ردیف در گوگل شیت، زمان‌بندی یا وبهوک. هر ورکفلو حداکثر یک شروع‌کننده دارد.
            </p>
        </div>

        <div class="rounded-2xl border border-line-border bg-surface-base/60 p-4">
            <div class="flex items-center gap-2.5">
                <span class="flex size-8 items-center justify-center rounded-xl bg-purple-500/10 text-purple-500">
                    <x-ui.icon name="brand:ai-agent" class="size-4.5"/>
                </span>
                <h3 class="text-xs font-extrabold text-content-main">۲. هوش مصنوعی (AI & Agents)</h3>
            </div>
            <p class="mt-2 text-xs leading-6 text-content-muted">
                شامل نود <strong>AI Agent</strong>، مدل‌های چت، حافظه گفتگو (Simple Memory) و ابزارهای هوشمند (Tools) برای تصمیم‌گیری و اجرای خودکار کارها.
            </p>
        </div>

        <div class="rounded-2xl border border-line-border bg-surface-base/60 p-4">
            <div class="flex items-center gap-2.5">
                <span class="flex size-8 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <x-ui.icon name="world" class="size-4.5"/>
                </span>
                <h3 class="text-xs font-extrabold text-content-main">۳. عملیات در سرویس‌ها (Actions)</h3>
            </div>
            <p class="mt-2 text-xs leading-6 text-content-muted">
                ارسال پیام و فایل در تلگرام، ارسال یا پاسخ به ایمیل در جیمیل، خواندن و نوشتن در Google Sheets، فراخوانی API با HTTP Request و سرویس‌های چندوجهی Gemini.
            </p>
        </div>

        <div class="rounded-2xl border border-line-border bg-surface-base/60 p-4">
            <div class="flex items-center gap-2.5">
                <span class="flex size-8 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500">
                    <x-ui.icon name="arrows-split-2" class="size-4.5"/>
                </span>
                <h3 class="text-xs font-extrabold text-content-main">۴. منطق و تغییر داده (Logic & Transform)</h3>
            </div>
            <p class="mt-2 text-xs leading-6 text-content-muted">
                شاخه‌بندی شرطی (If / Switch)، توقف و تاخیر زمانی (Delay)، ویرایش فیلدها (Set)، پردازش لیست‌ها، عملیات متنی، تاریخ شمسی و میلادی و مخزن داده (Data Store).
            </p>
        </div>
    </div>
</section>

<section id="execution-modes" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">حالت تست در برابر حالت انتشار (Publish)</h2>
    <p class="text-content-muted">
        هنگام طراحی در ویرایشگر، می‌توانید هر نود را به صورت تکی یا کل ورکفلو را با دکمه <strong class="text-emerald-500">«تست اجرا»</strong> آزمایش کنید و ورودی و خروجی هر مرحله را ببینید.
    </p>
    <x-docs.callout type="info" title="اجرای خودکار در پس‌زمینه">
        پس از اطمینان از درستی ورکفلو، با کلیک روی دکمه <strong>«انتشار» (Publish)</strong> در نوار بالای ویرایشگر، وبهوک‌ها و زمان‌بندی‌ها فعال می‌شوند و ورکفلو بدون نیاز به باز بودن مرورگر، به صورت ۲۴ ساعته کار می‌کند.
    </x-docs.callout>
</section>

<section id="manual-trigger" class="scroll-mt-24 space-y-3">
    <h2 class="text-lg font-extrabold text-content-main">شروع‌کننده دستی (Manual Trigger)</h2>
    <p class="text-content-muted">
        ساده‌ترین شروع‌کننده در تمشک است که برای آزمایش و اجرای دستی ورکفلو در محیط ویرایشگر کاربرد دارد. هر زمان که روی دکمه <strong class="text-emerald-500">«تست اجرا»</strong> در بالای صفحه کلیک کنید، اجرای ورکفلو از این نود آغاز می‌شود.
    </p>
    <x-docs.callout type="warning" title="نکته درباره انتشار">
        نود <code>Manual Trigger</code> فقط برای محیط تست طراحی شده است. برای اینکه ورکفلو قابل انتشار (Publish) باشد، باید از شروع‌کننده‌های خودکار مثل وبهوک، زمان‌بندی، چت یا سرویس‌ها استفاده کنید.
    </x-docs.callout>
</section>

<section id="schedule-trigger" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">زمان‌بندی خودکار (Schedule Trigger)</h2>
    <p class="text-content-muted">
        با این شروع‌کننده می‌توانید ورکفلو را در بازه‌های زمانی منظم (هر چند دقیقه، ساعت، روز، هفته یا با عبارت <code>Cron</code> سفارشی) به صورت خودکار اجرا کنید.
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">بازه تکرار:</strong> امکان انتخاب دقیقه، ساعت، روز، روز مشخصی از هفته یا عبارت استاندارد ۵ بخشی Cron.</li>
        <li><strong class="text-content-main">منطقه زمانی (Timezone):</strong> به صورت پیش‌فرض روی <code>Asia/Tehran</code> تنظیم شده تا ساعت اجرای روزانه یا هفتگی دقیقا با ساعت ایران هماهنگ باشد.</li>
    </ul>

    <x-docs.vars
        title="داده‌های خروجی زمان‌بندی (Schedule Trigger)"
        :items="[
            ['label' => 'تاریخ اجرای ورکفلو (مثلا 2026-11-04)', 'expr' => 'input.readable_date'],
            ['label' => 'ساعت دقیق اجرا (مثلا 09:00:00)', 'expr' => 'input.readable_time'],
            ['label' => 'نام انگلیسی روز هفته', 'expr' => 'input.day_of_week'],
            ['label' => 'تاریخ و زمان استاندارد ISO', 'expr' => 'input.iso'],
        ]"
    />
</section>

<section id="webhook-trigger" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">دریافت وبهوک (Webhook Trigger)</h2>
    <p class="text-content-muted">
        نود <strong>Webhook</strong> یک آدرس اینترنتی (URL) اختصاصی در اختیار شما قرار می‌دهد تا هر زمان سایت، فروشگاه اینترنتی یا سرویس خارجی درخواستی به آن ارسال کرد، ورکفلو بلافاصله اجرا شود.
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">Test URL در برابر Production URL:</strong> در زمان طراحی، روی دکمه «گوش دادن به رویداد تستی» کلیک کنید و درخواست را به <code>Test URL</code> بفرستید. پس از انتشار ورکفلو، از <code>Production URL</code> استفاده نمایید.</li>
        <li><strong class="text-content-main">مسیر سفارشی (Path):</strong> می‌توانید آدرس انتهایی وبهوک را به مسیر دلخواه خود (مثلا <code>orders/new</code>) تغییر دهید.</li>
        <li><strong class="text-content-main">احراز هویت:</strong> پشتیبانی از حالت‌های بدون احراز هویت، توکن <code>Bearer</code> و <code>Basic Auth</code>.</li>
        <li><strong class="text-content-main">نحوه پاسخ‌دهی (Respond):</strong> می‌توانید مشخص کنید وبهوک بلافاصله پاسخ دهد یا صبر کند تا آخرین نود ورکفلو تمام شود و خروجی همان نود را به عنوان پاسخ HTTP برگرداند.</li>
    </ul>

    <x-docs.vars
        title="داده‌های خروجی وبهوک (Webhook)"
        :items="[
            ['label' => 'داده‌های ارسالی در بدنه درخواست (JSON / Form Body)', 'expr' => 'input.body'],
            ['label' => 'پارامترهای آدرس (Query Parameters)', 'expr' => 'input.query'],
            ['label' => 'هدرهای درخواست HTTP', 'expr' => 'input.headers'],
            ['label' => 'آدرس IP فرستنده درخواست', 'expr' => 'input.ip'],
        ]"
    />
</section>

<section id="chat-trigger" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">شروع‌کننده چت (On Chat Message)</h2>
    <p class="text-content-muted">
        این نود یک صفحه چت عمومی برای ورکفلوی شما می‌سازد. می‌توانید عنوان صفحه، متن راهنمای کادر پیام و پیام خوشامدگویی اولیه را شخصی‌سازی کنید.
    </p>

    <x-docs.vars
        title="داده‌های خروجی شروع‌کننده چت"
        :items="[
            ['label' => 'متن پیام ارسال‌شده توسط کاربر در چت', 'expr' => 'input.chatInput'],
            ['label' => 'شناسه یکتای نشست کاربر (برای تفکیک حافظه)', 'expr' => 'input.sessionId'],
        ]"
    />
</section>

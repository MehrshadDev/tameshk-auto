<section id="data-flow" class="scroll-mt-24 space-y-3">
    <h2 class="text-lg font-extrabold text-content-main">جریان داده بین نودها (JSON)</h2>
    <p class="text-content-muted">
        در تمشک، هر نود پس از اجرا خروجی خود را تولید کرده و به نود بعدی می‌فرستد. وقتی روی یک نود دوبار کلیک می‌کنید، پنجره تنظیمات با ۳ ستون باز می‌شود:
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">ستون راست (داده‌های ورودی - Input):</strong> اطلاعاتی که از نودهای قبلی به این نود رسیده‌اند.</li>
        <li><strong class="text-content-main">ستون وسط (تنظیمات - Parameters):</strong> فیلدها و گزینه‌های نود فعلی.</li>
        <li><strong class="text-content-main">ستون چپ (نتیجه خروجی - Output):</strong> داده‌ای که این نود پس از اجرا تولید کرده است.</li>
    </ul>
</section>

<section id="drag-and-drop" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">کشیدن و رها کردن فیلدها (ساده‌ترین روش)</h2>
    <p class="text-content-muted">
        برای استفاده از خروجی نود قبلی نیازی به نوشتن هیچ کدی ندارید! کافی است یک بار نود قبلی را تست کنید تا خروجی آن در <strong>ستون راست (داده‌های ورودی)</strong> ظاهر شود؛ سپس هر فیلدی را که می‌خواهید با ماوس بکشید (Drag) و داخل کادر مورد نظر در ستون وسط رها کنید (Drop).
    </p>
</section>

<section id="expression-syntax" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">استفاده از متغیرها در حالت (fx)</h2>
    <p class="text-content-muted">
        علاوه بر کشیدن و رها کردن، می‌توانید بالای هر فیلد متنی حالت را از <strong>«ثابت»</strong> به <strong class="text-primary">«متغیر (fx)»</strong> تغییر دهید و از عبارت‌های آماده زیر استفاده کنید:
    </p>

    <x-docs.vars
        title="نمونه متغیرهای پرکاربرد"
        :items="[
            ['label' => 'متن پیام دریافتی از نود قبلی', 'expr' => 'input.text'],
            ['label' => 'شناسه چت کاربر در تلگرام', 'expr' => 'input.chat_id'],
            ['label' => 'پاسخ تولیدشده توسط ایجنت یا مدل هوش مصنوعی', 'expr' => 'input.response'],
            ['label' => 'مقدار ستون email در اولین ردیف خوانده‌شده از شیت', 'expr' => 'input.rows[0].email'],
        ]"
    />

    <x-docs.callout type="info" title="بررسی خودکار درستی متغیر">
        اگر قبلا نودهای قبلی را اجرا کرده باشید، به محض قرار دادن متغیر، در صورتی که آن داده در خروجی وجود داشته باشد کادر ورودی به رنگ <strong class="text-emerald-500">سبز (معتبر)</strong> نمایش داده می‌شود.
    </x-docs.callout>
</section>

<section id="referencing-nodes" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">دسترسی به خروجی نودهای چند مرحله قبل</h2>
    <p class="text-content-muted">
        در ستون داده‌های ورودی (Input)، تمام نودهای قبلی به ترتیب فهرست می‌شوند. اگر فیلدی را از نودهای ۲ یا چند مرحله قبل بکشید و رها کنید، تمشک به صورت خودکار نام آن نود را در متغیر قرار می‌دهد:
    </p>

    <x-docs.vars
        title="نمونه دسترسی مستقیم به نودهای قبلی با نام نود"
        :items="[
            ['label' => 'خواندن شناسه چت از شروع‌کننده تلگرام در انتهای ورکفلو', 'expr' => '$(\'Telegram Trigger\').item.json.chat_id'],
            ['label' => 'خواندن پاسخ ایجنت هوش مصنوعی در مراحل بعدی', 'expr' => '$(\'AI Agent\').item.json.response'],
            ['label' => 'خواندن پیام اولیه شروع‌کننده از هر جای ورکفلو', 'expr' => 'trigger.chatInput'],
        ]"
    />
</section>

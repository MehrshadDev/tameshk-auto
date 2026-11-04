<section id="telegram-action" class="scroll-mt-24 space-y-3">
    <h2 class="text-lg font-extrabold text-content-main">نود عملیاتی تلگرام (Telegram)</h2>
    <p class="text-content-muted">
        نود <strong>Telegram</strong> به شما اجازه می‌دهد انواع عملیات ربات را در چت خصوصی، گروه یا کانال اجرا کنید:
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">Send text message:</strong> ارسال پیام متنی با پشتیبانی از قالب‌بندی <code>HTML</code> و <code>Markdown</code>، ریپلای روی پیام و ارسال بی‌صدا.</li>
        <li><strong class="text-content-main">Send photo / Send document:</strong> ارسال تصویر یا فایل از طریق لینک مستقیم اینترنتی یا <code>file_id</code> تلگرام به همراه کپشن.</li>
        <li><strong class="text-content-main">Edit / Delete / Pin message:</strong> ویرایش متن پیام قبلی ربات، حذف پیام یا پین کردن پیام با استفاده از <code>chat_id</code> و <code>message_id</code>.</li>
        <li><strong class="text-content-main">Answer callback query:</strong> پاسخ به کلیک کاربر روی دکمه‌های شیشه‌ای (به صورت اعلان بالای صفحه یا پنجره پاپ‌آپ).</li>
        <li><strong class="text-content-main">Send chat action:</strong> نمایش وضعیت «در حال نوشتن...» (<code>typing</code>) یا «در حال ارسال فایل...» در بالای صفحه چت کاربر پیش از آماده شدن پاسخ هوش مصنوعی.</li>
    </ul>
</section>

<section id="gmail-action" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">نود عملیاتی جیمیل (Gmail)</h2>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">ارسال ایمیل (Send Message):</strong> ارسال ایمیل به صورت متن ساده یا <code>HTML</code> به همراه فیلدهای <code>CC</code> و <code>BCC</code>.</li>
        <li><strong class="text-content-main">پاسخ به ایمیل (Reply to Message):</strong> ارسال پاسخ به یک ایمیل مشخص با حفظ زنجیره گفتگو (Thread) تنها با دادن <code>Message ID</code>.</li>
        <li><strong class="text-content-main">دریافت و جستجو (Get / Search Messages):</strong> دریافت جزئیات کامل یک ایمیل یا جستجو در صندوق پستی با عبارت‌های فیلتر جیمیل (مانند <code>is:unread</code>).</li>
        <li><strong class="text-content-main">تغییر برچسب و حذف (Modify / Trash):</strong> تغییر وضعیت ایمیل به خوانده‌شده، خوانده‌نشده، ستاره‌دار یا انتقال به سطل زباله.</li>
    </ul>
</section>

<section id="sheets-action" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">نود گوگل شیت (Google Sheets)</h2>
    <p class="text-content-muted">
        پس از انتخاب حساب گوگل، می‌توانید اسپردشیت و برگه (Sheet) را از لیست انتخاب کرده و یکی از ۴ عملیات زیر را انجام دهید:
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">افزودن ردیف (Append Row):</strong> اضافه کردن یک یا چند ردیف به انتهای شیت. مقادیر را می‌توانید به صورت آرایه JSON یا مقادیر جداشده با کاما وارد کنید.</li>
        <li><strong class="text-content-main">خواندن ردیف‌ها (Get Rows):</strong> دریافت سطرهای شیت. اگر گزینه «ستون‌های نام‌گذاری‌شده» فعال باشد، هر ردیف بر اساس عنوان ستون‌های سطر اول شیت به صورت کلید-مقدار برگردانده می‌شود.</li>
        <li><strong class="text-content-main">به‌روزرسانی ردیف (Update Row):</strong> ویرایش مقادیر یک بازه مشخص (مثلا <code>A2:D2</code>).</li>
        <li><strong class="text-content-main">پاک کردن بازه (Clear):</strong> پاک‌سازی مقادیر سلول‌ها بدون حذف ساختار شیت.</li>
    </ul>

    <x-docs.code
        label="نمونه مقدار ورودی برای فیلد «مقادیر ردیف» در Google Sheets"
        :lines="[
            '[&quot;[[ input.first_name ]]&quot;, &quot;[[ input.username ]]&quot;, &quot;ثبت‌شده&quot;]',
        ]"
    />
</section>

<section id="http-request-action" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">نود درخواست وب (HTTP Request)</h2>
    <p class="text-content-muted">
        برای اتصال به هر وب‌سرویس خارجی (مثل سامانه‌های پیامکی، CRM، درگاه‌ها یا API سایت خودتان) از نود <strong>HTTP Request</strong> استفاده کنید. این نود از متدهای <code>GET</code>، <code>POST</code>، <code>PUT</code>، <code>PATCH</code> و <code>DELETE</code> به همراه هدرهای دلخواه و بدنه JSON پشتیبانی می‌کند.
    </p>

    <x-docs.vars
        title="داده‌های خروجی نود HTTP Request"
        :items="[
            ['label' => 'پاسخ دریافتی از سرور مقصد (JSON یا متن)', 'expr' => 'input.body'],
            ['label' => 'کد وضعیت HTTP پاسخ (مثلا 200)', 'expr' => 'input.status'],
            ['label' => 'هدرهای پاسخ سرور', 'expr' => 'input.headers'],
        ]"
    />
</section>

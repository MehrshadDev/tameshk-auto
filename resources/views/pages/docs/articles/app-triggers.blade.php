<section id="telegram-trigger" class="scroll-mt-24 space-y-3">
    <h2 class="text-lg font-extrabold text-content-main">شروع‌کننده ربات تلگرام (Telegram Trigger)</h2>
    <p class="text-content-muted">
        با استفاده از این نود، هر زمان کاربری به ربات تلگرام شما پیام بفرستد یا روی دکمه‌های شیشه‌ای (Inline Buttons) کلیک کند، ورکفلو به صورت آنی اجرا می‌شود.
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">ثبت خودکار Webhook تلگرام:</strong> نیازی به تنظیم دستی وب‌هوک در تلگرام ندارید؛ به محض انتشار ورکفلو، تمشک به صورت خودکار وب‌هوک امن را روی ربات شما ثبت می‌کند.</li>
        <li><strong class="text-content-main">فیلتر نوع رویداد (Trigger On):</strong> می‌توانید مشخص کنید ورکفلو فقط با پیام جدید (<code>message</code>)، کلیک روی دکمه (<code>callback_query</code>)، پست کانال (<code>channel_post</code>) یا سایر رویدادها فعال شود.</li>
    </ul>

    <x-docs.vars
        title="داده‌های خروجی شروع‌کننده تلگرام (برای استفاده در نودهای بعدی)"
        :items="[
            ['label' => 'شناسه چت کاربر یا گروه (برای ارسال پاسخ در نود تلگرام)', 'expr' => 'input.chat_id'],
            ['label' => 'متن پیام ارسال‌شده توسط کاربر', 'expr' => 'input.text'],
            ['label' => 'نام کوچک کاربر در تلگرام', 'expr' => 'input.first_name'],
            ['label' => 'نام کاربری (Username) در تلگرام', 'expr' => 'input.username'],
            ['label' => 'شناسه پیام (برای ریپلای، ویرایش یا حذف پیام)', 'expr' => 'input.message_id'],
            ['label' => 'شناسه کلیک دکمه شیشه‌ای (برای عملیات Answer Callback)', 'expr' => 'input.callback_query_id'],
        ]"
    />

    <x-docs.callout type="info" title="نکته مهم هنگام تست دستی">
        اگر ورکفلو در حالت «منتشر شده» باشد، وب‌هوک تلگرام فعال است. برای استفاده از دکمه «دریافت پیام تستی» داخل ویرایشگر، ابتدا انتشار ورکفلو را موقتا لغو کنید.
    </x-docs.callout>
</section>

<section id="gmail-trigger" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">دریافت ایمیل جدید در جیمیل (On Email Received)</h2>
    <p class="text-content-muted">
        این شروع‌کننده صندوق ورودی حساب جیمیل متصل‌شده را بررسی می‌کند و با دریافت ایمیل جدید منطبق با فیلتر شما، ورکفلو را راه می‌اندازد.
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">فیلتر پیام‌ها (Query):</strong> از عبارت‌های جستجوی استاندارد جیمیل پشتیبانی می‌کند؛ مثلا <code>is:unread label:INBOX</code> یا <code>from:support@example.com</code>.</li>
        <li><strong class="text-content-main">علامت‌گذاری به عنوان خوانده‌شده:</strong> از بخش گزینه‌های بیشتر می‌توانید مشخص کنید که ایمیل پس از پردازش به حالت خوانده‌شده (Read) تغییر کند تا دوباره پردازش نشود.</li>
    </ul>

    <x-docs.vars
        title="داده‌های خروجی شروع‌کننده جیمیل"
        :items="[
            ['label' => 'شناسه یکتای ایمیل (برای پاسخ دادن یا تغییر وضعیت)', 'expr' => 'input.id'],
            ['label' => 'آدرس ایمیل فرستنده', 'expr' => 'input.from'],
            ['label' => 'موضوع ایمیل (Subject)', 'expr' => 'input.subject'],
            ['label' => 'متن کامل ایمیل دریافت‌شده', 'expr' => 'input.text'],
            ['label' => 'شناسه رشته گفتگو (Thread ID)', 'expr' => 'input.threadId'],
        ]"
    />
</section>

<section id="sheets-trigger" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">تغییر یا افزودن ردیف در گوگل شیت (Google Sheets Triggers)</h2>
    <p class="text-content-muted">
        برای اتصال به Google Sheets سه شروع‌کننده در اختیار دارید:
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">On row added:</strong> اجرا هنگام اضافه شدن ردیف جدید در انتهای شیت.</li>
        <li><strong class="text-content-main">On row updated:</strong> اجرا هنگام ویرایش مقادیر ردیف‌های موجود.</li>
        <li><strong class="text-content-main">On row added or updated:</strong> اجرا در هر دو حالت افزودن یا ویرایش ردیف.</li>
    </ul>

    <x-docs.vars
        title="داده‌های خروجی شروع‌کننده گوگل شیت"
        :items="[
            ['label' => 'اطلاعات ردیف جدید یا ویرایش‌شده', 'expr' => 'input.row'],
            ['label' => 'شماره سطر در شیت', 'expr' => 'input.row_number'],
            ['label' => 'نوع تغییر رخ‌داده (added یا updated)', 'expr' => 'input.event'],
        ]"
    />
</section>

<section id="condition-node" class="scroll-mt-24 space-y-3">
    <h2 class="text-lg font-extrabold text-content-main">شرط دو مسیره (If Condition)</h2>
    <p class="text-content-muted">
        نود <strong>If Condition</strong> داده‌های ورودی را بررسی می‌کند و بر اساس برقرار بودن یا نبودن شروط، جریان ورکفلو را به خروجی <strong class="text-emerald-500">بله (true)</strong> یا <strong class="text-primary">خیر (false)</strong> هدایت می‌کند.
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">ترکیب شروط:</strong> می‌توانید مشخص کنید همه شروط با هم برقرار باشند (<code>AND</code>) یا برقرار بودن حداقل یکی از آن‌ها کافی باشد (<code>OR</code>).</li>
        <li><strong class="text-content-main">عملگرهای مقایسه:</strong> برابر بودن، نابرابری، شامل بودن متن (Contains)، شروع یا پایان با یک عبارت، بزرگ‌تر/کوچک‌تر بودن عددی، خالی بودن و تطابق با الگوی <code>Regex</code>.</li>
    </ul>
</section>

<section id="switch-node" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">مسیریابی چندشاخه (Switch)</h2>
    <p class="text-content-muted">
        زمانی که می‌خواهید ورکفلو را بر اساس یک مقدار (مثلا متن دستور کاربر در تلگرام مثل <code>/start</code>، <code>/help</code> یا <code>/support</code>) به چند مسیر مختلف تقسیم کنید، از نود <strong>Switch</strong> استفاده نمایید.
    </p>
    <p class="text-content-muted">
        این نود دارای ۴ خروجی است: <strong>حالت ۱</strong>، <strong>حالت ۲</strong>، <strong>حالت ۳</strong> و مسیر <strong>پیش‌فرض (Default)</strong> که در صورت عدم تطابق با سه حالت اول فعال می‌شود.
    </p>
</section>

<section id="delay-node" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">وقفه زمانی (Delay)</h2>
    <p class="text-content-muted">
        نود <strong>Delay</strong> اجرای ورکفلو را به مدت مشخصی (بین ۰ تا ۳۶۰۰ ثانیه) متوقف می‌کند و سپس داده ورودی را بدون تغییر به نود بعدی می‌فرستد.
    </p>
</section>

<section id="stop-error-node" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">توقف و خطا (Stop and Error)</h2>
    <p class="text-content-muted">
        با این نود می‌توانید اجرای یک شاخه را متوقف کنید. این نود دو حالت دارد:
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">توقف موفق مسیر (Stop Quietly):</strong> ادامه آن مسیر را بدون ثبت خطا متوقف می‌کند.</li>
        <li><strong class="text-content-main">توقف با خطا (Throw Error):</strong> اجرای کل ورکفلو را متوقف کرده و پیام خطای سفارشی شما را در گزارش اجراها ثبت می‌کند.</li>
    </ul>
</section>

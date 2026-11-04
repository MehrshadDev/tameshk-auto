<section id="edit-fields-node" class="scroll-mt-24 space-y-3">
    <h2 class="text-lg font-extrabold text-content-main">ویرایش و ساخت فیلدها (Edit Fields / Set)</h2>
    <p class="text-content-muted">
        نود <strong>Edit Fields (Set)</strong> برای ساخت فیلدهای جدید، تغییر نام فیلدها یا تمیزکاری ساختار JSON قبل از ارسال به نود بعدی استفاده می‌شود.
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">نگاشت دستی (Manual Mapping):</strong> تعریف فیلدها با تعیین نوع دقیق داده (<code>String</code>، <code>Number</code>، <code>Boolean</code>، <code>Array</code> و <code>Object</code>).</li>
        <li><strong class="text-content-main">حالت JSON:</strong> نوشتن مستقیم ساختار JSON خروجی به همراه متغیرها.</li>
        <li><strong class="text-content-main">پشتیبانی از نقطه‌گذاری:</strong> اگر نام فیلد را <code>user.profile.name</code> بگذارید، به صورت خودکار یک آبجکت تودرتو ساخته می‌شود.</li>
    </ul>
</section>

<section id="item-lists-node" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">پردازش آرایه‌ها و لیست‌ها (Item Lists)</h2>
    <p class="text-content-muted">
        وقتی خروجی نود قبلی یک لیست باشد (مثل ردیف‌های گوگل شیت یا پیام‌های جیمیل)، نود <strong>Item Lists</strong> شش عملیات کاربردی روی آن انجام می‌دهد:
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">Filter Items:</strong> فیلتر کردن آیتم‌های لیست بر اساس یک یا چند شرط.</li>
        <li><strong class="text-content-main">Sort:</strong> مرتب‌سازی صعودی یا نزولی آیتم‌ها بر اساس یک فیلد مشخص.</li>
        <li><strong class="text-content-main">Remove Duplicates:</strong> حذف موارد تکراری از لیست.</li>
        <li><strong class="text-content-main">Limit / Slice:</strong> انتخاب N آیتم اول یا آخر لیست.</li>
        <li><strong class="text-content-main">Pluck Field:</strong> استخراج یک فیلد خاص (مثلا فقط ایمیل‌ها) از تمام آیتم‌ها به صورت آرایه و رشته جداشده با کاما.</li>
        <li><strong class="text-content-main">Summarize:</strong> محاسبه مجموع (<code>sum</code>)، میانگین (<code>average</code>)، کمترین (<code>min</code>) و بیشترین (<code>max</code>) مقدار عددی در لیست.</li>
    </ul>
</section>

<section id="text-and-json" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">عملیات متنی (Text Operations) و مبدل JSON</h2>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li>
            <strong class="text-content-main">Text Operations:</strong> شامل جایگزینی متن (Find & Replace)، استخراج الگو با <code>Regex</code>، تفکیک متن به لیست (Split)، برش متن (Substring) و بخش بسیار کاربردی <strong>پاک‌سازی متن (Clean)</strong> برای حذف تگ‌های HTML و <strong>تبدیل اعداد فارسی/عربی به انگلیسی (۱۲۳ به 123) و برعکس</strong>.
        </li>
        <li>
            <strong class="text-content-main">JSON Parser:</strong> اگر خروجی مدل هوش مصنوعی شامل متن JSON داخل بلاک مارک‌داون باشد، این نود به صورت هوشمند JSON را استخراج و به فیلدهای مجزا و قابل استفاده تبدیل می‌کند.
        </li>
    </ul>
</section>

<section id="datetime-and-crypto" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">تاریخ شمسی/میلادی (Date & Time) و رمزنگاری (Crypto)</h2>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li>
            <strong class="text-content-main">Date & Time:</strong> دریافت تاریخ و زمان کنونی، افزودن یا کسر زمان (روز، ساعت، ماه و...) و محاسبه اختلاف دو تاریخ به همراه خروجی تقویم خورشیدی (شمسی).
        </li>
        <li>
            <strong class="text-content-main">Crypto & Random:</strong> تولید کد تایید عددی تصادفی ۴ تا ۱۰ رقمی (<code>OTP</code>)، تولید شناسه <code>UUID / ULID</code>، هش کردن متن (<code>SHA-256</code>, <code>SHA-512</code>, <code>MD5</code>)، امضای امنیتی <code>HMAC</code> و کدگذاری/رمزگشایی <code>Base64</code>.
        </li>
    </ul>

    <x-docs.vars
        title="متغیرهای خروجی تاریخ شمسی و کد تایید (OTP)"
        :items="[
            ['label' => 'تاریخ و ساعت کامل به شمسی (مثلا 1405/08/13 14:30:00)', 'expr' => 'input.persian_date'],
            ['label' => 'فقط تاریخ شمسی (مثلا 1405/08/13)', 'expr' => 'input.persian_ymd'],
            ['label' => 'نام فارسی روز هفته (مثلا چهارشنبه)', 'expr' => 'input.day_of_week_fa'],
            ['label' => 'کد تایید یا توکن تولیدشده در نود Crypto', 'expr' => 'input.value'],
        ]"
    />
</section>

<section id="datastore-node" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">مخزن داده دائمی (Data Store)</h2>
    <p class="text-content-muted">
        نود <strong>Data Store</strong> یک دیتابیس کلید-مقدار سریع در اختیار شما می‌گذارد که داده‌ها را بین اجراهای مختلف ورکفلو حفظ می‌کند:
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">محدوده دسترسی (Scope):</strong> قابل تنظیم روی «فقط همین ورکفلو» یا «مشترک بین تمام ورکفلوهای من».</li>
        <li><strong class="text-content-main">عملیات‌ها:</strong> خواندن مقدار یک کلید (<code>Get</code>)، ذخیره مقدار (<code>Set</code>)، افزایش یا کاهش شمارنده عددی (<code>Increment</code>)، حذف کلید (<code>Delete</code>) و دریافت کل داده‌ها (<code>Get All</code>).</li>
    </ul>
</section>

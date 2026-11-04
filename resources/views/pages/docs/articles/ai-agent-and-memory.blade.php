<section id="how-agent-works" class="scroll-mt-24 space-y-3">
    <h2 class="text-lg font-extrabold text-content-main">ایجنت هوش مصنوعی چگونه کار می‌کند؟</h2>
    <p class="text-content-muted">
        برخلاف یک درخواست ساده به مدل زبانی، نود <strong class="text-purple-500">AI Agent</strong> یک موجودیت خودمختار است که می‌تواند فکر کند، تاریخچه مکالمه را بخواند، در صورت نیاز از ابزارهای متصل‌شده (مثل جستجو در گوگل شیت، خواندن سایت یا ارسال ایمیل) تا ۸ مرحله متوالی استفاده کند و در نهایت پاسخ دقیق را برگرداند.
    </p>
</section>

<section id="agent-prompts" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">پرامپت کاربر و دستورالعمل سیستمی</h2>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li>
            <strong class="text-content-main">پرامپت کاربر (Prompt):</strong> پیامی است که در هر بار اجرا به ایجنت داده می‌شود. به صورت پیش‌فرض روی متغیر پیام ورودی قرار دارد که با تمام شروع‌کننده‌های چت، تلگرام، جیمیل و وبهوک سازگار است.
        </li>
        <li>
            <strong class="text-content-main">دستورالعمل‌های سیستمی (System Instructions):</strong> شخصیت، قوانین، لحن پاسخ‌گویی و وظایف ایجنت را در این بخش تعریف کنید (مثلا: «تو کارشناس فروش فروشگاه تمشک هستی، همیشه به زبان فارسی و محترمانه پاسخ بده و قبل از اعلام قیمت، لیست محصولات را با ابزار گوگل شیت بررسی کن»).
        </li>
    </ul>
</section>

<section id="chat-models" class="scroll-mt-24 space-y-4 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">اتصال مدل‌های زبانی (Chat Models)</h2>
    <p class="text-content-muted">
        هر ایجنت برای کار کردن باید از طریق درگاه <code>Chat Model*</code> به یکی از ۶ ارائه‌دهنده زیر متصل شود:
    </p>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border border-line-border bg-surface-base/60 p-3.5">
            <p class="text-xs font-extrabold text-content-main">۱. Google Gemini</p>
            <p class="mt-1 text-xs text-content-muted">دسترسی مستقیم به مدل‌های پرسرعت و هوشمند سری Gemini گوگل.</p>
        </div>
        <div class="rounded-2xl border border-line-border bg-surface-base/60 p-3.5">
            <p class="text-xs font-extrabold text-content-main">۲. OpenAI (ChatGPT)</p>
            <p class="mt-1 text-xs text-content-muted">پشتیبانی از مدل‌های پرچم‌دار GPT به همراه قابلیت تغییر Base URL.</p>
        </div>
        <div class="rounded-2xl border border-line-border bg-surface-base/60 p-3.5">
            <p class="text-xs font-extrabold text-content-main">۳. Anthropic Claude</p>
            <p class="mt-1 text-xs text-content-muted">مدل‌های دقیق Sonnet، Opus و Haiku؛ فوق‌العاده در نگارش و فراخوانی ابزار.</p>
        </div>
        <div class="rounded-2xl border border-line-border bg-surface-base/60 p-3.5">
            <p class="text-xs font-extrabold text-content-main">۴. DeepSeek</p>
            <p class="mt-1 text-xs text-content-muted">مدل‌های اقتصادی و استدلال‌گر DeepSeek Chat و Reasoner.</p>
        </div>
        <div class="rounded-2xl border border-line-border bg-surface-base/60 p-3.5">
            <p class="text-xs font-extrabold text-content-main">۵. Groq</p>
            <p class="mt-1 text-xs text-content-muted">پردازش فوق‌سریع مدل‌های متن‌باز Llama و Qwen روی تراشه‌های LPU.</p>
        </div>
        <div class="rounded-2xl border border-line-border bg-surface-base/60 p-3.5">
            <p class="text-xs font-extrabold text-content-main">۶. OpenRouter</p>
            <p class="mt-1 text-xs text-content-muted">دسترسی یکپارچه به تمام مدل‌های روز دنیا تنها با یک کلید API.</p>
        </div>
    </div>
</section>

<section id="simple-memory" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">حافظه گفتگو (Simple Memory)</h2>
    <p class="text-content-muted">
        با اتصال نود <strong>Simple Memory</strong> به درگاه <code>Memory</code> ایجنت، تاریخچه پیام‌های قبلی کاربر ذخیره می‌شود.
    </p>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">تعداد پیام‌های ذخیره‌شده:</strong> مشخص می‌کند چند نوبت از مکالمات اخیر در حافظه بماند (از ۱ تا ۵۰ نوبت).</li>
        <li><strong class="text-content-main">شناسه اختصاصی نشست (Session Key):</strong> در چت عمومی به صورت خودکار تفکیک می‌شود؛ اما اگر ربات تلگرام می‌سازید، متغیر شناسه چت را در این فیلد قرار دهید تا حافظه هر کاربر تلگرام به صورت کاملا مجزا نگه‌داری شود.</li>
    </ul>

    <x-docs.vars
        title="متغیرهای مهم در کار با ایجنت و حافظه"
        :items="[
            ['label' => 'پاسخ نهایی تولیدشده توسط ایجنت (برای ارسال در تلگرام یا جیمیل)', 'expr' => 'input.response'],
            ['label' => 'شناسه نشست پیشنهادی برای فیلد Session Key در ربات تلگرام', 'expr' => 'input.chat_id'],
        ]"
    />
</section>

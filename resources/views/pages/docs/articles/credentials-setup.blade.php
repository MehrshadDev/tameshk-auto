<section id="credential-security" class="scroll-mt-24 space-y-3">
    <h2 class="text-lg font-extrabold text-content-main">امنیت و رمزنگاری اعتبارنامه‌ها</h2>
    <p class="text-content-muted">
        تمام کلیدهای API، رمزهای عبور و توکن‌های OAuth که در بخش <strong class="text-content-main">«اعتبارنامه‌ها»</strong> ثبت می‌کنید، در پایگاه داده به صورت کاملا رمزنگاری‌شده (Encrypted) نگه‌داری می‌شوند و پس از ذخیره، کلید خام در رابط کاربری نمایش داده نمی‌شود.
    </p>
</section>

<section id="telegram-botfather" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">دریافت توکن ربات تلگرام (BotFather)</h2>
    <ol class="list-decimal space-y-2 pr-5 text-content-muted">
        <li>در تلگرام وارد ربات رسمی <code>@BotFather</code> شوید و دستور <code>/newbot</code> را ارسال کنید.</li>
        <li>یک نام نمایشی و سپس یک نام کاربری (که به <code>bot</code> ختم شود) برای ربات انتخاب کنید.</li>
        <li>توکن دریافتی (مثلا <code>123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ</code>) را کپی کرده و در تمشک یک اعتبارنامه از نوع <strong>ربات تلگرام</strong> بسازید.</li>
    </ol>
</section>

<section id="google-oauth-setup" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">راه‌اندازی اتصال جیمیل و گوگل شیت (Google OAuth2)</h2>
    <p class="text-content-muted">
        برای اتصال حساب <strong>Gmail</strong> یا <strong>Google (ویژه Google Sheets)</strong> مراحل زیر را در کنسول ابری گوگل انجام دهید:
    </p>
    <ol class="list-decimal space-y-2.5 pr-5 text-content-muted">
        <li>وارد <strong class="text-content-main">Google Cloud Console</strong> شوید و یک پروژه بسازید.</li>
        <li>
            از بخش <code>APIs & Services > Library</code> سرویس‌های مورد نیاز را فعال کنید:
            برای جیمیل سرویس <strong>Gmail API</strong> و برای گوگل شیت هر دو سرویس <strong>Google Sheets API</strong> و <strong>Google Drive API</strong> را فعال نمایید.
        </li>
        <li>در بخش <code>OAuth consent screen</code> ایمیل خود را در لیست <code>Test users</code> قرار دهید.</li>
        <li>از منوی <code>Credentials</code> روی <code>Create Credentials > OAuth client ID</code> کلیک کرده و نوع را روی <strong>Web application</strong> بگذارید.</li>
        <li>آدرس زیر را در بخش <strong>Authorized redirect URIs</strong> وارد کنید:</li>
    </ol>

    <x-docs.code
        label="Google OAuth Redirect URI"
        :lines="[
            url('/oauth/google/callback'),
        ]"
    />

    <p class="text-content-muted">
        سپس <code>Client ID</code> و <code>Client Secret</code> دریافتی را در تمشک وارد کرده و روی دکمه <strong class="text-primary">«ذخیره و اتصال به گوگل»</strong> کلیک کنید تا پنجره دسترسی گوگل باز شود.
    </p>
</section>

<section id="ai-and-http-keys" class="scroll-mt-24 space-y-3 pt-4">
    <h2 class="text-lg font-extrabold text-content-main">کلیدهای هوش مصنوعی و احراز هویت HTTP</h2>
    <ul class="list-disc space-y-2 pr-5 text-content-muted">
        <li><strong class="text-content-main">Google Gemini:</strong> کلید API را از <code>aistudio.google.com</code> دریافت کنید.</li>
        <li><strong class="text-content-main">OpenAI / DeepSeek / Groq / Anthropic / OpenRouter:</strong> کلید API اختصاصی را از پنل توسعه‌دهندگان هر سرویس دریافت و ثبت کنید. در اعتبارنامه OpenAI امکان تغییر <code>Base URL</code> نیز وجود دارد.</li>
        <li><strong class="text-content-main">توکن Bearer و Basic Auth:</strong> برای محافظت از وبهوک‌های ورودی در نود <code>Webhook Trigger</code> استفاده می‌شوند.</li>
    </ul>
</section>

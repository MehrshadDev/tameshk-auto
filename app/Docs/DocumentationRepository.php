<?php

namespace App\Docs;

final class DocumentationRepository
{
    public static function sections(): array
    {
        return [
            [
                'key'   => 'getting-started',
                'title' => 'شروع سریع و مفاهیم پایه',
                'icon'  => 'rocket',
                'items' => [
                    [
                        'slug'        => 'introduction',
                        'title'       => 'معرفی تمشک و نحوه کارکرد',
                        'description' => 'آشنایی با پلتفرم اتوماسیون گردش‌کار و هوش مصنوعی تمشک، معماری نودها و کاربردها.',
                        'readingTime' => '۴ دقیقه',
                        'view'        => 'pages.docs.articles.introduction',
                        'toc'         => [
                            ['id' => 'what-is-tameshk', 'label' => 'تمشک چیست؟'],
                            ['id' => 'core-concepts', 'label' => 'اجزای اصلی یک ورکفلو'],
                            ['id' => 'node-categories', 'label' => 'دسته‌بندی نودها در تمشک'],
                            ['id' => 'execution-modes', 'label' => 'حالت تست در برابر حالت انتشار'],
                        ],
                    ],
                    [
                        'slug'        => 'first-workflow',
                        'title'       => 'ساخت اولین ورکفلو (گام‌به‌گام)',
                        'description' => 'آموزش عملی ساخت، تست و انتشار یک چت‌بات هوشمند در کمتر از ۵ دقیقه.',
                        'readingTime' => '۶ دقیقه',
                        'view'        => 'pages.docs.articles.first-workflow',
                        'toc'         => [
                            ['id' => 'step-1-create', 'label' => '۱. ایجاد ورکفلو جدید'],
                            ['id' => 'step-2-trigger', 'label' => '۲. افزودن شروع‌کننده چت'],
                            ['id' => 'step-3-agent', 'label' => '۳. اتصال ایجنت و مدل هوش مصنوعی'],
                            ['id' => 'step-4-test', 'label' => '۴. تست زنده در محیط گفتگو'],
                            ['id' => 'step-5-publish', 'label' => '۵. انتشار و دریافت لینک عمومی'],
                        ],
                    ],
                    [
                        'slug'        => 'expressions-and-data',
                        'title'       => 'کار با داده‌ها و متغیرها (fx)',
                        'description' => 'نحوه انتقال داده بین نودها با Drag & Drop و نگارش عبارت‌های پویا با {{ ... }}.',
                        'readingTime' => '۵ دقیقه',
                        'view'        => 'pages.docs.articles.expressions-and-data',
                        'toc'         => [
                            ['id' => 'data-flow', 'label' => 'جریان داده بین نودها (JSON)'],
                            ['id' => 'expression-syntax', 'label' => 'استفاده از متغیرها در حالت (fx)'],
                            ['id' => 'referencing-nodes', 'label' => 'دسترسی به خروجی نودهای قبلی'],
                            ['id' => 'drag-and-drop', 'label' => 'کشیدن و رها کردن فیلدها'],
                        ],
                    ],
                ],
            ],
            [
                'key'   => 'triggers',
                'title' => 'شروع‌کننده‌ها (Triggers)',
                'icon'  => 'bolt',
                'items' => [
                    [
                        'slug'        => 'core-triggers',
                        'title'       => 'وبهوک، زمان‌بندی و چت عمومی',
                        'description' => 'راهنمای کامل شروع‌کننده‌های پایه: اجرای دستی، زمان‌بندی (Cron)، دریافت Webhook و صفحه چت.',
                        'readingTime' => '۷ دقیقه',
                        'view'        => 'pages.docs.articles.core-triggers',
                        'toc'         => [
                            ['id' => 'manual-trigger', 'label' => 'شروع‌کننده دستی (Manual)'],
                            ['id' => 'schedule-trigger', 'label' => 'زمان‌بندی خودکار (Schedule)'],
                            ['id' => 'webhook-trigger', 'label' => 'دریافت وبهوک (Webhook)'],
                            ['id' => 'chat-trigger', 'label' => 'شروع‌کننده چت (On Chat Message)'],
                        ],
                    ],
                    [
                        'slug'        => 'app-triggers',
                        'title'       => 'شروع‌کننده‌های تلگرام، جیمیل و شیت',
                        'description' => 'آغاز خودکار ورکفلو به محض دریافت پیام در ربات تلگرام، ایمیل جدید یا تغییر در Google Sheets.',
                        'readingTime' => '۶ دقیقه',
                        'view'        => 'pages.docs.articles.app-triggers',
                        'toc'         => [
                            ['id' => 'telegram-trigger', 'label' => 'شروع‌کننده ربات تلگرام'],
                            ['id' => 'gmail-trigger', 'label' => 'دریافت ایمیل جدید در جیمیل'],
                            ['id' => 'sheets-trigger', 'label' => 'تغییر یا افزودن ردیف در گوگل شیت'],
                        ],
                    ],
                ],
            ],
            [
                'key'   => 'ai-and-agents',
                'title' => 'هوش مصنوعی و ایجنت‌ها',
                'icon'  => 'brand:ai-agent',
                'items' => [
                    [
                        'slug'        => 'ai-agent-and-memory',
                        'title'       => 'ایجنت هوش مصنوعی و حافظه',
                        'description' => 'نحوه کارکرد AI Agent، تنظیم دستورالعمل سیستمی، اتصال مدل زبانی و مدیریت حافظه گفتگو.',
                        'readingTime' => '۷ دقیقه',
                        'view'        => 'pages.docs.articles.ai-agent-and-memory',
                        'toc'         => [
                            ['id' => 'how-agent-works', 'label' => 'ایجنت هوش مصنوعی چگونه کار می‌کند؟'],
                            ['id' => 'agent-prompts', 'label' => 'پرامپت کاربر و دستورالعمل سیستمی'],
                            ['id' => 'chat-models', 'label' => 'اتصال مدل‌های زبانی (Chat Models)'],
                            ['id' => 'simple-memory', 'label' => 'حافظه گفتگو (Simple Memory)'],
                        ],
                    ],
                    [
                        'slug'        => 'agent-tools',
                        'title'       => 'ابزارهای ایجنت (Agent Tools)',
                        'description' => 'توانمندسازی ایجنت با ابزارهای گوگل شیت، جیمیل، تلگرام، وب‌اسکرپر، ویکی‌پدیا، دیتاستور و ماشین‌حساب.',
                        'readingTime' => '۸ دقیقه',
                        'view'        => 'pages.docs.articles.agent-tools',
                        'toc'         => [
                            ['id' => 'what-are-tools', 'label' => 'ابزار (Tool) در ایجنت چیست؟'],
                            ['id' => 'service-tools', 'label' => 'ابزارهای گوگل شیت، جیمیل و تلگرام'],
                            ['id' => 'web-and-api-tools', 'label' => 'ابزارهای HTTP، وب‌اسکرپر و ویکی‌پدیا'],
                            ['id' => 'utility-tools', 'label' => 'ابزارهای دیتاستور، تاریخ و ماشین‌حساب'],
                        ],
                    ],
                    [
                        'slug'        => 'google-gemini-node',
                        'title'       => 'نود چندوجهی Google Gemini',
                        'description' => 'تولید متن، ساخت تصویر با Imagen 3، تحلیل صوت و رونوشت، بررسی PDF، ویدیو و مخزن جستجوی فایل.',
                        'readingTime' => '۶ دقیقه',
                        'view'        => 'pages.docs.articles.google-gemini-node',
                        'toc'         => [
                            ['id' => 'gemini-overview', 'label' => 'امکانات نود اختصاصی Google Gemini'],
                            ['id' => 'image-and-audio', 'label' => 'تولید و تحلیل تصویر و فایل صوتی'],
                            ['id' => 'document-and-video', 'label' => 'تحلیل اسناد PDF و ویدیو'],
                            ['id' => 'file-search-corpus', 'label' => 'مخزن جستجوی فایل (File Search)'],
                        ],
                    ],
                ],
            ],
            [
                'key'   => 'actions-and-credentials',
                'title' => 'سرویس‌ها و اعتبارنامه‌ها',
                'icon'  => 'world',
                'items' => [
                    [
                        'slug'        => 'credentials-setup',
                        'title'       => 'تنظیم اعتبارنامه‌ها و اتصال گوگل',
                        'description' => 'راهنمای دریافت توکن ربات تلگرام از BotFather، کلیدهای هوش مصنوعی و تنظیم OAuth2 گوگل.',
                        'readingTime' => '۶ دقیقه',
                        'view'        => 'pages.docs.articles.credentials-setup',
                        'toc'         => [
                            ['id' => 'credential-security', 'label' => 'امنیت و رمزنگاری اعتبارنامه‌ها'],
                            ['id' => 'telegram-botfather', 'label' => 'دریافت توکن ربات تلگرام'],
                            ['id' => 'google-oauth-setup', 'label' => 'راه‌اندازی اتصال جیمیل و گوگل شیت'],
                            ['id' => 'ai-and-http-keys', 'label' => 'کلیدهای هوش مصنوعی و HTTP'],
                        ],
                    ],
                    [
                        'slug'        => 'service-actions',
                        'title'       => 'عملیات تلگرام، جیمیل، شیت و HTTP',
                        'description' => 'آموزش کامل نودهای عملیاتی Telegram، Gmail، Google Sheets و ارسال درخواست با HTTP Request.',
                        'readingTime' => '۸ دقیقه',
                        'view'        => 'pages.docs.articles.service-actions',
                        'toc'         => [
                            ['id' => 'telegram-action', 'label' => 'نود عملیاتی تلگرام (Telegram)'],
                            ['id' => 'gmail-action', 'label' => 'نود عملیاتی جیمیل (Gmail)'],
                            ['id' => 'sheets-action', 'label' => 'نود گوگل شیت (Google Sheets)'],
                            ['id' => 'http-request-action', 'label' => 'نود درخواست وب (HTTP Request)'],
                        ],
                    ],
                ],
            ],
            [
                'key'   => 'logic-and-transform',
                'title' => 'منطق و تبدیل داده',
                'icon'  => 'arrows-split-2',
                'items' => [
                    [
                        'slug'        => 'logic-nodes',
                        'title'       => 'شرط‌گذاری و کنترل مسیر (Logic)',
                        'description' => 'هدایت هوشمند جریان ورکفلو با نودهای If Condition، Switch، Delay و Stop and Error.',
                        'readingTime' => '۶ دقیقه',
                        'view'        => 'pages.docs.articles.logic-nodes',
                        'toc'         => [
                            ['id' => 'condition-node', 'label' => 'شرط دو مسیره (If Condition)'],
                            ['id' => 'switch-node', 'label' => 'مسیریابی چندشاخه (Switch)'],
                            ['id' => 'delay-node', 'label' => 'وقفه زمانی (Delay)'],
                            ['id' => 'stop-error-node', 'label' => 'توقف و خطا (Stop and Error)'],
                        ],
                    ],
                    [
                        'slug'        => 'transform-nodes',
                        'title'       => 'تغییر داده، تاریخ و دیتاستور',
                        'description' => 'کار با نودهای Edit Fields، Item Lists، Text Operations، JSON Parser، Date & Time، Crypto و Data Store.',
                        'readingTime' => '۸ دقیقه',
                        'view'        => 'pages.docs.articles.transform-nodes',
                        'toc'         => [
                            ['id' => 'edit-fields-node', 'label' => 'ویرایش و ساخت فیلدها (Edit Fields)'],
                            ['id' => 'item-lists-node', 'label' => 'پردازش آرایه‌ها (Item Lists)'],
                            ['id' => 'text-and-json', 'label' => 'عملیات متنی و مبدل JSON'],
                            ['id' => 'datetime-and-crypto', 'label' => 'تاریخ شمسی/میلادی و رمزنگاری (OTP)'],
                            ['id' => 'datastore-node', 'label' => 'مخزن داده دائمی (Data Store)'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function allArticles(): array
    {
        $flat = [];

        foreach (self::sections() as $section) {
            foreach ($section['items'] as $item) {
                $flat[] = [
                    ...$item,
                    'section_key'   => $section['key'],
                    'section_title' => $section['title'],
                ];
            }
        }

        return $flat;
    }

    public static function find(?string $slug): ?array
    {
        $articles = self::allArticles();

        if ($slug === null || $slug === '') {
            return $articles[0] ?? null;
        }

        foreach ($articles as $article) {
            if ($article['slug'] === $slug) {
                return $article;
            }
        }

        return null;
    }

    public static function neighbors(string $slug): array
    {
        $articles = self::allArticles();
        $count = count($articles);

        for ($i = 0; $i < $count; $i++) {
            if ($articles[$i]['slug'] === $slug) {
                return [
                    'prev' => $i > 0 ? $articles[$i - 1] : null,
                    'next' => $i < $count - 1 ? $articles[$i + 1] : null,
                ];
            }
        }

        return ['prev' => null, 'next' => null];
    }
}

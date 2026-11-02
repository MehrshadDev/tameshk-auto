<?php

return [
    'enabled' => (bool) env('SAAS_MODE', false),

    'default_plan_slug' => 'free',

    'grace_period_days' => 3,

    'renewal_window_days' => 10,

    'zarinpal' => [
        'sandbox' => (bool) env('ZARINPAL_SANDBOX', true),
        'merchant_id' => (string) env('ZARINPAL_MERCHANT_ID', '00000000-0000-0000-0000-000000000000'),
        'currency' => (string) env('ZARINPAL_CURRENCY', 'IRT'),
    ],

    'plans' => [
        [
            'slug' => 'free',
            'name' => 'رایگان',
            'description' => 'مناسب برای شروع، یادگیری و ساخت اتوماسیون‌های شخصی',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'max_monthly_runs' => 500,
            'max_active_workflows' => 2,
            'features' => [
                '۵۰۰ اجرای ماهانه',
                '۲ ورک‌فلو فعال همزمان',
                'دسترسی به تمام نودها و ابزارهای هوش مصنوعی',
                'پشتیبانی از وبهوک و زمان‌بندی',
            ],
            'sort_order' => 1,
        ],
        [
            'slug' => 'pro',
            'name' => 'پرو',
            'description' => 'مناسب برای فریلنسرها، سازندگان ربات و کسب‌وکارهای در حال رشد',
            'monthly_price' => 390000,
            'yearly_price' => 3900000,
            'max_monthly_runs' => 10000,
            'max_active_workflows' => 10,
            'features' => [
                '۱۰,۰۰۰ اجرای ماهانه',
                '۱۰ ورک‌فلو فعال همزمان',
                'دسترسی به تمام نودها و ایجنت‌های هوش مصنوعی',
                'وبهوک اختصاصی و چت عمومی بدون محدودیت',
                'پشتیبانی اولویت‌دار',
            ],
            'sort_order' => 2,
        ],
        [
            'slug' => 'business',
            'name' => 'بیزینس',
            'description' => 'مناسب برای تیم‌ها، شرکت‌ها و پردازش‌های سنگین و پرتعداد',
            'monthly_price' => 990000,
            'yearly_price' => 9900000,
            'max_monthly_runs' => 50000,
            'max_active_workflows' => null,
            'features' => [
                '۵۰,۰۰۰ اجرای ماهانه',
                'ورک‌فلوهای فعال نامحدود',
                'ظرفیت اجرای موازی بالا برای وبهوک‌ها',
                'دسترسی کامل به تمامی ابزارها و مدل‌ها',
                'پشتیبانی ویژه سازمانی',
            ],
            'sort_order' => 3,
        ],
    ],
];

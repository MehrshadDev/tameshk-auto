export type FolderItem = {
    id: string;
    title: string;
    description: string;
    icon: string;
    iconClass: string;
};

export const FOLDERS_CONFIG: Record<string, FolderItem> = {
    trigger: {
        id: 'trigger',
        title: 'شروع‌کننده‌ها',
        description: 'شروع‌کننده‌های پایه‌ای سیستم، زمان‌بندی، چت و وبهوک',
        icon: 'player-play',
        iconClass: 'bg-emerald-500/10 text-emerald-500',
    },
    ai: {
        id: 'ai',
        title: 'هوش مصنوعی',
        description: 'ساخت ایجنت‌های خودمختار، مدل‌های زبانی، ابزارها و حافظه',
        icon: 'brand:ai-agent',
        iconClass: 'bg-purple-500/10 text-purple-500',
    },
    action: {
        id: 'action',
        title: 'عملیات در سرویس‌ها',
        description: 'انجام عملیات، اتوماسیون و وب‌سرویس‌های خارجی',
        icon: 'world',
        iconClass: 'bg-primary/10 text-primary',
    },
    transform: {
        id: 'transform',
        title: 'تغییر و تبدیل داده',
        description: 'ویرایش فیلدها، پردازش لیست‌ها، عملیات متنی و تبدیل JSON',
        icon: 'pencil',
        iconClass: 'bg-sky-500/10 text-sky-500',
    },
    logic: {
        id: 'logic',
        title: 'جریان و مسیر',
        description: 'شاخه‌بندی، ایجاد شروط، مسیریابی چندگانه و وقفه در اجرا',
        icon: 'hierarchy-2',
        iconClass: 'bg-amber-500/10 text-amber-500',
    },
    'ai/llm': {
        id: 'ai/llm',
        title: 'مدل‌های زبانی',
        description: 'مدل‌های چت و هوش مصنوعی متصل‌شونده به ایجنت',
        icon: 'sparkles',
        iconClass: 'bg-purple-500/10 text-purple-500',
    },
    'ai/memory': {
        id: 'ai/memory',
        title: 'حافظه گفتگو',
        description: 'نگه‌داری و مدیریت تاریخچه پیام‌های ایجنت',
        icon: 'history',
        iconClass: 'bg-sky-500/10 text-sky-500',
    },
    'ai/tools': {
        id: 'ai/tools',
        title: 'ابزارهای ایجنت',
        description: 'ابزارهای عملکردی ایجنت مثل گوگل شیت، جیمیل، وب‌سرویس و محاسبات',
        icon: 'tools',
        iconClass: 'bg-amber-500/10 text-amber-500',
    },
    'ai/gemini': {
        id: 'ai/gemini',
        title: 'Google Gemini',
        description: 'تولید متن و تصویر، تحلیل صوت، ویدیو، اسناد و مخازن جستجوی فایل',
        icon: 'brand:gemini',
        iconClass: 'bg-blue-500/10 text-blue-500',
    },
    'action/telegram': {
        id: 'action/telegram',
        title: 'Telegram',
        description: 'عملیات و شروع‌کننده‌های ربات تلگرام',
        icon: 'brand:telegram',
        iconClass: 'bg-sky-500/10 text-sky-500',
    },
    'action/gmail': {
        id: 'action/gmail',
        title: 'Gmail',
        description: 'ارسال، پاسخ، جستجو و مدیریت ایمیل‌های حساب گوگل',
        icon: 'brand:gmail',
        iconClass: 'bg-rose-500/10 text-rose-500',
    },
    'action/google-sheets': {
        id: 'action/google-sheets',
        title: 'Google Sheets',
        description: 'خواندن، افزودن، به‌روزرسانی و پاک‌کردن داده‌ها در اسپردشیت گوگل',
        icon: 'brand:google-sheets',
        iconClass: 'bg-emerald-500/10 text-emerald-500',
    },
};

export const getFolderMeta = (path: string, fallbackTitle: string): FolderItem => {
    return FOLDERS_CONFIG[path] ?? {
        id: path,
        title: fallbackTitle,
        description: 'مشاهده نودها',
        icon: 'box',
        iconClass: 'bg-surface-card text-content-muted',
    };
};

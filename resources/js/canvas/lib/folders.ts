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
        title: 'سایر شروع‌کننده‌ها (Other Triggers)',
        description: 'شروع‌کننده‌های پایه‌ای سیستم، چت و تست دستی',
        icon: 'player-play',
        iconClass: 'bg-emerald-500/10 text-emerald-500',
    },
    ai: {
        id: 'ai',
        title: 'هوش مصنوعی (AI)',
        description: 'ساخت ایجنت‌های خودمختار، مدل‌های زبانی، ابزارها و حافظه',
        icon: 'brand:ai-agent',
        iconClass: 'bg-purple-500/10 text-purple-500',
    },
    action: {
        id: 'action',
        title: 'عملیات در سرویس‌ها (Action in an app)',
        description: 'انجام عملیات، اتوماسیون و وب‌سرویس‌های خارجی',
        icon: 'world',
        iconClass: 'bg-primary/10 text-primary',
    },
    transform: {
        id: 'transform',
        title: 'تغییر و تبدیل داده (Data transformation)',
        description: 'ویرایش، فیلتر، ترکیب و تغییر ساختار داده‌های ورودی',
        icon: 'pencil',
        iconClass: 'bg-sky-500/10 text-sky-500',
    },
    logic: {
        id: 'logic',
        title: 'جریان و مسیر (Flow)',
        description: 'شاخه‌بندی، ایجاد شروط if، حلقه‌ها و وقفه در اجرای ورکفلو',
        icon: 'hierarchy-2',
        iconClass: 'bg-amber-500/10 text-amber-500',
    },
    'ai/llm': {
        id: 'ai/llm',
        title: 'مدل‌های زبانی (LLM)',
        description: 'مدل‌های چت و هوش مصنوعی متصل‌شونده به ایجنت',
        icon: 'sparkles',
        iconClass: 'bg-purple-500/10 text-purple-500',
    },
    'ai/memory': {
        id: 'ai/memory',
        title: 'حافظه (Memory)',
        description: 'نگه‌داری و مدیریت تاریخچه پیام‌های ایجنت',
        icon: 'history',
        iconClass: 'bg-sky-500/10 text-sky-500',
    },
    'ai/tools': {
        id: 'ai/tools',
        title: 'ابزارها (Tools)',
        description: 'ابزارهای عملکردی ایجنت مثل محاسبات، وب‌سرویس و تاریخ',
        icon: 'tools',
        iconClass: 'bg-amber-500/10 text-amber-500',
    },
    'action/telegram': {
        id: 'action/telegram',
        title: 'Telegram',
        description: 'عملیات و شروع‌کننده‌های ربات تلگرام',
        icon: 'brand:telegram',
        iconClass: 'bg-sky-500/10 text-sky-500',
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

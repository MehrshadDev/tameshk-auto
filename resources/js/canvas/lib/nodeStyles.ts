import type { NodeCategory, NodeDefinition } from '@canvas/types/graph';
import { Handle } from '@canvas/lib/handles';

type CategoryMeta = {
    label: string;
    style: string;
    color: string;
    order: number;
};

type Palette = {
    style: string;
    color: string;
};

export const CATEGORY_META: Record<NodeCategory, CategoryMeta> = {
    trigger: {
        label: 'شروع‌کننده‌ها',
        style: 'bg-emerald-500/10 text-emerald-500',
        color: '#10b981',
        order: 0,
    },
    ai: {
        label: 'هوش مصنوعی',
        style: 'bg-purple-500/10 text-purple-500',
        color: '#a855f7',
        order: 1,
    },
    action: {
        label: 'عملیات و سرویس‌ها',
        style: 'bg-primary/10 text-primary',
        color: '#e21c48',
        order: 2,
    },
    logic: {
        label: 'منطق و کنترل مسیر',
        style: 'bg-amber-500/10 text-amber-500',
        color: '#f59e0b',
        order: 3,
    },
    transform: {
        label: 'تبدیل و تغییر داده',
        style: 'bg-sky-500/10 text-sky-500',
        color: '#0ea5e9',
        order: 4,
    },
};

export const NODE_PALETTE: Record<string, Palette> = {
    emerald: { style: 'bg-emerald-500/10 text-emerald-500', color: '#10b981' },
    teal: { style: 'bg-teal-500/10 text-teal-500', color: '#14b8a6' },
    cyan: { style: 'bg-cyan-500/10 text-cyan-500', color: '#06b6d4' },
    sky: { style: 'bg-sky-500/10 text-sky-500', color: '#0ea5e9' },
    blue: { style: 'bg-blue-500/10 text-blue-500', color: '#3b82f6' },
    indigo: { style: 'bg-indigo-500/10 text-indigo-500', color: '#6366f1' },
    violet: { style: 'bg-violet-500/10 text-violet-500', color: '#8b5cf6' },
    fuchsia: { style: 'bg-fuchsia-500/10 text-fuchsia-500', color: '#d946ef' },
    pink: { style: 'bg-pink-500/10 text-pink-500', color: '#ec4899' },
    rose: { style: 'bg-rose-500/10 text-rose-500', color: '#f43f5e' },
    orange: { style: 'bg-orange-500/10 text-orange-500', color: '#f97316' },
    amber: { style: 'bg-amber-500/10 text-amber-500', color: '#f59e0b' },
    yellow: { style: 'bg-yellow-500/10 text-yellow-500', color: '#eab308' },
    lime: { style: 'bg-lime-500/10 text-lime-500', color: '#84cc16' },
};

export const categoryStyles: Record<NodeCategory, string> = Object.fromEntries(
    Object.entries(CATEGORY_META).map(([key, meta]) => [key, meta.style]),
) as Record<NodeCategory, string>;

export const categoryColors: Record<NodeCategory, string> = Object.fromEntries(
    Object.entries(CATEGORY_META).map(([key, meta]) => [key, meta.color]),
) as Record<NodeCategory, string>;

export const categoryLabels: Record<NodeCategory, string> = Object.fromEntries(
    Object.entries(CATEGORY_META).map(([key, meta]) => [key, meta.label]),
) as Record<NodeCategory, string>;

export const categoryOrder: NodeCategory[] = (Object.keys(CATEGORY_META) as NodeCategory[])
    .sort((a, b) => CATEGORY_META[a].order - CATEGORY_META[b].order);

export const handleLabels: Record<string, string> = {
    [Handle.Main]: 'اصلی',
    [Handle.True]: 'بله',
    [Handle.False]: 'خیر',
    [Handle.Case1]: 'حالت ۱',
    [Handle.Case2]: 'حالت ۲',
    [Handle.Case3]: 'حالت ۳',
    [Handle.Default]: 'پیش‌فرض',
    [Handle.ChatModel]: 'مدل چت',
    [Handle.Memory]: 'حافظه',
    [Handle.Tool]: 'ابزار',
};

const paletteOf = (definition?: NodeDefinition): Palette | undefined =>
    definition?.color ? NODE_PALETTE[definition.color] : undefined;

export const nodeColor = (definition?: NodeDefinition): string =>
    paletteOf(definition)?.color ?? (definition ? categoryColors[definition.category] : '#737373');

export const nodeAppearance = (definition?: NodeDefinition) => ({
    iconName: definition ? (definition.icon ?? 'box') : 'alert-triangle',
    iconStyle: definition
        ? (paletteOf(definition)?.style ?? CATEGORY_META[definition.category].style)
        : 'bg-line-border/60 text-content-muted',
});

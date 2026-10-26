import {useEffect, useState} from 'react';
import {Icon} from '@canvas/components/ui/Icon';
import type {LogEntry} from '@canvas/store/types';
import {useWorkflowStore} from '@canvas/store/useWorkflowStore';
import { Tooltip } from '@canvas/components/ui/Tooltip';

const SOURCE_LABELS: Record<LogEntry['source'], string> = {
    node: 'خطای نود',
    run: 'خطای اجرا',
    save: 'خطای ذخیره‌سازی',
    publish: 'خطای انتشار',
};

const PREVIEW_CHARS = 140;

const formatTime = (iso: string) =>
    new Date(iso).toLocaleString('fa-IR', {
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });

export function LogsFloatingWidget() {
    const [isOpen, setIsOpen] = useState(false);
    const [expandedIds, setExpandedIds] = useState<Record<string, boolean>>({});
    const [seenId, setSeenId] = useState<string | null | undefined>(undefined);

    const status = useWorkflowStore((state) => state.status);
    const logs = useWorkflowStore((state) => state.logs);
    const clearLogs = useWorkflowStore((state) => state.clearLogs);
    const addToast = useWorkflowStore((state) => state.addToast);

    const newestId = logs[0]?.id ?? null;

    useEffect(() => {
        if (status === 'ready' && seenId === undefined) {
            setSeenId(newestId);
        }
    }, [status, seenId, newestId]);

    useEffect(() => {
        if (isOpen) {
            setSeenId(newestId);
        }
    }, [isOpen, newestId]);

    const seenIndex = seenId ? logs.findIndex((log) => log.id === seenId) : -1;
    const unread = seenId === undefined ? 0 : seenIndex === -1 ? logs.length : seenIndex;

    const toggleExpand = (id: string) => {
        setExpandedIds((prev) => ({...prev, [id]: !prev[id]}));
    };

    const copyText = (text: string) => {
        navigator.clipboard.writeText(text);
        addToast('متن خطا در کلیپ‌بورد کپی شد.', 'success', 2000);
    };

    return (
        <div className="fixed bottom-5 left-20 z-20" dir="ltr">
            {isOpen && (
                <div
                    dir="rtl"
                    className="absolute bottom-14 left-0 flex h-130 w-105 max-w-[calc(100vw-6rem)] flex-col overflow-hidden rounded-3xl border border-line-border bg-surface-raised/95 shadow-2xl backdrop-blur-xl animate-in fade-in zoom-in-95 duration-150"
                >
                    <div
                        className="flex h-16 shrink-0 items-center justify-between border-b border-line-border/70 px-5">
                        <div className="flex min-w-0 items-center gap-3">
                            <div
                                className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <Icon name="file-text" className="size-5"/>
                            </div>
                            <div className="min-w-0 space-y-0.5">
                                <span className="block truncate text-xs font-bold leading-tight text-content-main">
                                    گزارش خطاها
                                </span>
                                <span className="block text-[11px] leading-tight text-content-muted">
                                    {logs.length > 0 ? `${logs.length} خطای اخیر` : 'هیچ خطایی ثبت نشده'}
                                </span>
                            </div>
                        </div>

                        <div className="flex items-center gap-1.5">
                            {logs.length > 0 && (
                                <button
                                    type="button"
                                    onClick={clearLogs}
                                    title="پاک‌کردن همه لاگ‌ها"
                                    className="flex size-8 items-center justify-center rounded-xl text-content-muted transition hover:bg-primary/10 hover:text-primary"
                                >
                                    <Icon name="trash" className="size-4"/>
                                </button>
                            )}

                            <button
                                type="button"
                                onClick={() => setIsOpen(false)}
                                title="بستن"
                                className="flex size-8 items-center justify-center rounded-xl text-content-muted transition hover:bg-line-border/60 hover:text-content-main"
                            >
                                <Icon name="x" className="size-4"/>
                            </button>
                        </div>
                    </div>

                    <div className="custom-scrollbar flex-1 select-text space-y-3 overflow-y-auto p-4">
                        {logs.length === 0 && (
                            <div className="space-y-2 py-20 text-center text-xs text-content-muted">
                                <Icon name="check" className="mx-auto size-7 opacity-30"/>
                                <p>خطایی برای نمایش وجود ندارد.</p>
                            </div>
                        )}

                        {logs.map((entry) => {
                            const isExpanded = Boolean(expandedIds[entry.id]);
                            const isLong = entry.message.length > PREVIEW_CHARS;
                            const text = isLong && !isExpanded ? `${entry.message.slice(0, PREVIEW_CHARS)}...` : entry.message;
                            const showNode = Boolean(entry.nodeName) && !entry.message.includes(entry.nodeName as string);

                            return (
                                <div
                                    key={entry.id}
                                    className="space-y-2.5 rounded-2xl border border-line-border bg-surface-card p-3.5 shadow-xs"
                                >
                                    <div
                                        className="flex items-center justify-between gap-2 border-b border-line-border/50 pb-2">
                                        <div className="flex min-w-0 items-center gap-2">
                                            <span className="size-2 shrink-0 rounded-full bg-primary"/>
                                            <span className="truncate text-xs font-bold text-content-main">
                                                {SOURCE_LABELS[entry.source]}
                                            </span>
                                            {showNode && (
                                                <span
                                                    className="truncate rounded-md bg-primary/10 px-1.5 py-0.5 text-[10px] font-semibold text-primary">
                                                    {entry.nodeName}
                                                </span>
                                            )}
                                        </div>

                                        <div className="flex shrink-0 items-center gap-1.5">
                                            <span
                                                className="text-[10px] text-content-muted">{formatTime(entry.at)}</span>
                                            <button
                                                type="button"
                                                onClick={() => copyText(entry.message)}
                                                title="کپی متن خطا"
                                                className="flex size-6 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary"
                                            >
                                                <Icon name="copy" className="size-3"/>
                                            </button>
                                        </div>
                                    </div>

                                    <div
                                        dir="auto"
                                        className="select-text whitespace-pre-wrap break-all rounded-xl border border-primary/20 bg-primary/5 p-2.5 text-xs leading-5 text-primary"
                                    >
                                        {text}
                                    </div>

                                    {isLong && (
                                        <div className="flex justify-end">
                                            <button
                                                type="button"
                                                onClick={() => toggleExpand(entry.id)}
                                                className="text-[11px] font-semibold text-primary transition hover:underline"
                                            >
                                                {isExpanded ? 'بستن جزئیات' : 'جزئیات بیشتر'}
                                            </button>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            <Tooltip label="مشاهده لاگ و خطاها" side="top">
                <button
                    type="button"
                    onClick={() => setIsOpen(!isOpen)}
                    className={`relative flex size-11 items-center justify-center rounded-2xl border shadow-2xl backdrop-blur-md transition-all active:scale-95 ${
                        isOpen
                            ? 'border-line-border bg-surface-raised text-content-main hover:bg-surface-card'
                            : unread > 0
                                ? 'border-primary/40 bg-primary text-white shadow-primary/20 hover:bg-primary/90'
                                : 'border-line-border bg-surface-raised text-content-muted hover:bg-surface-card hover:text-content-main'
                    }`}
                >
                    <Icon name={isOpen ? 'x' : 'file-text'} className="size-5"/>
                    {unread > 0 && !isOpen && (
                        <span
                            className="absolute -right-1 -top-1 flex size-4 items-center justify-center rounded-full bg-primary text-[9px] font-bold text-white ring-2 ring-surface-raised">
                        {unread}
                    </span>
                    )}
                </button>
            </Tooltip>
        </div>
    );
}

import { useState } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';

type TriggerEndpointListProps = {
    nodeType: string;
    config?: Record<string, unknown>;
};

export function TriggerEndpointList({ nodeType, config }: TriggerEndpointListProps) {
    const endpoints = useWorkflowStore((state) => state.endpoints[nodeType]);
    const isLive = useWorkflowStore((state) => Boolean(state.workflow?.published_at && state.workflow?.is_active));
    const addToast = useWorkflowStore((state) => state.addToast);

    const [activeMode, setActiveMode] = useState<'test' | 'production'>('test');

    if (!endpoints || endpoints.length === 0) {
        return null;
    }

    const hasBothModes =
        endpoints.some((e) => e.mode === 'test') && endpoints.some((e) => e.mode === 'production');

    const currentEndpoint = hasBothModes
        ? (endpoints.find((e) => e.mode === activeMode) ?? endpoints[0])
        : endpoints[0];

    const customPath = typeof config?.path === 'string' ? config.path.trim().replace(/^\/+|\/+$/g, '') : '';
    const baseEndpointUrl = currentEndpoint.url.replace(/\/[^/]+$/, '');
    const fullUrl = customPath !== '' ? `${baseEndpointUrl}/${customPath}` : currentEndpoint.url;
    const httpMethod = typeof config?.http_method === 'string' ? config.http_method.toUpperCase() : null;

    const needsLive = currentEndpoint.mode === 'production';
    const active = !needsLive || isLive;

    const copy = async (url: string) => {
        try {
            await navigator.clipboard.writeText(url);
            addToast('آدرس وبهوک کپی شد.', 'success', 2000);
        } catch {
            addToast('کپی آدرس انجام نشد.', 'error', 3000);
        }
    };

    return (
        <div className="space-y-3 rounded-2xl border border-line-border/80 bg-surface-card p-4">
            <div className="flex items-center justify-between gap-2">
                <span className="flex items-center gap-1.5 text-xs font-bold text-primary">
                    <Icon name="link" className="size-4" />
                    <span>{hasBothModes ? 'آدرس‌های وبهوک (Webhook URLs)' : currentEndpoint.label}</span>
                </span>

                {hasBothModes ? (
                    <div
                        dir="ltr"
                        className="flex items-center rounded-xl border border-line-border bg-surface-raised p-0.5 text-[11px] font-semibold"
                    >
                        <button
                            type="button"
                            onClick={() => setActiveMode('test')}
                            className={`rounded-lg px-2.5 py-1 transition ${
                                activeMode === 'test'
                                    ? 'bg-surface-card text-content-main shadow-xs'
                                    : 'text-content-muted hover:text-content-main'
                            }`}
                        >
                            Test URL
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveMode('production')}
                            className={`rounded-lg px-2.5 py-1 transition ${
                                activeMode === 'production'
                                    ? 'bg-surface-card text-content-main shadow-xs'
                                    : 'text-content-muted hover:text-content-main'
                            }`}
                        >
                            Production URL
                        </button>
                    </div>
                ) : (
                    <span
                        className={`rounded-md px-2 py-0.5 text-[10px] font-bold ${
                            needsLive ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500'
                        }`}
                    >
                        {needsLive ? 'محیط انتشار' : 'محیط تست'}
                    </span>
                )}
            </div>

            <div
                dir="ltr"
                onClick={() => void copy(fullUrl)}
                title="کلیک برای کپی آدرس"
                className="group flex cursor-pointer items-center justify-between gap-2 rounded-xl border border-line-border bg-field px-3 py-2.5 transition hover:border-primary/50"
            >
                <div className="flex min-w-0 flex-1 items-center gap-2.5">
                    {httpMethod && (
                        <span className="shrink-0 rounded-md bg-line-border px-2 py-0.5 font-mono text-[10px] font-bold text-content-main">
                            {httpMethod}
                        </span>
                    )}
                    <span className="truncate font-mono text-xs text-content-main select-all">
                        {fullUrl}
                    </span>
                </div>

                <div className="flex shrink-0 items-center gap-1">
                    <span className="flex size-7 items-center justify-center rounded-lg text-content-muted transition group-hover:bg-primary/10 group-hover:text-primary">
                        <Icon name="copy" className="size-3.5" />
                    </span>

                    {active && nodeType === 'trigger.chat' && (
                        <a
                            href={fullUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            onClick={(e) => e.stopPropagation()}
                            title="باز کردن در تب جدید"
                            className="flex size-7 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary"
                        >
                            <Icon name="external-link" className="size-3.5" />
                        </a>
                    )}
                </div>
            </div>

            <p className="flex items-center gap-1.5 text-[11px] leading-5 text-content-muted">
                {active ? (
                    <>
                        <span
                            className={`size-1.5 shrink-0 rounded-full ${
                                needsLive ? 'bg-emerald-500' : 'bg-amber-500'
                            }`}
                        />
                        <span>{currentEndpoint.hint}</span>
                    </>
                ) : (
                    <span>
                        این آدرس فقط پس از انتشار و فعال بودن ورکفلو کار می‌کند. برای تست در همین لحظه از تب Test URL استفاده کنید.
                    </span>
                )}
            </p>
        </div>
    );
}

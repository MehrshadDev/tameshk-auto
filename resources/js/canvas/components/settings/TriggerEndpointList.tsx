import { inputClass } from '@canvas/components/settings/fieldStyles';
import { Icon } from '@canvas/components/ui/Icon';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';

const modeStyle = {
    production: 'bg-emerald-500/10 text-emerald-500',
    test: 'bg-amber-500/10 text-amber-500',
};

const modeLabel = {
    production: 'محیط انتشار',
    test: 'محیط تست',
};

export function TriggerEndpointList({ nodeType }: { nodeType: string }) {
    const endpoints = useWorkflowStore((state) => state.endpoints[nodeType]);
    const isLive = useWorkflowStore((state) => Boolean(state.workflow?.published_at && state.workflow?.is_active));
    const addToast = useWorkflowStore((state) => state.addToast);

    if (!endpoints || endpoints.length === 0) {
        return null;
    }

    const copy = async (url: string) => {
        try {
            await navigator.clipboard.writeText(url);
            addToast('آدرس کپی شد.', 'success', 2000);
        } catch {
            addToast('کپی آدرس انجام نشد.', 'error', 3000);
        }
    };

    return (
        <div className="space-y-4 rounded-2xl border border-line-border/80 bg-surface-card p-4">
            {endpoints.map((endpoint) => {
                const needsLive = endpoint.mode === 'production';
                const active = !needsLive || isLive;

                return (
                    <div key={`${endpoint.key}-${endpoint.mode}`} className="space-y-2">
                        <div className="flex items-center justify-between gap-2">
                            <span className="flex items-center gap-1.5 text-xs font-bold text-content-main">
                                <Icon name="link" className="size-4 text-primary" />
                                {endpoint.label}
                            </span>

                            <span className={`rounded-md px-2 py-0.5 text-[10px] font-bold ${modeStyle[endpoint.mode]}`}>
                                {modeLabel[endpoint.mode]}
                            </span>
                        </div>

                        <div className="flex items-center gap-2">
                            <input
                                readOnly
                                dir="ltr"
                                value={endpoint.url}
                                onFocus={(event) => event.currentTarget.select()}
                                className={`${inputClass} min-w-0 flex-1 font-mono text-[11px]`}
                            />

                            <button
                                type="button"
                                onClick={() => void copy(endpoint.url)}
                                title="کپی آدرس"
                                className="flex size-9 shrink-0 items-center justify-center rounded-xl border border-line-border bg-surface-raised text-content-muted transition hover:border-primary hover:text-primary"
                            >
                                <Icon name="copy" className="size-4" />
                            </button>

                            {active && (
                                <a
                                    href={endpoint.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    title="باز کردن در تب جدید"
                                    className="flex size-9 shrink-0 items-center justify-center rounded-xl border border-line-border bg-surface-raised text-content-muted transition hover:border-primary hover:text-primary"
                                >
                                    <Icon name="external-link" className="size-4" />
                                </a>
                            )}
                        </div>

                        <p className="flex items-center gap-1.5 text-[11px] leading-5 text-content-muted">
                            {active ? (
                                <>
                                    <span className="size-1.5 shrink-0 rounded-full bg-emerald-500" />
                                    {needsLive ? 'ورکفلو فعال است و این آدرس درخواست می‌پذیرد.' : endpoint.hint}
                                </>
                            ) : (
                                'این آدرس فقط پس از انتشار و فعال بودن ورکفلو کار می‌کند. تا آن زمان خطای ۴۰۴ برمی‌گرداند.'
                            )}
                        </p>

                        {active && needsLive && endpoint.hint && (
                            <p className="text-[11px] leading-5 text-content-muted">{endpoint.hint}</p>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

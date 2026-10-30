import { Icon } from '@canvas/components/ui/Icon';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';
import type { NodeRunData } from '@canvas/types/graph';

type NodeOutputPanelProps = {
    width: number;
    visibleOnMobile: boolean;
    isManualTrigger: boolean;
    currentNodeRun?: NodeRunData;
};

export function NodeOutputPanel({
                                    width,
                                    visibleOnMobile,
                                    isManualTrigger,
                                    currentNodeRun,
                                }: NodeOutputPanelProps) {
    const addToast = useWorkflowStore((state) => state.addToast);

    const copyOutputJson = () => {
        if (!currentNodeRun?.output_data) return;
        navigator.clipboard.writeText(JSON.stringify(currentNodeRun.output_data, null, 2));
        addToast('داده‌های خروجی کپی شدند.', 'success', 2500);
    };

    const copyErrorText = () => {
        if (!currentNodeRun?.error) return;
        navigator.clipboard.writeText(currentNodeRun.error);
        addToast('متن خطا در کلیپ‌بورد کپی شد.', 'success', 2000);
    };

    return (
        <section
            style={{ width: window.innerWidth >= 768 ? width : '100%' }}
            className={`min-h-0 shrink-0 flex-col bg-surface-card/40 ${
                visibleOnMobile ? 'flex' : 'hidden md:flex'
            }`}
            dir="rtl"
        >
            <div className="flex items-center justify-between border-b border-line-border px-5 py-3">
                <span className="flex items-center gap-2 text-xs font-bold text-content-main">
                    <Icon name="sparkles" className="size-4 text-primary" />
                    نتیجه خروجی (Output)
                </span>

                {currentNodeRun && (
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={copyOutputJson}
                            title="کپی داده‌های خروجی"
                            className="flex items-center gap-1 rounded-md border border-line-border bg-surface-raised px-2 py-0.5 text-[10px] text-content-muted transition hover:text-content-main"
                        >
                            <Icon name="copy" className="size-3" />
                            <span>کپی خروجی</span>
                        </button>

                        {currentNodeRun.duration_ms !== null && (
                            <span
                                className="flex items-center gap-1 font-mono text-[11px] text-content-muted"
                                dir="ltr"
                            >
                                <Icon name="clock" className="size-3" />
                                {currentNodeRun.duration_ms} ms
                            </span>
                        )}

                        <span
                            className={`rounded-md px-2 py-0.5 text-[10px] font-bold ${
                                currentNodeRun.status === 'success'
                                    ? 'bg-emerald-500/10 text-emerald-500'
                                    : 'bg-primary/10 text-primary'
                            }`}
                        >
                            {currentNodeRun.status === 'success' ? 'موفق' : 'خطا'}
                        </span>
                    </div>
                )}
            </div>

            <div className="custom-scrollbar flex-1 space-y-4 overflow-y-auto p-4 select-text sm:p-5">
                {!currentNodeRun ? (
                    <div className="space-y-2 py-16 text-center text-xs text-content-muted">
                        <Icon name="clock" className="mx-auto size-7 opacity-30" />
                        <p>
                            {!isManualTrigger
                                ? 'ورکفلو را از طریق ورودی مربوطه اجرا کنید تا خروجی نمایش داده شود.'
                                : 'این نود هنوز در تست اجرا نشده است.'}
                        </p>
                    </div>
                ) : (
                    <div className="space-y-4 text-xs">
                        {currentNodeRun.error && (
                            <div className="max-h-56 space-y-2 overflow-y-auto rounded-2xl border border-primary/20 bg-primary/10 p-3.5 text-xs leading-5 text-primary">
                                <div className="flex items-center justify-between border-b border-primary/20 pb-1.5">
                                    <span className="text-[11px] font-bold">Error Output</span>
                                    <button
                                        type="button"
                                        onClick={copyErrorText}
                                        className="flex items-center gap-1 text-[10px] text-primary transition hover:underline"
                                    >
                                        <Icon name="copy" className="size-3" />
                                        <span>کپی متن خطا</span>
                                    </button>
                                </div>
                                <p
                                    className="select-text text-start font-mono text-[11px] break-all whitespace-pre-wrap"
                                    dir="auto"
                                >
                                    {currentNodeRun.error}
                                </p>
                            </div>
                        )}

                        <div className="space-y-1.5">
                            <pre
                                dir="ltr"
                                className="custom-scrollbar max-h-96 overflow-auto rounded-2xl border border-line-border bg-surface-card p-4 font-mono text-[11px] leading-5 text-content-main select-text"
                            >
                                {JSON.stringify(currentNodeRun.output_data, null, 2) ?? 'null'}
                            </pre>
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}

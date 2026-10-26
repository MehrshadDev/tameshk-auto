import { useEffect } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';
import type { Toast } from '@canvas/types/graph';

function ToastItem({ toast }: { toast: Toast }) {
    const removeToast = useWorkflowStore((state) => state.removeToast);

    useEffect(() => {
        const timer = window.setTimeout(() => {
            removeToast(toast.id);
        }, toast.duration ?? 20000);

        return () => window.clearTimeout(timer);
    }, [toast.id, toast.duration, removeToast]);

    const isError = toast.type === 'error';
    const isSuccess = toast.type === 'success';

    return (
        <div
            dir="rtl"
            className="pointer-events-auto flex items-center gap-3 rounded-2xl border border-line-border bg-surface-card/95 p-4 shadow-2xl backdrop-blur-md transition-all animate-in fade-in slide-in-from-bottom-3 duration-200"
        >
            <div
                className={`flex size-8 shrink-0 items-center justify-center rounded-xl ${
                    isError
                        ? 'bg-primary/10 text-primary'
                        : isSuccess
                            ? 'bg-emerald-500/10 text-emerald-500'
                            : 'bg-sky-500/10 text-sky-500'
                }`}
            >
                <Icon name={isError ? 'alert-triangle' : isSuccess ? 'check' : 'brackets'} className="size-4.5" />
            </div>

            <div className="min-w-0 flex-1">
                <p dir="auto" className="text-start text-xs font-semibold leading-5 text-content-main wrap-break-word">{toast.message}</p>
            </div>

            <button
                type="button"
                onClick={() => removeToast(toast.id)}
                aria-label="بستن"
                className="flex size-6 shrink-0 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border hover:text-content-main"
            >
                <Icon name="x" className="size-3.5" />
            </button>
        </div>
    );
}

export function ToastContainer() {
    const toasts = useWorkflowStore((state) => state.toasts);

    if (toasts.length === 0) return null;

    return (
        <div className="fixed bottom-5 inset-e-5 z-50 flex w-88 max-w-[calc(100vw-2.5rem)] flex-col gap-2.5 pointer-events-none">
            {toasts.map((toast) => (
                <ToastItem key={toast.id} toast={toast} />
            ))}
        </div>
    );
}

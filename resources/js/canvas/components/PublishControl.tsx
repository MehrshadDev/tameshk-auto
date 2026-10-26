import { useEffect, useRef, useState } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';

const usePublishability = (): { canPublish: boolean; reason: string | null } => {
    const nodes = useWorkflowStore((state) => state.nodes);
    const definitions = useWorkflowStore((state) => state.definitions);

    const trigger = nodes.find(
        (node) => definitions.find((item) => item.type === node.data.nodeType)?.category === 'trigger',
    );

    if (!trigger) {
        return { canPublish: false, reason: 'برای انتشار، ابتدا یک شروع‌کننده به ورکفلو اضافه کنید.' };
    }

    const definition = definitions.find((item) => item.type === trigger.data.nodeType);

    if (!definition?.publishable) {
        return {
            canPublish: false,
            reason: `شروع‌کننده «${definition?.name ?? ''}» فقط در محیط تست کار می‌کند و قابل انتشار نیست.`,
        };
    }

    return { canPublish: true, reason: null };
};

export function PublishControl() {
    const isPublished = useWorkflowStore((state) => Boolean(state.workflow?.published_at));
    const isActive = useWorkflowStore((state) => Boolean(state.workflow?.is_active));
    const isPublishing = useWorkflowStore((state) => state.isPublishing);
    const publish = useWorkflowStore((state) => state.publish);
    const unpublish = useWorkflowStore((state) => state.unpublish);
    const { canPublish, reason } = usePublishability();

    const [open, setOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onMouseDown = (event: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onMouseDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('mousedown', onMouseDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    if (!isPublished) {
        const disabled = !canPublish || isPublishing;

        return (
            <span title={reason ?? 'انتشار ورکفلو'} className={disabled ? 'cursor-not-allowed' : ''}>
                <button
                    type="button"
                    onClick={() => void publish()}
                    disabled={disabled}
                    className="flex h-9 items-center gap-2 rounded-xl bg-primary px-4 text-xs font-bold text-white shadow-sm transition hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50"
                >
                    <Icon
                        name={isPublishing ? 'loader' : 'cloud-upload'}
                        className={`size-4 ${isPublishing ? 'animate-spin' : ''}`}
                    />
                    <span>{isPublishing ? 'در حال انتشار...' : 'انتشار'}</span>
                </button>
            </span>
        );
    }

    return (
        <div ref={containerRef} className="relative">
            <button
                type="button"
                onClick={() => setOpen((prev) => !prev)}
                className={`flex h-9 items-center gap-2 rounded-xl border px-3.5 text-xs font-bold transition ${
                    isActive
                        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500/15'
                        : 'border-amber-500/30 bg-amber-500/10 text-amber-500 hover:bg-amber-500/15'
                }`}
            >
                {isPublishing ? (
                    <Icon name="loader" className="size-4 animate-spin" />
                ) : (
                    <span className={`size-1.5 rounded-full ${isActive ? 'bg-emerald-500' : 'bg-amber-500'}`} />
                )}
                <span>{isActive ? 'منتشر شده' : 'متوقف'}</span>
                <Icon name="chevron-down" className={`size-3.5 transition-transform ${open ? 'rotate-180' : ''}`} />
            </button>

            {open && (
                <div className="absolute left-0 top-full z-50 mt-2 w-60 rounded-xl border border-line-border bg-surface-raised p-1.5 shadow-xl animate-in fade-in zoom-in-95 duration-100">
                    <p className="px-2.5 py-2 text-[11px] leading-5 text-content-muted">
                        {isActive
                            ? 'ورکفلو فعال است و درخواست‌های ورودی را پردازش می‌کند.'
                            : 'ورکفلو منتشر شده ولی متوقف است. از داشبورد می‌توانید دوباره فعالش کنید.'}
                    </p>

                    <div className="my-1 border-t border-line-border/60" />

                    <button
                        type="button"
                        disabled={isPublishing}
                        onClick={() => {
                            setOpen(false);
                            void unpublish();
                        }}
                        className="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-start text-xs font-medium text-primary transition hover:bg-primary/10 disabled:opacity-50"
                    >
                        <Icon name="cloud-off" className="size-4" />
                        <span>لغو انتشار</span>
                    </button>
                </div>
            )}
        </div>
    );
}

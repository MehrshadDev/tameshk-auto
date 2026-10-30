import { useEffect, useRef, useState } from 'react';
import { DataTreeViewer } from '@canvas/components/settings/DataTreeViewer';
import { Icon } from '@canvas/components/ui/Icon';
import type { FlowNode } from '@canvas/lib/graph';
import { nodeAppearance } from '@canvas/lib/nodeStyles';
import type { NodeDefinition, NodeRunStatus } from '@canvas/types/graph';

export type UpstreamNodeItem = {
    sourceNode: FlowNode;
    sourceDef?: NodeDefinition;
    depth: number;
    outputData: unknown;
    status: NodeRunStatus | null;
};

type NodeInputPanelProps = {
    nodeId: string;
    width: number;
    visibleOnMobile: boolean;
    upstreamDataList: UpstreamNodeItem[];
};

export function NodeInputPanel({ nodeId, width, visibleOnMobile, upstreamDataList }: NodeInputPanelProps) {
    const [expandedNodes, setExpandedNodes] = useState<Record<string, boolean>>({});
    const scrollRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (scrollRef.current) {
            scrollRef.current.scrollTop = 0;
        }
    }, [nodeId]);

    const isNodeCardOpen = (id: string, index: number) => {
        if (id in expandedNodes) {
            return expandedNodes[id];
        }
        return index === 0;
    };

    const toggleNodeCard = (id: string, index: number) => {
        const currentOpen = isNodeCardOpen(id, index);
        setExpandedNodes((prev) => ({
            ...prev,
            [id]: !currentOpen,
        }));
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
                    <Icon name="brackets" className="size-4 text-primary" />
                    داده‌های ورودی (Input)
                </span>
                {upstreamDataList.length > 0 && (
                    <span className="rounded-md bg-line-border/60 px-2 py-0.5 text-[10px] font-semibold text-content-muted">
                        {upstreamDataList.length} نود قبلی
                    </span>
                )}
            </div>

            <div
                ref={scrollRef}
                className="custom-scrollbar flex-1 space-y-3 overflow-y-auto p-3.5 select-text sm:p-4"
            >
                {upstreamDataList.length === 0 ? (
                    <div className="space-y-2 py-16 text-center text-xs text-content-muted">
                        <Icon name="link-off" className="mx-auto size-7 opacity-30" />
                        <p>نود قبلی به این نود متصل نیست.</p>
                    </div>
                ) : (
                    upstreamDataList.map((item, idx) => {
                        const isOpen = isNodeCardOpen(item.sourceNode.id, idx);
                        const { iconName: srcIcon, iconStyle: srcStyle } = nodeAppearance(item.sourceDef);
                        const basePath =
                            idx === 0 ? 'input' : `$('${item.sourceNode.data.label}').item.json`;

                        return (
                            <div
                                key={item.sourceNode.id}
                                className="overflow-hidden rounded-2xl border border-line-border/80 bg-surface-raised shadow-xs transition-all"
                            >
                                <button
                                    type="button"
                                    dir="ltr"
                                    onClick={() => toggleNodeCard(item.sourceNode.id, idx)}
                                    className="flex w-full items-center justify-between gap-3 px-3.5 py-2.5 text-left transition hover:bg-surface-card/70"
                                >
                                    <div className="flex min-w-0 flex-1 items-center gap-3">
                                        <span
                                            className={`flex size-8 shrink-0 items-center justify-center rounded-xl ${srcStyle}`}
                                        >
                                            <Icon name={srcIcon} className="size-4.5" />
                                        </span>
                                        <div className="min-w-0 flex-1 text-left">
                                            <span className="block truncate text-xs font-bold text-content-main">
                                                {item.sourceNode.data.label}
                                            </span>
                                            <span
                                                dir="rtl"
                                                className="mt-0.5 block truncate text-left text-[10px] text-content-muted"
                                            >
                                                {idx === 0 ? 'نود قبلی مستقیم' : `${idx + 1} مرحله قبل`}
                                            </span>
                                        </div>
                                    </div>

                                    <div className="flex shrink-0 items-center gap-2">
                                        {item.status && (
                                            <span
                                                dir="rtl"
                                                className={`rounded-md px-2 py-0.5 text-[10px] font-bold ${
                                                    item.status === 'success'
                                                        ? 'bg-emerald-500/10 text-emerald-500'
                                                        : 'bg-primary/10 text-primary'
                                                }`}
                                            >
                                                {item.status === 'success' ? 'موفق' : 'خطا'}
                                            </span>
                                        )}
                                        <Icon
                                            name="chevron-down"
                                            className={`size-4 text-content-muted transition-transform duration-200 ${
                                                isOpen ? 'rotate-180 text-primary' : ''
                                            }`}
                                        />
                                    </div>
                                </button>

                                {isOpen && (
                                    <div className="border-t border-line-border/60 bg-surface-card/30 p-3.5">
                                        {item.outputData ? (
                                            <DataTreeViewer data={item.outputData} basePath={basePath} />
                                        ) : (
                                            <p className="py-4 text-center text-[11px] text-content-muted">
                                                داده‌ای از این نود ثبت نشده است. ابتدا آن را اجرا کنید.
                                            </p>
                                        )}
                                    </div>
                                )}
                            </div>
                        );
                    })
                )}
            </div>
        </section>
    );
}

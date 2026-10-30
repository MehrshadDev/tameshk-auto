import { useState, useRef, useEffect, useMemo } from 'react';
import { ConfigFieldInput } from '@canvas/components/settings/ConfigFieldInput';
import { DataTreeViewer } from '@canvas/components/settings/DataTreeViewer';
import { FieldShell } from '@canvas/components/settings/FieldShell';
import { TriggerSettings } from '@canvas/components/settings/TriggerSettings';
import { Icon } from '@canvas/components/ui/Icon';
import { nodeAppearance } from '@canvas/lib/nodeStyles';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';
import { isSpecialHandle } from '@canvas/lib/handles';
import { isTelegramTrigger, isTestableTrigger, isGmailTrigger, isSheetsTrigger } from '@canvas/lib/triggers';
import { useBackdropClose } from '@canvas/lib/useBackdropClose';
import type { ConfigField } from '@canvas/types/graph';
import type { FlowNode } from '@canvas/lib/graph';

export function NodeSettingsPanel() {
    const panel = useWorkflowStore((state) => state.panel);

    if (panel?.type !== 'node') {
        return null;
    }

    return <NodeStudio key={panel.id} nodeId={panel.id} />;
}

function NodeStudio({ nodeId }: { nodeId: string }) {
    const [mobileTab, setMobileTab] = useState<'input' | 'parameters' | 'output'>('parameters');
    const [optionsMenuOpen, setOptionsMenuOpen] = useState(false);
    const [expandedNodes, setExpandedNodes] = useState<Record<string, boolean>>({});
    const optionsContainerRef = useRef<HTMLDivElement>(null);
    const inputScrollRef = useRef<HTMLDivElement>(null);

    const [leftWidth, setLeftWidth] = useState(380);
    const [rightWidth, setRightWidth] = useState(380);
    const [activeHandle, setActiveHandle] = useState<'left' | 'right' | null>(null);

    const isDraggingLeft = useRef(false);
    const isDraggingRight = useRef(false);
    const isFullWorkflowRunning = useWorkflowStore((state) => state.isRunning);
    const dragStartX = useRef(0);
    const dragStartWidth = useRef(0);

    const node = useWorkflowStore((state) => state.nodes.find((item) => item.id === nodeId));
    const allNodes = useWorkflowStore((state) => state.nodes);
    const edges = useWorkflowStore((state) => state.edges);
    const definitions = useWorkflowStore((state) => state.definitions);
    const updateNode = useWorkflowStore((state) => state.updateNode);
    const closePanel = useWorkflowStore((state) => state.closePanel);
    const executeSingleNode = useWorkflowStore((state) => state.executeSingleNode);
    const cancelWorkflow = useWorkflowStore((state) => state.cancelWorkflow);
    const runningNodeId = useWorkflowStore((state) => state.runningNodeId);
    const ids = useWorkflowStore((state) => state.ids);
    const lastRun = useWorkflowStore((state) => state.lastRun);
    const addToast = useWorkflowStore((state) => state.addToast);
    const backdrop = useBackdropClose(closePanel);
    const isManualTrigger = allNodes.some((n) => isTestableTrigger(n.data.nodeType));

    useEffect(() => {
        if (inputScrollRef.current) {
            inputScrollRef.current.scrollTop = 0;
        }
    }, [nodeId]);

    useEffect(() => {
        if (!optionsMenuOpen) return;

        const handleOutsideClick = (e: MouseEvent) => {
            if (optionsContainerRef.current && !optionsContainerRef.current.contains(e.target as Node)) {
                setOptionsMenuOpen(false);
            }
        };

        const handleEscape = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setOptionsMenuOpen(false);
        };

        document.addEventListener('mousedown', handleOutsideClick);
        document.addEventListener('keydown', handleEscape);

        return () => {
            document.removeEventListener('mousedown', handleOutsideClick);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [optionsMenuOpen]);

    useEffect(() => {
        const handleMouseMove = (e: MouseEvent) => {
            if (isDraggingLeft.current) {
                const deltaX = e.clientX - dragStartX.current;
                const newWidth = Math.max(280, Math.min(650, dragStartWidth.current + deltaX));
                setLeftWidth(newWidth);
            } else if (isDraggingRight.current) {
                const deltaX = dragStartX.current - e.clientX;
                const newWidth = Math.max(260, Math.min(650, dragStartWidth.current + deltaX));
                setRightWidth(newWidth);
            }
        };

        const handleMouseUp = () => {
            isDraggingLeft.current = false;
            isDraggingRight.current = false;
            setActiveHandle(null);
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        };

        window.addEventListener('mousemove', handleMouseMove);
        window.addEventListener('mouseup', handleMouseUp);

        return () => {
            window.removeEventListener('mousemove', handleMouseMove);
            window.removeEventListener('mouseup', handleMouseUp);
        };
    }, []);

    const startLeftResize = (e: React.MouseEvent) => {
        e.preventDefault();
        isDraggingLeft.current = true;
        setActiveHandle('left');
        dragStartX.current = e.clientX;
        dragStartWidth.current = leftWidth;
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    };

    const startRightResize = (e: React.MouseEvent) => {
        e.preventDefault();
        isDraggingRight.current = true;
        setActiveHandle('right');
        dragStartX.current = e.clientX;
        dragStartWidth.current = rightWidth;
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    };

    const definition = definitions.find((item) => item.type === node?.data.nodeType);

    const isSupplyNode =
        definition !== undefined
        && definition.inputs.length === 0
        && definition.outputs.length > 0
        && definition.outputs.every(isSpecialHandle);

    const upstreamDataList = useMemo(() => {
        if (!node) return [];

        const hostIds = isSupplyNode
            ? edges.filter((edge) => edge.source === nodeId).map((edge) => edge.target)
            : [nodeId];

        const visited = new Set<string>(hostIds);
        const queue: { id: string; depth: number }[] = hostIds.map((id) => ({ id, depth: 0 }));
        const orderedNodes: { sourceNode: FlowNode; depth: number }[] = [];

        while (queue.length > 0) {
            const current = queue.shift()!;
            const parentEdges = edges.filter(
                (edge) => edge.target === current.id && !isSpecialHandle(edge.targetHandle ?? 'main'),
            );

            for (const edge of parentEdges) {
                if (!visited.has(edge.source)) {
                    visited.add(edge.source);
                    const foundNode = allNodes.find((n) => n.id === edge.source);
                    if (foundNode) {
                        orderedNodes.push({ sourceNode: foundNode, depth: current.depth + 1 });
                        queue.push({ id: foundNode.id, depth: current.depth + 1 });
                    }
                }
            }
        }

        const reversedRuns = [...(lastRun?.node_runs ?? [])].reverse();

        return orderedNodes.map(({ sourceNode, depth }) => {
            const sourceDbId = ids[sourceNode.id] ?? Number(sourceNode.id);
            const sourceRun = reversedRuns.find((r) => r.node_id === sourceDbId);
            const sourceDef = definitions.find((d) => d.type === sourceNode.data.nodeType);

            return {
                sourceNode,
                sourceDef,
                depth,
                outputData: sourceRun?.output_data ?? null,
                status: sourceRun?.status ?? null,
            };
        });
    }, [node, nodeId, isSupplyNode, edges, allNodes, lastRun, ids, definitions]);

    const combinedIncomingContext = useMemo(() => {
        const nodesMap: Record<string, unknown> = {};
        upstreamDataList.forEach((item) => {
            if (item.outputData !== null && item.outputData !== undefined) {
                nodesMap[item.sourceNode.data.label] = item.outputData;
                nodesMap[item.sourceNode.data.label.replace(/\s+/g, '_')] = item.outputData;
            }
        });

        return {
            input: upstreamDataList[0]?.outputData ?? undefined,
            nodes: nodesMap,
        };
    }, [upstreamDataList]);

    if (!node) {
        return null;
    }

    const { iconName, iconStyle } = nodeAppearance(definition);

    const isTrigger = definition?.category === 'trigger';
    const canTestNode = isManualTrigger && (!isTrigger || isTelegramTrigger(node.data.nodeType) || isGmailTrigger(node.data.nodeType));
    const activeTab = isTrigger && mobileTab === 'input' ? 'parameters' : mobileTab;

    const isExecuting = runningNodeId === nodeId;
    const dbId = ids[nodeId] ?? Number(nodeId);
    const currentNodeRun = [...(lastRun?.node_runs ?? [])].reverse().find((r) => r.node_id === dbId);

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

    const restoreName = () => {
        if (node.data.label.trim() === '') {
            updateNode(node.id, { label: definition?.name ?? 'نود' });
        }
    };

    const copyOutputJson = () => {
        if (!currentNodeRun?.output_data) {
            return;
        }
        navigator.clipboard.writeText(JSON.stringify(currentNodeRun.output_data, null, 2));
        addToast('داده‌های خروجی کپی شدند.', 'success', 2500);
    };

    const copyErrorText = () => {
        if (!currentNodeRun?.error) return;
        navigator.clipboard.writeText(currentNodeRun.error);
        addToast('متن خطا در کلیپ‌بورد کپی شد.', 'success', 2000);
    };

    const isFieldVisible = (field: ConfigField, config: Record<string, unknown>) => {
        if (!field.depends_on) return true;
        for (const [depKey, depValue] of Object.entries(field.depends_on)) {
            const actual = config[depKey];
            const allowed = Array.isArray(depValue) ? depValue : [depValue];
            if (!allowed.includes(String(actual ?? ''))) return false;
        }
        return true;
    };

    const allSchemaFields = Object.entries(definition?.configSchema ?? {});
    const visibleFields = allSchemaFields.filter(([, field]) => isFieldVisible(field, node.data.config));

    const primaryFields = visibleFields.filter(([, field]) => !field.advanced);
    const availableAdvancedFields = visibleFields.filter(([, field]) => field.advanced);

    const activeAdvancedFields = availableAdvancedFields.filter(([key]) => node.data.config[key] !== undefined && node.data.config[key] !== null);
    const inactiveAdvancedFields = availableAdvancedFields.filter(([key]) => node.data.config[key] === undefined || node.data.config[key] === null);

    const addAdvancedField = (key: string, field: ConfigField) => {
        updateNode(node.id, {
            config: {
                ...node.data.config,
                [key]: field.default ?? '',
            },
        });
        setOptionsMenuOpen(false);
    };

    const removeAdvancedField = (key: string) => {
        const nextConfig = { ...node.data.config };
        delete nextConfig[key];
        updateNode(node.id, { config: nextConfig });
    };

    return (
        <div
            {...backdrop}
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-md p-0 sm:p-2"
        >
            <div
                dir="rtl"
                onClick={(e) => e.stopPropagation()}
                className="studio-modal flex h-[96vh] w-full max-w-[1680px] flex-col overflow-hidden rounded-2xl border border-line-border bg-surface-raised shadow-2xl animate-in fade-in zoom-in-95 duration-150"
            >
                <header
                    dir="ltr"
                    className="flex h-16 shrink-0 items-center justify-between border-b border-line-border bg-surface-raised px-4 sm:px-6"
                >
                    <div className="flex items-center gap-3 min-w-0">
                        <span className={`flex size-10 shrink-0 items-center justify-center rounded-xl ${iconStyle}`}>
                            <Icon name={iconName} className="size-5" />
                        </span>

                        <div className="min-w-0">
                            <input
                                aria-label="نام نود"
                                value={node.data.label}
                                maxLength={100}
                                autoComplete="off"
                                dir="ltr"
                                onChange={(e) => updateNode(node.id, { label: e.target.value })}
                                onBlur={restoreName}
                                className="w-48 sm:w-64 rounded-md bg-transparent px-1 text-sm font-bold text-content-main focus:bg-surface-card focus:outline-none focus:ring-2 focus:ring-primary/40 truncate select-text"
                            />
                            <p className="px-1 text-[11px] text-content-muted text-left">{definition?.name ?? 'نوع ناشناخته'}</p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 sm:gap-3" dir="rtl">
                        <button
                            type="button"
                            onClick={closePanel}
                            aria-label="بستن"
                            className="flex size-9 items-center justify-center rounded-xl text-content-muted transition hover:bg-line-border hover:text-content-main"
                        >
                            <Icon name="x" className="size-5" />
                        </button>

                        {canTestNode && <span className="h-5 w-px bg-line-border/80" />}

                        {isExecuting ? (
                            <button
                                type="button"
                                onClick={() => void cancelWorkflow()}
                                className="flex items-center gap-2 rounded-xl bg-amber-500/10 border border-amber-500/20 px-3.5 py-2 text-xs font-bold text-amber-500 shadow-sm transition hover:bg-amber-500/20"
                            >
                                <Icon name="player-stop" className="size-4" />
                                <span className="hidden sm:inline">
                                    {isFullWorkflowRunning ? 'توقف ورکفلو' : 'توقف مرحله'}
                                </span>
                            </button>
                        ) : (
                            canTestNode && (
                                <button
                                    type="button"
                                    onClick={() => void executeSingleNode(nodeId)}
                                    className="flex items-center gap-2 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-500"
                                >
                                    <Icon name="player-play" className="size-4" />
                                    <span className="hidden sm:inline">
                                        {isTelegramTrigger(node.data.nodeType)
                                            ? 'دریافت پیام تستی'
                                            : isGmailTrigger(node.data.nodeType)
                                                ? 'بررسی و دریافت ایمیل تستی'
                                                : isSheetsTrigger(node.data.nodeType)
                                                    ? 'بررسی تغییرات شیت'
                                                    : 'تست این مرحله'}
                                    </span>
                                </button>
                            )
                        )}
                    </div>
                </header>

                <div className="flex md:hidden border-b border-line-border bg-surface-card/60 p-1 text-xs font-semibold">
                    {!isTrigger && (
                        <button
                            type="button"
                            onClick={() => setMobileTab('input')}
                            className={`flex-1 py-2 text-center rounded-xl transition ${
                                activeTab === 'input' ? 'bg-surface-raised text-primary shadow-xs' : 'text-content-muted'
                            }`}
                        >
                            ورودی
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={() => setMobileTab('parameters')}
                        className={`flex-1 py-2 text-center rounded-xl transition ${
                            activeTab === 'parameters' ? 'bg-surface-raised text-primary shadow-xs' : 'text-content-muted'
                        }`}
                    >
                        تنظیمات
                    </button>
                    <button
                        type="button"
                        onClick={() => setMobileTab('output')}
                        className={`flex-1 py-2 text-center rounded-xl transition ${
                            activeTab === 'output' ? 'bg-surface-raised text-primary shadow-xs' : 'text-content-muted'
                        }`}
                    >
                        خروجی
                    </button>
                </div>

                <div className="flex min-h-0 flex-1 w-full flex-col md:flex-row" dir="ltr">
                    {!isTrigger && (
                        <>
                            <section
                                style={{ width: window.innerWidth >= 768 ? leftWidth : '100%' }}
                                className={`flex-col min-h-0 bg-surface-card/40 shrink-0 ${
                                    activeTab === 'input' ? 'flex' : 'hidden md:flex'
                                }`}
                                dir="rtl"
                            >
                                <div className="flex items-center justify-between border-b border-line-border px-5 py-3">
                                    <span className="text-xs font-bold text-content-main flex items-center gap-2">
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
                                    ref={inputScrollRef}
                                    className="custom-scrollbar flex-1 overflow-y-auto p-3.5 sm:p-4 space-y-3 select-text"
                                >
                                    {upstreamDataList.length === 0 ? (
                                        <div className="py-16 text-center text-xs text-content-muted space-y-2">
                                            <Icon name="link-off" className="mx-auto size-7 opacity-30" />
                                            <p>نود قبلی به این نود متصل نیست.</p>
                                        </div>
                                    ) : (
                                        upstreamDataList.map((item, idx) => {
                                            const isOpen = isNodeCardOpen(item.sourceNode.id, idx);
                                            const { iconName: srcIcon, iconStyle: srcStyle } = nodeAppearance(item.sourceDef);
                                            const basePath = idx === 0
                                                ? 'input'
                                                : `$('${item.sourceNode.data.label}').item.json`;

                                            return (
                                                <div
                                                    key={item.sourceNode.id}
                                                    className="overflow-hidden rounded-2xl border border-line-border/80 bg-surface-raised shadow-xs transition-all"
                                                >
                                                    <button
                                                        type="button"
                                                        onClick={() => toggleNodeCard(item.sourceNode.id, idx)}
                                                        className="flex w-full items-center justify-between gap-2.5 px-3.5 py-2.5 text-start transition hover:bg-surface-card/70"
                                                    >
                                                        <div className="flex min-w-0 items-center gap-2.5">
                                                            <span className={`flex size-7 shrink-0 items-center justify-center rounded-lg ${srcStyle}`}>
                                                                <Icon name={srcIcon} className="size-4" />
                                                            </span>
                                                            <div className="min-w-0">
                                                                <span dir="auto" className="block truncate text-xs font-bold text-content-main">
                                                                    {item.sourceNode.data.label}
                                                                </span>
                                                                <span className="block text-[10px] text-content-muted">
                                                                    {idx === 0 ? 'نود قبلی مستقیم' : `${idx + 1} مرحله قبل`}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div className="flex shrink-0 items-center gap-2">
                                                            {item.status && (
                                                                <span
                                                                    className={`rounded-md px-1.5 py-0.5 text-[9px] font-bold ${
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
                                                        <div className="border-t border-line-border/60 p-3.5 bg-surface-card/30">
                                                            {item.outputData ? (
                                                                <DataTreeViewer
                                                                    data={item.outputData}
                                                                    basePath={basePath}
                                                                />
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

                            <div
                                onMouseDown={startLeftResize}
                                className="group/handle relative z-10 hidden w-px shrink-0 cursor-col-resize select-none bg-line-border md:block"
                            >
                                <span className="absolute inset-y-0 -inset-x-1.5" />
                                <span
                                    className={`pointer-events-none absolute inset-y-0 left-1/2 -translate-x-1/2 rounded-full transition-all duration-150 ${
                                        activeHandle === 'left'
                                            ? 'w-0.75 bg-primary'
                                            : 'w-px bg-transparent group-hover/handle:w-0.75 group-hover/handle:bg-primary/60'
                                    }`}
                                />
                            </div>
                        </>
                    )}

                    <section
                        className={`flex-1 flex-col min-h-0 min-w-0 bg-surface-raised ${
                            activeTab === 'parameters' ? 'flex' : 'hidden md:flex'
                        }`}
                        dir="rtl"
                    >
                        <div className="flex items-center justify-between border-b border-line-border px-6 py-3">
                            <span className="text-xs font-bold text-content-main flex items-center gap-2">
                                <Icon name={isTrigger ? 'player-play' : 'settings'} className="size-4 text-primary" />
                                {isTrigger ? 'تنظیمات شروع‌کننده (Trigger)' : 'تنظیمات نود (Parameters)'}
                            </span>
                        </div>

                        <div className="custom-scrollbar flex-1 space-y-5 overflow-y-auto p-4 sm:p-6 select-text">
                            {isTrigger ? (
                                <TriggerSettings node={node} definition={definition} />
                            ) : (
                                <>
                                    {definition?.description && (
                                        <p className="text-xs leading-6 text-content-muted">{definition.description}</p>
                                    )}

                                    {!definition && (
                                        <p className="text-xs leading-6 text-primary">نوع این نود دیگر وجود ندارد.</p>
                                    )}

                                    {definition && primaryFields.length === 0 && (
                                        <p className="py-12 text-center text-xs text-content-muted">این نود تنظیماتی ندارد.</p>
                                    )}

                                    {primaryFields.map(([key, field]) => {
                                        const id = `studio-field-${node.id}-${key}`;

                                        return (
                                            <FieldShell
                                                key={key}
                                                id={id}
                                                label={field.label ?? key}
                                                required={field.required}
                                                help={field.help}
                                                hideLabel={field.type === 'boolean'}
                                            >
                                                <ConfigFieldInput
                                                    id={id}
                                                    field={field}
                                                    value={node.data.config[key] ?? field.default ?? null}
                                                    incomingData={combinedIncomingContext}
                                                    fullConfig={node.data.config}
                                                    onChange={(value) => {
                                                        const nextConfig: Record<string, unknown> = { ...node.data.config, [key]: value };
                                                        if (key === 'credential_id') {
                                                            delete nextConfig.spreadsheet_id;
                                                            delete nextConfig.sheet;
                                                        } else if (key === 'spreadsheet_id') {
                                                            delete nextConfig.sheet;
                                                        }
                                                        updateNode(node.id, { config: nextConfig });
                                                    }}
                                                />
                                            </FieldShell>
                                        );
                                    })}

                                    {activeAdvancedFields.map(([key, field]) => {
                                        const id = `studio-field-${node.id}-${key}`;

                                        return (
                                            <div key={key} className="relative rounded-2xl border border-line-border/70 bg-surface-card/60 p-4 pt-3">
                                                <div className="flex items-center justify-between mb-2 pb-1.5 border-b border-line-border/50">
                                                    <span className="text-xs font-bold text-content-main">
                                                        {field.label ?? key}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        onClick={() => removeAdvancedField(key)}
                                                        title="حذف این گزینه"
                                                        className="flex size-6 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary"
                                                    >
                                                        <Icon name="x" className="size-3.5" />
                                                    </button>
                                                </div>

                                                <ConfigFieldInput
                                                    id={id}
                                                    field={field}
                                                    value={node.data.config[key] ?? field.default ?? null}
                                                    incomingData={combinedIncomingContext}
                                                    fullConfig={node.data.config}
                                                    onChange={(value) =>
                                                        updateNode(node.id, { config: { ...node.data.config, [key]: value } })
                                                    }
                                                />
                                            </div>
                                        );
                                    })}

                                    {inactiveAdvancedFields.length > 0 && (
                                        <div className="pt-2">
                                            <div ref={optionsContainerRef} className="relative">
                                                <button
                                                    type="button"
                                                    onClick={() => setOptionsMenuOpen((prev) => !prev)}
                                                    className="flex w-full items-center justify-center gap-2 rounded-2xl border border-line-border bg-surface-card py-2.5 text-xs font-semibold text-content-main hover:bg-line-border/40 transition shadow-xs"
                                                >
                                                    <Icon name="plus" className="size-3.5" />
                                                    <span>افزودن گزینه (گزینه‌های بیشتر)</span>
                                                </button>

                                                {optionsMenuOpen && (
                                                    <div className="absolute right-0 bottom-full mb-1.5 w-full rounded-2xl border border-line-border bg-surface-raised p-1.5 shadow-xl backdrop-blur-md z-30 space-y-0.5">
                                                        {inactiveAdvancedFields.map(([key, field]) => (
                                                            <button
                                                                key={key}
                                                                type="button"
                                                                onClick={() => addAdvancedField(key, field)}
                                                                className="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-start text-xs font-medium text-content-main hover:bg-primary/10 hover:text-primary transition"
                                                            >
                                                                <Icon name="plus" className="size-3.5 text-primary" />
                                                                <span>{field.label ?? key}</span>
                                                            </button>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                    </section>

                    <div
                        onMouseDown={startRightResize}
                        className="group/handle relative z-10 hidden w-px shrink-0 cursor-col-resize select-none bg-line-border md:block"
                    >
                        <span className="absolute inset-y-0 -inset-x-1.5" />
                        <span
                            className={`pointer-events-none absolute inset-y-0 left-1/2 -translate-x-1/2 rounded-full transition-all duration-150 ${
                                activeHandle === 'right'
                                    ? 'w-0.75 bg-primary'
                                    : 'w-px bg-transparent group-hover/handle:w-0.75 group-hover/handle:bg-primary/60'
                            }`}
                        />
                    </div>

                    <section
                        style={{ width: window.innerWidth >= 768 ? rightWidth : '100%' }}
                        className={`flex-col min-h-0 bg-surface-card/40 shrink-0 ${
                            activeTab === 'output' ? 'flex' : 'hidden md:flex'
                        }`}
                        dir="rtl"
                    >
                        <div className="flex items-center justify-between border-b border-line-border px-5 py-3">
                            <span className="text-xs font-bold text-content-main flex items-center gap-2">
                                <Icon name="sparkles" className="size-4 text-primary" />
                                نتیجه خروجی (Output)
                            </span>

                            {currentNodeRun && (
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={copyOutputJson}
                                        title="کپی داده‌های خروجی"
                                        className="flex items-center gap-1 rounded-md border border-line-border bg-surface-raised px-2 py-0.5 text-[10px] text-content-muted hover:text-content-main transition"
                                    >
                                        <Icon name="copy" className="size-3" />
                                        <span>کپی خروجی</span>
                                    </button>

                                    {currentNodeRun.duration_ms !== null && (
                                        <span className="flex items-center gap-1 font-mono text-[11px] text-content-muted" dir="ltr">
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

                        <div className="custom-scrollbar flex-1 overflow-y-auto p-4 sm:p-5 space-y-4 select-text">
                            {!currentNodeRun ? (
                                <div className="py-16 text-center text-xs text-content-muted space-y-2">
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
                                        <div className="rounded-2xl border border-primary/20 bg-primary/10 p-3.5 text-primary text-xs leading-5 max-h-56 overflow-y-auto space-y-2">
                                            <div className="flex items-center justify-between border-b border-primary/20 pb-1.5">
                                                <span className="font-bold text-[11px]">Error Output</span>
                                                <button
                                                    type="button"
                                                    onClick={copyErrorText}
                                                    className="flex items-center gap-1 text-[10px] text-primary hover:underline transition"
                                                >
                                                    <Icon name="copy" className="size-3" />
                                                    <span>کپی متن خطا</span>
                                                </button>
                                            </div>
                                            <p className="text-start font-mono text-[11px] break-all select-text whitespace-pre-wrap" dir="auto">
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
                </div>
            </div>
        </div>
    );
}

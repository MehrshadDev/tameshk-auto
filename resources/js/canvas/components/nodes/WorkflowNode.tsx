import {Handle, NodeToolbar, Position, useConnection, useUpdateNodeInternals, type NodeProps} from '@xyflow/react';
import {useLayoutEffect} from 'react';
import {Icon} from '@canvas/components/ui/Icon';
import type {FlowNode} from '@canvas/lib/graph';
import {AGENT_SLOTS} from '@canvas/lib/agentLayout';
import {Handle as HandleId, SPECIAL_HANDLES} from '@canvas/lib/handles';
import {handleLabels, nodeAppearance} from '@canvas/lib/nodeStyles';
import {useWorkflowStore} from '@canvas/store/useWorkflowStore';
import {missingRequiredLabels} from '@canvas/lib/fields';
import {isTestableTrigger} from '@canvas/lib/triggers';
import {NodeRunStatus} from "@canvas/types/graph";
import {Tooltip} from "@canvas/components/ui/Tooltip";

const HANDLE_RESET = 'relative! left-auto! right-auto! top-auto! bottom-auto! transform-none!';

const DOT_HANDLE = `${HANDLE_RESET} pointer-events-auto size-3.5! rounded-full! border-2! border-surface-card! bg-content-muted/70! shadow-sm ring-1 ring-line-border transition-all duration-150 cursor-crosshair hover:scale-125 hover:bg-primary! hover:ring-primary/40`;

const DIAMOND_HANDLE = `${HANDLE_RESET} pointer-events-auto size-6! rounded-none! border-0! bg-transparent! cursor-pointer flex items-center justify-center`;

const BADGE_BASE =
    'absolute -top-1.5 -right-1.5 z-20 flex h-5 min-w-5 items-center justify-center gap-0.5 rounded-full px-1 text-white shadow-md ring-2 ring-surface-card';

type NodeBadgeProps = {
    disabled: boolean;
    executing: boolean;
    missing: string[];
    status?: NodeRunStatus;
    successCount: number;
};

const handleTop = (index: number, total: number) => {
    if (total <= 1) {
        return '50%';
    }
    return `${((index + 1) / (total + 1)) * 100}%`;
};

function SidePlus({onClick}: { onClick: () => void }) {
    return (
        <div
            onClick={(e) => {
                e.stopPropagation();
                onClick();
            }}
            className="tameshk-plus-wrapper group/plus absolute inset-s-full top-1/2 flex -translate-y-1/2 cursor-pointer items-center pointer-events-auto"
        >
            <span
                className="pointer-events-none h-px w-5 bg-linear-to-r from-content-muted/60 to-content-muted/20 transition-all duration-150 group-hover/plus:w-7 group-hover/plus:from-primary group-hover/plus:to-primary/40"/>
            <span
                className="flex size-6 items-center justify-center rounded-full border border-line-border bg-surface-raised text-content-muted shadow-md transition-all duration-150 group-hover/plus:scale-110 group-hover/plus:border-primary group-hover/plus:bg-primary group-hover/plus:text-white group-hover/plus:shadow-lg group-hover/plus:shadow-primary/30">
                <Icon name="plus" className="pointer-events-none size-3.5"/>
            </span>
        </div>
    );
}

function NodeBadge({disabled, executing, missing, status, successCount}: NodeBadgeProps) {
    if (disabled) {
        return null;
    }

    if (executing) {
        return (
            <span className={`${BADGE_BASE} bg-amber-500`}>
                <Icon name="loader" className="size-3 animate-spin"/>
            </span>
        );
    }

    if (status === 'failed') {
        return (
            <span className={`${BADGE_BASE} bg-primary`}>
                <Icon name="x" className="size-3"/>
            </span>
        );
    }

    if (status === 'success') {
        return (
            <span className={`${BADGE_BASE} bg-emerald-500`}>
                <Icon name="check" className="size-3"/>
                {successCount > 1 && (
                    <span className="font-mono text-[9px] font-bold leading-none" dir="ltr">
                        {successCount}
                    </span>
                )}
            </span>
        );
    }

    if (missing.length > 0) {
        return (
            <span title={`فیلدهای ناقص: ${missing.join('، ')}`} className={`${BADGE_BASE} bg-amber-500`}>
                <Icon name="alert-triangle" className="size-3"/>
            </span>
        );
    }

    return null;
}

export function WorkflowNode({id, data, selected}: NodeProps<FlowNode>) {
    const updateNodeInternals = useUpdateNodeInternals();
    const {inProgress} = useConnection();

    const nodes = useWorkflowStore((state) => state.nodes);
    const definitions = useWorkflowStore((state) => state.definitions);
    const ids = useWorkflowStore((state) => state.ids);
    const lastRun = useWorkflowStore((state) => state.lastRun);
    const startConnection = useWorkflowStore((state) => state.startConnection);
    const startTargetConnection = useWorkflowStore((state) => state.startTargetConnection);
    const connectingHandle = useWorkflowStore((state) => state.connectingHandle);
    const edges = useWorkflowStore((state) => state.edges);
    const deleteNode = useWorkflowStore((state) => state.deleteNode);
    const toggleNodeDisabled = useWorkflowStore((state) => state.toggleNodeDisabled);
    const executeSingleNode = useWorkflowStore((state) => state.executeSingleNode);
    const duplicateSelected = useWorkflowStore((state) => state.duplicateSelected);
    const runningNodeId = useWorkflowStore((state) => state.runningNodeId);

    const definition = definitions.find((item) => item.type === data.nodeType);
    const dbId = ids[id] ?? Number(id);

    const isManualTrigger = nodes.some((n) => isTestableTrigger(n.data.nodeType));

    const nodeRuns = lastRun?.node_runs?.filter((r) => r.node_id === dbId) ?? [];
    const lastRunItem = nodeRuns.length > 0 ? nodeRuns[nodeRuns.length - 1] : undefined;
    const successCount = nodeRuns.filter((r) => r.status === 'success').length;

    const isAiAgent = data.nodeType === 'ai.agent';
    const isTrigger = definition?.category === 'trigger';
    const isCondition = data.nodeType === 'logic.condition';
    const isDisabled = Boolean(data.config?.disabled) && !isTrigger;
    const isNodeExecuting = runningNodeId === id;

    // فقط زمانی که حداقل یک بار اجرا صورت گرفته باشد فیلدهای ناقص بررسی می‌شوند
    const hasEverRun = lastRun !== null;
    const missingFields = hasEverRun && definition && !isDisabled
        ? missingRequiredLabels(definition.configSchema, data.config ?? {})
        : [];

    const inputs = definition?.inputs ?? [];
    const outputs = definition?.outputs ?? [];
    const labeled = outputs.length > 1;
    const {iconName, iconStyle} = nodeAppearance(definition);

    const isSupplyNode =
        outputs.length === 1
        && (SPECIAL_HANDLES as readonly string[]).includes(outputs[0])
        && inputs.length === 0;

    const isSupplyConnected = isSupplyNode && edges.some((edge) => edge.source === id);

    const isSlotConnected = (handle: string) =>
        edges.some((edge) => {
            if (edge.target !== id || edge.targetHandle !== handle) return false;
            const src = nodes.find((n) => n.id === edge.source);
            return !src?.data.config?.disabled;
        });

    const isMissingModel = hasEverRun && isAiAgent && !isSlotConnected(HandleId.ChatModel);
    const isForeignConnecting = Boolean(connectingHandle && connectingHandle.nodeId !== id);

    const conditionOutcome = isCondition && lastRunItem?.status === 'success'
        ? (lastRunItem.output_data as { handle?: string } | null)?.handle ?? null
        : null;

    useLayoutEffect(() => {
        updateNodeInternals(id);
    }, [id, edges.length, updateNodeInternals]);

    return (
        <div className={`group/node pointer-events-none flex flex-col items-center ${isDisabled ? 'opacity-50' : ''}`}>
            <NodeToolbar
                position={Position.Top}
                offset={8}
                className="flex items-center gap-1 rounded-xl border border-line-border bg-surface-raised p-1 shadow-xl backdrop-blur-md"
            >
                {isManualTrigger && (
                    <Tooltip label="تست این مرحله">
                        <button
                            type="button"
                            onClick={(e) => {
                                e.stopPropagation();
                                void executeSingleNode(id);
                            }}
                            disabled={isNodeExecuting}
                            className="flex size-6 items-center justify-center rounded-lg text-content-muted transition hover:bg-emerald-500/10 hover:text-emerald-500 disabled:opacity-50"
                        >
                            <Icon name="player-play" className="size-3.5"/>
                        </button>
                    </Tooltip>
                )}

                {!isTrigger && (
                    <Tooltip label={isDisabled ? 'فعال کردن نود' : 'غیرفعال کردن نود'}>
                        <button
                            type="button"
                            onClick={(e) => {
                                e.stopPropagation();
                                toggleNodeDisabled(id);
                            }}
                            className={`flex size-6 items-center justify-center rounded-lg transition ${
                                isDisabled
                                    ? 'bg-amber-500/10 text-amber-500'
                                    : 'text-content-muted hover:bg-line-border/60 hover:text-content-main'
                            }`}
                        >
                            <Icon name="power" className="size-3.5"/>
                        </button>
                    </Tooltip>
                )}

                {!isTrigger && (
                    <Tooltip label="تکثیر نود (Duplicate)">
                        <button
                            type="button"
                            onClick={(e) => {
                                e.stopPropagation();
                                duplicateSelected();
                            }}
                            className="flex size-6 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main"
                        >
                            <Icon name="copy" className="size-3.5"/>
                        </button>
                    </Tooltip>
                )}

                <Tooltip label="حذف نود">
                    <button
                        type="button"
                        onClick={(e) => {
                            e.stopPropagation();
                            deleteNode(id);
                        }}
                        className="flex size-6 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary"
                    >
                        <Icon name="trash" className="size-3.5"/>
                    </button>
                </Tooltip>
            </NodeToolbar>

            {isAiAgent ? (
                <div
                    className={`pointer-events-auto relative flex h-18 w-72 items-center justify-between rounded-2xl border bg-surface-card px-4 shadow-sm transition-all duration-200 ${
                        selected
                            ? 'border-purple-500 shadow-lg ring-4 ring-purple-500/20'
                            : isNodeExecuting
                                ? 'border-amber-500 shadow-lg ring-4 ring-amber-500/20'
                                : 'border-line-border hover:border-content-muted/40'
                    } ${isDisabled ? 'border-dashed' : ''}`}
                >
                    <NodeBadge
                        disabled={isDisabled}
                        executing={isNodeExecuting}
                        missing={missingFields}
                        status={lastRunItem?.status}
                        successCount={successCount}
                    />

                    <div
                        className="pointer-events-none absolute -left-1.5 top-1/2 z-10 flex -translate-y-1/2 items-center">
                        <Handle id={HandleId.Main} type="target" position={Position.Left} className={DOT_HANDLE}/>
                    </div>

                    <div className="flex items-center gap-3">
                        <div
                            className="flex size-11 items-center justify-center rounded-xl bg-purple-500/10 text-purple-500 shadow-inner">
                            <Icon name="brand:ai-agent" className="size-6"/>
                        </div>
                        <div>
                            <p className="text-xs font-bold text-content-main">{data.label}</p>
                            <div className="flex items-center gap-1.5 mt-0.5">
                                <span className="text-[10px] text-content-muted">Autonomous Agent</span>
                                {lastRunItem?.duration_ms !== null && lastRunItem?.duration_ms !== undefined && (
                                    <span className="text-[10px] text-content-muted font-mono" dir="ltr">
                                        • {lastRunItem.duration_ms} ms
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    {isMissingModel && (
                        <div title="نیاز به اتصال مدل چت دارد" className="absolute bottom-2 inset-e-3">
                            <Icon name="alert-triangle" className="size-4 text-primary animate-pulse"/>
                        </div>
                    )}

                    <div
                        className="pointer-events-none absolute -right-1.5 top-1/2 z-10 flex -translate-y-1/2 items-center">
                        <Handle id={HandleId.Main} type="source" position={Position.Right} className={DOT_HANDLE}>
                            {!edges.some(
                                (e) => e.source === id && (e.sourceHandle ?? HandleId.Main) === HandleId.Main,
                            ) && !inProgress && <SidePlus onClick={() => startConnection(id, HandleId.Main)}/>}
                        </Handle>
                    </div>

                    {AGENT_SLOTS.map((slot) => {
                        const connected = isSlotConnected(slot.handle);
                        const compatible =
                            isForeignConnecting
                            && connectingHandle?.handleId === slot.handle
                            && (!slot.single || !connected);
                        const dimmed = isForeignConnecting && !compatible;
                        const showDiamond = connected || compatible;
                        const showHandlePlus = !connected && !inProgress;

                        return (
                            <div
                                key={slot.handle}
                                style={{left: `${slot.ratio * 100}%`}}
                                className={`pointer-events-none absolute top-full z-10 flex -translate-x-1/2 -translate-y-1/2 flex-col items-center transition-opacity ${
                                    dimmed ? 'opacity-30' : ''
                                }`}
                            >
                                <Handle
                                    id={slot.handle}
                                    type="target"
                                    position={Position.Bottom}
                                    title={
                                        slot.single
                                            ? undefined
                                            : 'کلیک: افزودن ابزار جدید • کشیدن: اتصال به ابزار موجود'
                                    }
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        startTargetConnection(id, slot.handle);
                                    }}
                                    className={DIAMOND_HANDLE}
                                >
                                    {showDiamond && (
                                        <span
                                            className={`pointer-events-none absolute inset-0 m-auto size-3 rotate-45 rounded-xs border-2 border-surface-card bg-purple-500 shadow-xs transition hover:scale-125 ${
                                                compatible ? 'scale-150 ring-4 ring-purple-500/30' : ''
                                            }`}
                                        />
                                    )}

                                    {showHandlePlus && (
                                        <span
                                            className="pointer-events-none flex size-5 items-center justify-center rounded-md border-2 border-surface-card bg-purple-500 text-surface-card shadow-xs transition hover:scale-110">
                                            <Icon name="plus" className="size-3 stroke-3"/>
                                        </span>
                                    )}
                                </Handle>

                                <div
                                    className="pointer-events-none absolute top-full mt-2.5 flex flex-col items-center gap-1">
                                    <span className="whitespace-nowrap text-[9px] font-bold text-content-muted">
                                        {slot.label}
                                        {slot.required && <span className="text-primary">*</span>}
                                    </span>
                                </div>
                            </div>
                        );
                    })}
                </div>
            ) : isSupplyNode ? (
                <div
                    className={`pointer-events-auto relative flex size-16 items-center justify-center rounded-full border bg-surface-card shadow-sm transition-all duration-200 ${
                        selected
                            ? 'border-purple-500 shadow-lg ring-4 ring-purple-500/20'
                            : isNodeExecuting
                                ? 'border-amber-500 shadow-lg ring-4 ring-amber-500/20'
                                : 'border-line-border hover:border-content-muted/40'
                    } ${isDisabled ? 'border-dashed' : ''}`}
                >
                    <NodeBadge
                        disabled={isDisabled}
                        executing={isNodeExecuting}
                        missing={missingFields}
                        status={lastRunItem?.status}
                        successCount={successCount}
                    />

                    <div
                        className="pointer-events-none absolute left-1/2 top-0 z-10 flex -translate-x-1/2 -translate-y-1/2 items-center">
                        <Handle id={outputs[0]} type="source" position={Position.Top} className={DIAMOND_HANDLE}>
                            {!isSupplyConnected && (
                                <span
                                    className={`pointer-events-none absolute inset-0 m-auto size-3 rotate-45 rounded-xs border-2 border-surface-card bg-purple-500 shadow-xs transition ${
                                        isForeignConnecting && connectingHandle?.handleId === outputs[0]
                                            ? 'scale-150 ring-4 ring-purple-500/30'
                                            : ''
                                    }`}
                                />
                            )}
                        </Handle>
                    </div>

                    <div className={`flex size-10 items-center justify-center rounded-full shadow-inner ${iconStyle}`}>
                        <Icon name={iconName} className="size-5"/>
                    </div>
                </div>
            ) : (
                <div
                    className={`pointer-events-auto relative flex size-18 items-center justify-center rounded-2xl border bg-surface-card shadow-sm transition-all duration-200 ${
                        selected
                            ? 'border-primary shadow-lg ring-4 ring-primary/20'
                            : isNodeExecuting
                                ? 'border-amber-500 shadow-lg ring-4 ring-amber-500/20'
                                : 'border-line-border hover:border-content-muted/40'
                    } ${isDisabled ? 'border-dashed' : ''}`}
                >
                    <NodeBadge
                        disabled={isDisabled}
                        executing={isNodeExecuting}
                        missing={missingFields}
                        status={lastRunItem?.status}
                        successCount={successCount}
                    />

                    {inputs.map((handleId, index) => {
                        const top = handleTop(index, inputs.length);
                        return (
                            <div
                                key={handleId}
                                style={{top}}
                                className="pointer-events-none absolute -left-1.5 z-10 flex -translate-y-1/2 items-center"
                            >
                                <Handle id={handleId} type="target" position={Position.Left} className={DOT_HANDLE}/>
                            </div>
                        );
                    })}

                    <div className={`flex size-11 items-center justify-center rounded-xl shadow-inner ${iconStyle}`}>
                        <Icon name={iconName} className="size-6"/>
                    </div>

                    {outputs.map((handleId, index) => {
                        const top = handleTop(index, outputs.length);
                        const isConnected = edges.some(
                            (edge) => edge.source === id && (edge.sourceHandle ?? HandleId.Main) === handleId,
                        );
                        const showPlus = !isConnected && !inProgress;

                        return (
                            <div
                                key={handleId}
                                style={{top}}
                                className="pointer-events-none absolute -right-1.5 z-10 flex -translate-y-1/2 items-center"
                            >
                                <Handle id={handleId} type="source" position={Position.Right} className={DOT_HANDLE}>
                                    {showPlus && <SidePlus onClick={() => startConnection(id, handleId)}/>}
                                </Handle>

                                {labeled && (
                                    <span
                                        dir="rtl"
                                        className="pointer-events-none absolute -right-14 rounded bg-surface-raised px-1 py-0.5 text-[9px] font-medium text-content-muted shadow-xs border border-line-border whitespace-nowrap"
                                    >
                                        {handleLabels[handleId] ?? handleId}
                                    </span>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}

            {!isAiAgent && (
                <div
                    dir="auto"
                    className={`pointer-events-auto mt-2.5 text-center select-none ${isSupplyNode ? 'w-24' : 'w-36'}`}
                >
                    <p
                        className={`text-xs font-bold text-content-main ${
                            isSupplyNode ? 'line-clamp-2 wrap-break-word leading-4' : 'truncate'
                        }`}
                    >
                        {data.label}
                    </p>

                    {conditionOutcome && (
                        <span
                            className={`inline-block mt-0.5 rounded px-1.5 py-[0.2rem] text-[9px] font-bold ${
                                conditionOutcome === HandleId.True
                                    ? 'bg-emerald-500/10 text-emerald-500'
                                    : 'bg-primary/10 text-primary'
                            }`}
                        >
                            {conditionOutcome === HandleId.True ? 'نتیجه: بله' : 'نتیجه: خیر'}
                        </span>
                    )}

                    {!conditionOutcome && lastRunItem?.duration_ms !== null && lastRunItem?.duration_ms !== undefined && (
                        <p className="text-[10px] text-content-muted mt-0.5" dir="ltr">
                            {lastRunItem.duration_ms} ms
                        </p>
                    )}

                    {!conditionOutcome && (lastRunItem?.duration_ms === null || lastRunItem?.duration_ms === undefined) && (
                        <>
                            {!definition && <p className="truncate text-[10px] text-primary mt-0.5">نوع ناشناخته</p>}
                            {definition && !isSupplyNode && definition.name !== data.label && (
                                <p className="truncate text-[10px] text-content-muted mt-0.5">{definition.name}</p>
                            )}
                        </>
                    )}
                </div>
            )}
        </div>
    );
}

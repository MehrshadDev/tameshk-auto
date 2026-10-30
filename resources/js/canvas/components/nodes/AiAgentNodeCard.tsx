import {Handle, Position, type Edge} from '@xyflow/react';
import {Icon} from '@canvas/components/ui/Icon';
import {AGENT_SLOTS} from '@canvas/lib/agentLayout';
import {Handle as HandleId} from '@canvas/lib/handles';
import type {FlowNodeData} from '@canvas/lib/graph';
import type {ConnectingHandle} from '@canvas/store/types';
import type {NodeRunData} from '@canvas/types/graph';
import {DIAMOND_HANDLE, DOT_HANDLE, NodeBadge, SidePlus} from './NodeParts';

type AiAgentNodeCardProps = {
    id: string;
    data: FlowNodeData;
    selected?: boolean;
    isDisabled: boolean;
    isNodeExecuting: boolean;
    missingFields: string[];
    lastRunItem?: NodeRunData;
    successCount: number;
    isMissingModel: boolean;
    edges: Edge[];
    inProgress: boolean;
    isForeignConnecting: boolean;
    connectingHandle: ConnectingHandle;
    isSlotConnected: (handle: string) => boolean;
    startConnection: (nodeId: string, handleId: string) => void;
    startTargetConnection: (nodeId: string, handleId: string) => void;
};

export function AiAgentNodeCard({
                                    id,
                                    data,
                                    selected,
                                    isDisabled,
                                    isNodeExecuting,
                                    missingFields,
                                    lastRunItem,
                                    successCount,
                                    isMissingModel,
                                    edges,
                                    inProgress,
                                    isForeignConnecting,
                                    connectingHandle,
                                    isSlotConnected,
                                    startConnection,
                                    startTargetConnection,
                                }: AiAgentNodeCardProps) {
    return (
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

            <div className="pointer-events-none absolute -left-1.5 top-1/2 z-10 flex -translate-y-1/2 items-center">
                <Handle id={HandleId.Main} type="target" position={Position.Left} className={DOT_HANDLE}/>
            </div>

            <div className="flex items-center gap-3">
                <div
                    className="flex size-11 items-center justify-center rounded-xl bg-purple-500/10 text-purple-500 shadow-inner">
                    <Icon name="brand:ai-agent" className="size-6"/>
                </div>
                <div>
                    <p className="text-xs font-bold text-content-main">{data.label}</p>
                    <div className="mt-0.5 flex items-center gap-1.5">
                        <span className="text-[10px] text-content-muted">Autonomous Agent</span>
                        {lastRunItem?.duration_ms !== null && lastRunItem?.duration_ms !== undefined && (
                            <span className="font-mono text-[10px] text-content-muted" dir="ltr">
                                • {lastRunItem.duration_ms} ms
                            </span>
                        )}
                    </div>
                </div>
            </div>

            {isMissingModel && (
                <div title="نیاز به اتصال مدل چت دارد" className="absolute bottom-2 inset-e-3">
                    <Icon name="alert-triangle" className="size-4 animate-pulse text-primary"/>
                </div>
            )}

            <div className="pointer-events-none absolute -right-1.5 top-1/2 z-10 flex -translate-y-1/2 items-center">
                <Handle id={HandleId.Main} type="source" position={Position.Right} className={DOT_HANDLE}>
                    {!edges.some(
                            (e) => e.source === id && (e.sourceHandle ?? HandleId.Main) === HandleId.Main,
                        ) &&
                        !inProgress && <SidePlus onClick={() => startConnection(id, HandleId.Main)}/>}
                </Handle>
            </div>

            {AGENT_SLOTS.map((slot) => {
                const connected = isSlotConnected(slot.handle);
                const compatible =
                    isForeignConnecting &&
                    connectingHandle?.handleId === slot.handle &&
                    (!slot.single || !connected);
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

                        <div className="pointer-events-none absolute top-full mt-2.5 flex flex-col items-center gap-1">
                            <span className="whitespace-nowrap text-[9px] font-bold text-content-muted">
                                {slot.label}
                                {slot.required && <span className="text-primary">*</span>}
                            </span>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

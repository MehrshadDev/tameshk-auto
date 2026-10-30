import { Handle, Position } from '@xyflow/react';
import { Icon } from '@canvas/components/ui/Icon';
import type { ConnectingHandle } from '@canvas/store/types';
import type { NodeRunData } from '@canvas/types/graph';
import { DIAMOND_HANDLE, NodeBadge } from './NodeParts';

type SupplyNodeCardProps = {
    outputHandle: string;
    selected?: boolean;
    isDisabled: boolean;
    isNodeExecuting: boolean;
    missingFields: string[];
    lastRunItem?: NodeRunData;
    successCount: number;
    isSupplyConnected: boolean;
    isForeignConnecting: boolean;
    connectingHandle: ConnectingHandle;
    iconName: string;
    iconStyle: string;
};

export function SupplyNodeCard({
                                   outputHandle,
                                   selected,
                                   isDisabled,
                                   isNodeExecuting,
                                   missingFields,
                                   lastRunItem,
                                   successCount,
                                   isSupplyConnected,
                                   isForeignConnecting,
                                   connectingHandle,
                                   iconName,
                                   iconStyle,
                               }: SupplyNodeCardProps) {
    return (
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

            <div className="pointer-events-none absolute left-1/2 top-0 z-10 flex -translate-x-1/2 -translate-y-1/2 items-center">
                <Handle id={outputHandle} type="source" position={Position.Top} className={DIAMOND_HANDLE}>
                    {!isSupplyConnected && (
                        <span
                            className={`pointer-events-none absolute inset-0 m-auto size-3 rotate-45 rounded-xs border-2 border-surface-card bg-purple-500 shadow-xs transition ${
                                isForeignConnecting && connectingHandle?.handleId === outputHandle
                                    ? 'scale-150 ring-4 ring-purple-500/30'
                                    : ''
                            }`}
                        />
                    )}
                </Handle>
            </div>

            <div className={`flex size-10 items-center justify-center rounded-full shadow-inner ${iconStyle}`}>
                <Icon name={iconName} className="size-5" />
            </div>
        </div>
    );
}

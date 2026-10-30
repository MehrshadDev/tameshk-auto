import type { Edge, EdgeChange, NodeChange, XYPosition } from '@xyflow/react';
import type { FlowNode } from '@canvas/lib/graph';
import { isFlowHandle } from '@canvas/lib/handles';
import type { SaveStatus, Snapshot } from '@canvas/store/types';

export const HISTORY_LIMIT = 30;
export const LOG_LIMIT = 20;

export const dirty = (state: { revision: number; saveStatus: SaveStatus }) => ({
    revision: state.revision + 1,
    saveStatus: state.saveStatus === 'saving' ? state.saveStatus : ('dirty' as const),
});

export const pushHistory = (state: { past: Snapshot[]; nodes: FlowNode[]; edges: Edge[] }) => ({
    past: [...state.past, { nodes: state.nodes, edges: state.edges }].slice(-HISTORY_LIMIT),
    future: [] as Snapshot[],
});

export const isPersistentNodeChange = (change: NodeChange<FlowNode>) =>
    change.type === 'remove' ||
    change.type === 'add' ||
    (change.type === 'position' && change.dragging === false);

export const isPersistentEdgeChange = (change: EdgeChange) =>
    change.type === 'remove' || change.type === 'add' || change.type === 'replace';

const snapGrid = (value: number) => Math.round(value / 16) * 16;

export const calculateNextPosition = (nodes: FlowNode[], position: XYPosition): XYPosition => {
    const threshold = 24;
    let x = snapGrid(position.x);
    let y = snapGrid(position.y);

    while (nodes.some((n) => Math.abs(n.position.x - x) < threshold && Math.abs(n.position.y - y) < threshold)) {
        x += 32;
        y += 32;
    }

    return { x, y };
};

export const withRunningAnimation = (edges: Edge[], runningNodeId: string | null): Edge[] =>
    edges.map((edge) => {
        const shouldAnimate =
            isFlowHandle(edge.sourceHandle ?? 'main') && runningNodeId !== null && edge.source === runningNodeId;

        return edge.animated === shouldAnimate ? edge : { ...edge, animated: shouldAnimate };
    });

export const nodeLabelByDbId = (
    nodes: FlowNode[],
    ids: Record<string, number>,
    dbId: number,
): string | null => {
    const key = Object.keys(ids).find((k) => ids[k] === dbId) ?? String(dbId);

    return nodes.find((n) => n.id === key)?.data.label ?? null;
};

export const logsStorageKey = (workflowId: number) => `wf_${workflowId}_logs`;

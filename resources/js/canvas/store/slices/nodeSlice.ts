import type { Edge, XYPosition } from '@xyflow/react';
import {
    createFlowNode,
    createNoteNode,
    getUniqueLabel,
    type FlowNodeData,
} from '@canvas/lib/graph';
import {
    AGENT_NODE_TYPE,
    AGENT_WIDTH,
    SUPPLY_NODE_WIDTH,
    SUPPLY_OFFSET_Y,
    isSingleSlot,
    slotRatio,
} from '@canvas/lib/agentLayout';
import type { NodeDefinition, NodePreset } from '@canvas/types/graph';
import type {
    PendingConnection,
    PendingTargetConnection,
    ConnectingHandle,
} from '@canvas/store/types';
import { calculateNextPosition, dirty, pushHistory } from '@canvas/store/helpers';
import type { Slice } from './types';

export type NodeSlice = {
    pendingConnection: PendingConnection;
    pendingTargetConnection: PendingTargetConnection;
    connectingHandle: ConnectingHandle;
    addNode: (definition: NodeDefinition, position?: XYPosition, preset?: NodePreset) => void;
    addNote: (position: XYPosition) => void;
    deleteNode: (nodeId: string) => void;
    toggleNodeDisabled: (nodeId: string) => void;
    updateNode: (id: string, data: Partial<FlowNodeData>) => void;
    selectAll: () => void;
    startConnection: (sourceNodeId: string, sourceHandle: string) => void;
    startTargetConnection: (targetNodeId: string, targetHandle: string) => void;
    setConnectingHandle: (handle: ConnectingHandle) => void;
};

export const createNodeSlice: Slice<NodeSlice> = (set, get) => ({
    pendingConnection: null,
    pendingTargetConnection: null,
    connectingHandle: null,

    addNode: (definition, position, preset) => {
        const { pendingConnection, pendingTargetConnection, nodes, edges, definitions } = get();

        if (definition.category === 'trigger') {
            const alreadyHasTrigger = nodes.some((node) => {
                const def = definitions.find((d) => d.type === node.data.nodeType);
                return def?.category === 'trigger';
            });

            if (alreadyHasTrigger) return;
        }

        let adjustedPos: XYPosition;

        if (pendingTargetConnection) {
            const target = nodes.find((n) => n.id === pendingTargetConnection.targetNodeId);
            const targetHandle = pendingTargetConnection.targetHandle;

            if (target) {
                const baseX = target.position.x + AGENT_WIDTH * slotRatio(targetHandle) - SUPPLY_NODE_WIDTH / 2;
                const baseY = target.position.y + SUPPLY_OFFSET_Y;

                if (isSingleSlot(targetHandle)) {
                    adjustedPos = { x: baseX, y: baseY };
                } else {
                    const existingCount = edges.filter(
                        (e) => e.target === target.id && e.targetHandle === targetHandle,
                    ).length;
                    const offset = existingCount * 110;

                    adjustedPos = calculateNextPosition(nodes, {
                        x: baseX + offset,
                        y: baseY,
                    });
                }
            } else {
                adjustedPos = calculateNextPosition(nodes, position ?? { x: 100, y: 100 });
            }
        } else if (pendingConnection) {
            const src = nodes.find((n) => n.id === pendingConnection.sourceNodeId);
            const fromAgent = src?.data.nodeType === AGENT_NODE_TYPE;

            adjustedPos = calculateNextPosition(
                nodes,
                src
                    ? {
                        x: src.position.x + (fromAgent ? AGENT_WIDTH + 64 : 240),
                        y: src.position.y + (fromAgent ? 4 : 0),
                    }
                    : position ?? { x: 100, y: 100 },
            );
        } else {
            adjustedPos = calculateNextPosition(nodes, position ?? { x: 100, y: 100 });
        }

        const node = createFlowNode(definition, adjustedPos, nodes, preset);

        let nextEdges = edges;

        if (pendingTargetConnection) {
            const sourceHandle = definition.outputs[0] ?? 'main';
            const newEdge: Edge = {
                id: `new-${crypto.randomUUID()}`,
                source: node.id,
                target: pendingTargetConnection.targetNodeId,
                sourceHandle,
                targetHandle: pendingTargetConnection.targetHandle,
                type: 'workflow',
            };
            nextEdges = [...edges, newEdge];
        } else if (pendingConnection) {
            const targetHandle = definition.inputs[0] ?? 'main';
            const newEdge: Edge = {
                id: `new-${crypto.randomUUID()}`,
                source: pendingConnection.sourceNodeId,
                target: node.id,
                sourceHandle: pendingConnection.sourceHandle,
                targetHandle,
                type: 'workflow',
            };
            nextEdges = [...edges, newEdge];
        }

        set((state) => ({
            ...pushHistory(state),
            nodes: [...state.nodes.map((item) => ({ ...item, selected: false })), node],
            edges: nextEdges,
            pendingConnection: null,
            pendingTargetConnection: null,
            panel: Object.keys(definition.configSchema).length > 0 ? { type: 'node', id: node.id } : null,
            ...dirty(state),
        }));
    },

    addNote: (position) => {
        const { nodes } = get();
        const adjustedPos = calculateNextPosition(nodes, position);
        const note = createNoteNode(adjustedPos, nodes);

        set((state) => ({
            ...pushHistory(state),
            nodes: [...state.nodes.map((item) => ({ ...item, selected: false })), note],
            ...dirty(state),
        }));
    },

    deleteNode: (nodeId) => {
        set((state) => ({
            ...pushHistory(state),
            nodes: state.nodes.filter((node) => node.id !== nodeId),
            edges: state.edges.filter((edge) => edge.source !== nodeId && edge.target !== nodeId),
            panel: state.panel?.type === 'node' && state.panel.id === nodeId ? null : state.panel,
            ...dirty(state),
        }));
    },

    toggleNodeDisabled: (nodeId) => {
        set((state) => ({
            nodes: state.nodes.map((node) => {
                if (node.id !== nodeId) return node;

                const currentDisabled = Boolean(node.data.config?.disabled);

                return {
                    ...node,
                    data: {
                        ...node.data,
                        config: {
                            ...node.data.config,
                            disabled: !currentDisabled,
                        },
                    },
                };
            }),
            ...dirty(state),
        }));
    },

    updateNode: (id, data) =>
        set((state) => {
            const nextData = { ...data };

            if (typeof data.label === 'string') {
                nextData.label = getUniqueLabel(data.label, state.nodes, id);
            }

            return {
                nodes: state.nodes.map((node) =>
                    node.id === id ? { ...node, data: { ...node.data, ...nextData } } : node,
                ),
                ...dirty(state),
            };
        }),

    selectAll: () =>
        set((state) => ({
            nodes: state.nodes.map((node) => ({ ...node, selected: true })),
            edges: state.edges.map((edge) => ({ ...edge, selected: true })),
        })),

    startConnection: (sourceNodeId, sourceHandle) => {
        set({
            pendingConnection: { sourceNodeId, sourceHandle },
            pendingTargetConnection: null,
            panel: { type: 'picker' },
        });
    },

    startTargetConnection: (targetNodeId, targetHandle) => {
        set({
            pendingTargetConnection: { targetNodeId, targetHandle },
            pendingConnection: null,
            panel: { type: 'picker' },
        });
    },

    setConnectingHandle: (connectingHandle) => set({ connectingHandle }),
});

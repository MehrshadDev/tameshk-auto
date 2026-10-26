import type { Edge, Node, XYPosition } from '@xyflow/react';
import type { ConfigField, GraphPayload, NodeDefinition, NodePreset, ServerEdge, ServerNode } from '@canvas/types/graph';

export type FlowNodeData = {
    label: string;
    nodeType: string;
    config: Record<string, unknown>;
};

export type FlowNode = Node<FlowNodeData, 'workflow' | 'note'>;

export const getUniqueLabel = (baseName: string, existingNodes: FlowNode[], excludeId?: string): string => {
    const trimmed = baseName.trim();
    const cleanBase = trimmed.replace(/\s+\d+$/, '');
    const otherNodes = existingNodes.filter((n) => n.id !== excludeId);
    const existingLabels = new Set(otherNodes.map((n) => n.data.label.trim().toLowerCase()));

    if (!existingLabels.has(cleanBase.toLowerCase())) {
        return cleanBase;
    }

    let counter = 1;
    while (existingLabels.has(`${cleanBase.toLowerCase()} ${counter}`)) {
        counter++;
    }

    return `${cleanBase} ${counter}`;
};

export const toFlowNode = (node: ServerNode): FlowNode => ({
    id: String(node.id),
    type: node.type === 'sticky_note' ? 'note' : 'workflow',
    position: node.position,
    data: {
        label: node.name,
        nodeType: node.type,
        config: node.config,
    },
});

export const toFlowEdge = (edge: ServerEdge): Edge => ({
    id: `e${edge.id}`,
    source: String(edge.source),
    target: String(edge.target),
    sourceHandle: edge.sourceHandle,
    targetHandle: edge.targetHandle ?? undefined,
    type: 'workflow',
});

export const defaultConfig = (schema: Record<string, ConfigField>): Record<string, unknown> =>
    Object.fromEntries(
        Object.entries(schema)
            .filter(([, field]) => field.default !== undefined && field.default !== null)
            .map(([key, field]) => [key, field.default]),
    );

export const createFlowNode = (
    definition: NodeDefinition,
    position: XYPosition,
    existingNodes: FlowNode[] = [],
    preset?: NodePreset,
): FlowNode => ({
    id: `new-${crypto.randomUUID()}`,
    type: 'workflow',
    position,
    selected: true,
    data: {
        label: getUniqueLabel(preset?.name ?? definition.name, existingNodes),
        nodeType: definition.type,
        config: { ...defaultConfig(definition.configSchema), ...(preset?.config ?? {}) },
    },
});

export const createNoteNode = (position: XYPosition, existingNodes: FlowNode[] = []): FlowNode => ({
    id: `new-${crypto.randomUUID()}`,
    type: 'note',
    position,
    selected: true,
    data: {
        label: getUniqueLabel('Sticky Note', existingNodes),
        nodeType: 'sticky_note',
        config: {
            content: '## Note Title\nWrite your note content with Markdown support...',
        },
    },
});

export const toGraphPayload = (
    nodes: FlowNode[],
    edges: Edge[],
    ids: Record<string, number>,
): GraphPayload => ({
    nodes: nodes.map((node) => ({
        key: node.id,
        id: ids[node.id] ?? null,
        type: node.data.nodeType,
        name: node.data.label.trim().slice(0, 100) || node.data.nodeType,
        position: { x: node.position.x, y: node.position.y },
        config: node.data.config,
    })),
    edges: edges.map((edge) => ({
        source: edge.source,
        target: edge.target,
        sourceHandle: edge.sourceHandle ?? 'main',
        targetHandle: edge.targetHandle ?? null,
    })),
});

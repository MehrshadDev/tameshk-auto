import type { Edge, XYPosition } from '@xyflow/react';
import { getUniqueLabel, type FlowNode } from '@canvas/lib/graph';
import type { ClipboardData } from '@canvas/store/types';
import { dirty, pushHistory } from '@canvas/store/helpers';
import type { Slice } from './types';

export type ClipboardSlice = {
    clipboard: ClipboardData;
    copySelected: () => void;
    paste: (targetPosition?: XYPosition) => void;
    duplicateSelected: () => void;
};

export const createClipboardSlice: Slice<ClipboardSlice> = (set, get) => ({
    clipboard: null,

    copySelected: () => {
        const { nodes, edges, addToast } = get();
        const selectedNodes = nodes.filter((n) => n.selected);

        if (selectedNodes.length === 0) return;

        const selectedNodeIds = new Set(selectedNodes.map((n) => n.id));
        const internalEdges = edges.filter(
            (e) => selectedNodeIds.has(e.source) && selectedNodeIds.has(e.target),
        );

        const payload = {
            version: 'tameshk-1',
            nodes: JSON.parse(JSON.stringify(selectedNodes)),
            edges: JSON.parse(JSON.stringify(internalEdges)),
        };

        set({ clipboard: payload });

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard
                .writeText(JSON.stringify(payload, null, 2))
                .then(() => addToast('نودهای انتخاب‌شده در کلیپ‌بورد کپی شدند.', 'success', 2500))
                .catch(() => {});
        }
    },

    paste: (targetPosition) => {
        const { clipboard, nodes, edges, definitions } = get();

        if (!clipboard || clipboard.nodes.length === 0) return;

        const hasTrigger = nodes.some((n) => {
            const def = definitions.find((d) => d.type === n.data.nodeType);
            return def?.category === 'trigger';
        });

        const nodesToPaste = clipboard.nodes.filter((node) => {
            const def = definitions.find((d) => d.type === node.data.nodeType);
            return !(def?.category === 'trigger' && hasTrigger);
        });

        if (nodesToPaste.length === 0) return;

        let offsetX = 40;
        let offsetY = 40;

        if (targetPosition) {
            const minX = Math.min(...nodesToPaste.map((n) => n.position.x));
            const minY = Math.min(...nodesToPaste.map((n) => n.position.y));
            offsetX = targetPosition.x - minX;
            offsetY = targetPosition.y - minY;
        }

        const idMap: Record<string, string> = {};
        const workingNodesList = [...nodes];

        const newNodes: FlowNode[] = nodesToPaste.map((node) => {
            const newId = `new-${crypto.randomUUID()}`;
            idMap[node.id] = newId;

            const uniqueName = getUniqueLabel(node.data.label, workingNodesList);

            const createdNode: FlowNode = {
                ...node,
                id: newId,
                selected: true,
                position: {
                    x: node.position.x + offsetX,
                    y: node.position.y + offsetY,
                },
                data: {
                    ...node.data,
                    label: uniqueName,
                    config: JSON.parse(JSON.stringify(node.data.config ?? {})),
                },
            };

            workingNodesList.push(createdNode);
            return createdNode;
        });

        const newEdges: Edge[] = clipboard.edges
            .filter((edge) => idMap[edge.source] && idMap[edge.target])
            .map((edge) => ({
                ...edge,
                id: `new-${crypto.randomUUID()}`,
                source: idMap[edge.source],
                target: idMap[edge.target],
                selected: true,
                type: 'workflow',
            }));

        const unselectedNodes = nodes.map((n) => ({ ...n, selected: false }));
        const unselectedEdges = edges.map((e) => ({ ...e, selected: false }));

        set((state) => ({
            ...pushHistory(state),
            nodes: [...unselectedNodes, ...newNodes],
            edges: [...unselectedEdges, ...newEdges],
            ...dirty(state),
        }));
    },

    duplicateSelected: () => {
        get().copySelected();
        get().paste();
    },
});

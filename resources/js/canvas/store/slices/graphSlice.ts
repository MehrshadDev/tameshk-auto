import {
    addEdge,
    applyEdgeChanges,
    applyNodeChanges,
    type Connection,
    type Edge,
    type EdgeChange,
    type NodeChange,
} from '@xyflow/react';
import { fetchGraph, saveGraph } from '@canvas/api/workflows';
import { toFlowEdge, toFlowNode, toGraphPayload, type FlowNode } from '@canvas/lib/graph';
import type { GraphResponse, NodeDefinition, TriggerEndpoint } from '@canvas/types/graph';
import { ApiError } from '@canvas/api/client';
import { isSpecialHandle } from '@canvas/lib/handles';
import type {
    ChatMessage,
    LogEntry,
    SaveStatus,
    Snapshot,
    Status,
} from '@canvas/store/types';
import {
    dirty,
    isPersistentEdgeChange,
    isPersistentNodeChange,
    pushHistory,
    logsStorageKey,
    LOG_LIMIT,
    HISTORY_LIMIT,
} from '@canvas/store/helpers';
import type { Slice } from './types';

export type GraphSlice = {
    status: Status;
    workflow: GraphResponse['workflow'] | null;
    definitions: NodeDefinition[];
    endpoints: Record<string, TriggerEndpoint[]>;
    nodes: FlowNode[];
    edges: Edge[];
    ids: Record<string, number>;
    revision: number;
    savedRevision: number;
    saveStatus: SaveStatus;
    past: Snapshot[];
    future: Snapshot[];
    load: (workflowId: number) => Promise<void>;
    save: () => Promise<void>;
    undo: () => void;
    redo: () => void;
    onNodesChange: (changes: NodeChange<FlowNode>[]) => void;
    onEdgesChange: (changes: EdgeChange[]) => void;
    onConnect: (connection: Connection) => void;
};

export const createGraphSlice: Slice<GraphSlice> = (set, get) => ({
    status: 'loading',
    workflow: null,
    definitions: [],
    endpoints: {},
    nodes: [],
    edges: [],
    ids: {},
    revision: 0,
    savedRevision: 0,
    saveStatus: 'saved',
    past: [],
    future: [],

    load: async (workflowId) => {
        set({ status: 'loading' });

        try {
            const graph = await fetchGraph(workflowId);

            const storageSessionKey = `wf_${workflowId}_chat_session`;
            const storageMessagesKey = `wf_${workflowId}_chat_messages`;

            let savedSession = localStorage.getItem(storageSessionKey);
            if (!savedSession) {
                savedSession = crypto.randomUUID();
                localStorage.setItem(storageSessionKey, savedSession);
            }

            let savedMessages: ChatMessage[] = [];
            let savedLogs: LogEntry[] = [];

            try {
                const raw = localStorage.getItem(logsStorageKey(workflowId));
                const parsed = raw ? JSON.parse(raw) : [];
                if (Array.isArray(parsed)) savedLogs = parsed.slice(0, LOG_LIMIT);
            } catch {}

            try {
                const raw = localStorage.getItem(storageMessagesKey);
                if (raw) savedMessages = JSON.parse(raw);
            } catch {}

            set({
                status: 'ready',
                workflow: graph.workflow,
                definitions: graph.definitions,
                endpoints: graph.endpoints ?? {},
                nodes: graph.nodes.map(toFlowNode),
                edges: graph.edges.map(toFlowEdge),
                ids: Object.fromEntries(graph.nodes.map((node) => [String(node.id), node.id])),
                revision: 0,
                savedRevision: 0,
                saveStatus: 'saved',
                panel: null,
                exitPromptOpen: false,
                isRunning: false,
                runningNodeId: null,
                lastRun: null,
                pendingConnection: null,
                pendingTargetConnection: null,
                connectingHandle: null,
                clipboard: null,
                past: [],
                future: [],
                chatSessionId: savedSession,
                chatMessages: savedMessages,
                logs: savedLogs,
            });
        } catch {
            set({ status: 'error' });
            get().addToast('خطا در بارگذاری اطلاعات ورکفلو رخ داد.', 'error');
        }
    },

    save: async () => {
        const { workflow, nodes, edges, ids, revision, savedRevision, saveStatus, addToast, addLog } = get();

        if (!workflow || saveStatus === 'saving' || (saveStatus === 'saved' && revision === savedRevision)) {
            return;
        }

        set({ saveStatus: 'saving' });

        try {
            const response = await saveGraph(workflow.id, toGraphPayload(nodes, edges, ids));

            set((state) => ({
                ids: { ...state.ids, ...response.ids },
                savedRevision: revision,
                saveStatus: state.revision === revision ? 'saved' : 'dirty',
                workflow:
                    state.workflow && response.published === false
                        ? { ...state.workflow, is_active: false, published_at: null }
                        : state.workflow,
            }));

            if (response.unpublished_reason) {
                addLog({ source: 'publish', nodeName: null, message: response.unpublished_reason });
                addToast(`ذخیره شد، ولی انتشار لغو شد: ${response.unpublished_reason}`, 'error', 10000);
            }
        } catch (error) {
            set({ saveStatus: 'error' });

            if (error instanceof ApiError) {
                if (error.status === 422) {
                    Object.values(error.errors)
                        .flat()
                        .forEach((message) => addLog({ source: 'save', nodeName: null, message }));

                    const firstMessage = error.firstError() ?? 'اطلاعات ورکفلو نامعتبر است.';
                    const extra = error.errorCount() > 1 ? ` (و ${error.errorCount() - 1} خطای دیگر در لاگ‌ها)` : '';
                    addToast(`خطای اعتبارسنجی: ${firstMessage}${extra}`, 'error', 8000);
                    return;
                }

                addLog({ source: 'save', nodeName: null, message: error.message || `HTTP ${error.status}` });

                if (error.status === 429) {
                    addToast('تعداد درخواست‌ها زیاد است. چند لحظه صبر کنید.', 'error');
                } else if (error.status === 403) {
                    addToast('شما اجازه انجام این عملیات را ندارید.', 'error');
                } else if (error.status === 409) {
                    addToast(error.message, 'error');
                } else if (error.status >= 500) {
                    addToast('خطای سرور در ذخیره‌سازی. لطفاً بعداً تلاش کنید.', 'error');
                } else {
                    addToast(error.message || 'خطا در ذخیره‌سازی ورکفلو.', 'error');
                }
            } else {
                addLog({ source: 'save', nodeName: null, message: 'Network error while saving.' });
                addToast('خطا در ارتباط با سرور. اتصال اینترنت را بررسی کنید.', 'error');
            }
        }
    },

    undo: () => {
        const { past, future } = get();
        if (past.length === 0) return;

        const previous = past[past.length - 1];
        const newPast = past.slice(0, past.length - 1);

        set((state) => ({
            past: newPast,
            future: [{ nodes: state.nodes, edges: state.edges }, ...future].slice(0, HISTORY_LIMIT),
            nodes: previous.nodes,
            edges: previous.edges,
            ...dirty(state),
        }));
    },

    redo: () => {
        const { past, future } = get();
        if (future.length === 0) return;

        const next = future[0];
        const newFuture = future.slice(1);

        set((state) => ({
            past: [...past, { nodes: state.nodes, edges: state.edges }].slice(-HISTORY_LIMIT),
            future: newFuture,
            nodes: next.nodes,
            edges: next.edges,
            ...dirty(state),
        }));
    },

    onNodesChange: (changes) =>
        set((state) => {
            const hasPersistent = changes.some(isPersistentNodeChange);
            const nextNodes = applyNodeChanges(changes, state.nodes);

            return {
                ...(hasPersistent ? pushHistory(state) : {}),
                nodes: nextNodes,
                ...(hasPersistent ? dirty(state) : {}),
            };
        }),

    onEdgesChange: (changes) =>
        set((state) => {
            const hasPersistent = changes.some(isPersistentEdgeChange);
            const nextEdges = applyEdgeChanges(changes, state.edges);

            return {
                ...(hasPersistent ? pushHistory(state) : {}),
                edges: nextEdges,
                ...(hasPersistent ? dirty(state) : {}),
            };
        }),

    onConnect: (connection) => {
        const state = get();
        const nodeA = state.nodes.find((n) => n.id === connection.source);
        const nodeB = state.nodes.find((n) => n.id === connection.target);

        if (!nodeA || !nodeB) return;

        const defA = state.definitions.find((d) => d.type === nodeA.data.nodeType);
        const defB = state.definitions.find((d) => d.type === nodeB.data.nodeType);

        const isASupply = Boolean(defA && defA.inputs.length === 0 && defA.outputs.length > 0 && defA.outputs.every(isSpecialHandle));
        const isBSupply = Boolean(defB && defB.inputs.length === 0 && defB.outputs.length > 0 && defB.outputs.every(isSpecialHandle));

        let finalConnection = { ...connection };

        if (!isASupply && isBSupply) {
            finalConnection = {
                source: connection.target,
                sourceHandle: connection.targetHandle ?? defB?.outputs[0] ?? 'tool',
                target: connection.source,
                targetHandle: connection.sourceHandle ?? defB?.outputs[0] ?? 'tool',
            };
        }

        set((state) => ({
            ...pushHistory(state),
            edges: addEdge({ ...finalConnection, id: `new-${crypto.randomUUID()}`, type: 'workflow' }, state.edges),
            ...dirty(state),
        }));
    },
});

import { clearWorkflowMemory } from '@canvas/api/workflows';
import { extractReply } from '@canvas/lib/chatReply';
import { isSpecialHandle } from '@canvas/lib/handles';
import type { ChatMessage } from '@canvas/store/types';
import type { Slice } from './types';

export type ChatSlice = {
    isChatOpen: boolean;
    chatMessages: ChatMessage[];
    chatSessionId: string;
    toggleChat: () => void;
    setChatOpen: (open: boolean) => void;
    clearChat: () => Promise<void>;
    sendChatMessage: (text: string) => Promise<void>;
};

export const createChatSlice: Slice<ChatSlice> = (set, get) => ({
    isChatOpen: false,
    chatMessages: [],
    chatSessionId: crypto.randomUUID(),

    toggleChat: () => set((state) => ({ isChatOpen: !state.isChatOpen })),

    setChatOpen: (open: boolean) => set({ isChatOpen: open }),

    clearChat: async () => {
        const { isRunning, workflow, chatSessionId, addToast } = get();

        if (isRunning) return;

        if (workflow) {
            try {
                await clearWorkflowMemory(workflow.id, chatSessionId);
            } catch {
                addToast('پاک‌سازی حافظه ایجنت روی سرور با خطا مواجه شد.', 'error', 4000);
            }
        }

        const newSessionId = crypto.randomUUID();

        if (workflow?.id) {
            localStorage.setItem(`wf_${workflow.id}_chat_session`, newSessionId);
            localStorage.removeItem(`wf_${workflow.id}_chat_messages`);
        }

        set({
            chatMessages: [],
            chatSessionId: newSessionId,
        });
    },

    sendChatMessage: async (text) => {
        const { isRunning, executeWorkflow, chatSessionId, workflow } = get();
        const clean = text.trim();

        if (!clean || isRunning) return;

        const append = (role: ChatMessage['role'], body: string) => {
            const list: ChatMessage[] = [
                ...get().chatMessages,
                { id: crypto.randomUUID(), role, text: body },
            ];

            if (workflow?.id) {
                localStorage.setItem(`wf_${workflow.id}_chat_messages`, JSON.stringify(list));
            }

            set({ chatMessages: list });
        };

        append('user', clean);

        const prevRunId = get().lastRun?.id ?? null;

        await executeWorkflow({ chatInput: clean, sessionId: chatSessionId });

        const { lastRun: run, nodes, ids, definitions } = get();

        if (!run || run.id === prevRunId) {
            append('error', 'Execution was not completed.');
            return;
        }

        if (run.status === 'failed') {
            append('error', run.error ?? 'Workflow execution failed.');
            return;
        }

        if (run.status === 'canceled') {
            append('error', 'Workflow execution was canceled.');
            return;
        }

        const toDbId = (key: string) => ids[key] ?? Number(key);

        const supplyIds = new Set(
            nodes
                .filter((node) => {
                    const def = definitions.find((d) => d.type === node.data.nodeType);

                    return (
                        def !== undefined
                        && def.inputs.length === 0
                        && def.outputs.length > 0
                        && def.outputs.every(isSpecialHandle)
                    );
                })
                .map((node) => toDbId(node.id)),
        );

        const triggerIds = new Set(
            nodes.filter((node) => node.data.nodeType === 'trigger.chat').map((node) => toDbId(node.id)),
        );

        const last = [...(run.node_runs ?? [])].reverse().find((r) => !supplyIds.has(r.node_id));

        if (!last || triggerIds.has(last.node_id)) return;

        if (last.status === 'failed') {
            append('error', last.error ?? 'Final node failed.');
            return;
        }

        const reply = extractReply(last.output_data);

        if (reply !== '') {
            append('assistant', reply);
        }
    },
});

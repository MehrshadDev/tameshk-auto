import { request } from '@canvas/api/client';
import type { GraphPayload, GraphResponse, NodeRunData, PublishResponse, RunWorkflowResponse, SaveGraphResponse, WorkflowSummary } from '@canvas/types/graph';

let activeStreamController: AbortController | null = null;

const graphUrl = (workflowId: number) => `/app/api/workflows/${workflowId}/graph`;

export const fetchGraph = (workflowId: number) => request<GraphResponse>(graphUrl(workflowId));

export const saveGraph = (workflowId: number, payload: GraphPayload) =>
    request<SaveGraphResponse>(graphUrl(workflowId), {
        method: 'PUT',
        body: JSON.stringify(payload),
    });

export const runWorkflow = (workflowId: number, data: Record<string, unknown> = {}) =>
    request<RunWorkflowResponse>(`/app/api/workflows/${workflowId}/run`, {
        method: 'POST',
        body: JSON.stringify({ data }),
    });

export async function runWorkflowStream(
    workflowId: number,
    onProgress: (event: { event: string; node_id?: number; node_run?: any; error?: string; run?: any }) => void,
    data: Record<string, unknown> = {},
) {
    if (activeStreamController) activeStreamController.abort();

    activeStreamController = new AbortController();
    const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    try {
        const response = await fetch(`/app/api/workflows/${workflowId}/run?stream=1`, {
            method: 'POST',
            signal: activeStreamController.signal,
            headers: {
                Accept: 'text/event-stream',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ data }),
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        if (!response.body) return;

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (true) {
            const { value, done } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n\n');
            buffer = lines.pop() ?? '';

            for (const line of lines) {
                const trimmed = line.trim();
                if (trimmed.startsWith('data: ')) {
                    try {
                        onProgress(JSON.parse(trimmed.substring(6)));
                    } catch {}
                }
            }
        }
    } finally {
        activeStreamController = null;
    }
}

export function cancelWorkflowStream(workflowId: number) {
    if (activeStreamController) {
        activeStreamController.abort();
        activeStreamController = null;
    }

    return request<{ status: string }>(`/app/api/workflows/${workflowId}/cancel`, {
        method: 'POST',
    });
}

export const runSingleNode = (workflowId: number, nodeId: number) =>
    request<{ node_run: NodeRunData; run?: any }>(`/app/api/workflows/${workflowId}/nodes/${nodeId}/run`, {
        method: 'POST',
    });

export const clearWorkflowMemory = (workflowId: number, sessionId: string) =>
    request<{ status: string }>(`/app/api/workflows/${workflowId}/memory/clear`, {
        method: 'POST',
        body: JSON.stringify({ session_id: sessionId }),
    });

export const publishWorkflow = (workflowId: number) =>
    request<PublishResponse>(`/app/api/workflows/${workflowId}/publish`, { method: 'POST' });

export const unpublishWorkflow = (workflowId: number) =>
    request<PublishResponse>(`/app/api/workflows/${workflowId}/unpublish`, { method: 'POST' });

export const renameWorkflow = (workflowId: number, name: string) =>
    request<{ workflow: WorkflowSummary }>(`/app/api/workflows/${workflowId}`, {
        method: 'PATCH',
        body: JSON.stringify({ name }),
    });

import { cancelWorkflowStream, runSingleNode, runWorkflowStream } from '@canvas/api/workflows';
import { ApiError } from '@canvas/api/client';
import type { NodeRunData, WorkflowRunResult } from '@canvas/types/graph';
import { withRunningAnimation, nodeLabelByDbId } from '@canvas/store/helpers';
import type { Slice } from './types';

export type ExecutionSlice = {
    isRunning: boolean;
    runningNodeId: string | null;
    lastRun: WorkflowRunResult | null;
    executeWorkflow: (data?: Record<string, unknown>) => Promise<void>;
    cancelWorkflow: () => Promise<void>;
    executeSingleNode: (nodeKey: string) => Promise<void>;
};

export const createExecutionSlice: Slice<ExecutionSlice> = (set, get) => ({
    isRunning: false,
    runningNodeId: null,
    lastRun: null,

    executeWorkflow: async (data = {}) => {
        const { workflow, saveStatus, save, isRunning, addToast, addLog } = get();

        if (!workflow || isRunning) return;

        if (saveStatus !== 'saved') {
            await save();
            if (get().saveStatus === 'error') return;
        }

        set({ isRunning: true });

        let nodeFailureLogged = false;

        try {
            await runWorkflowStream(
                workflow.id,
                (progress) => {
                    const currentIds = get().ids;

                    if (progress.event === 'running' && progress.node_id) {
                        const matchKey =
                            Object.keys(currentIds).find((k) => currentIds[k] === progress.node_id) ??
                            String(progress.node_id);

                        set((state) => ({
                            runningNodeId: matchKey,
                            edges: withRunningAnimation(state.edges, matchKey),
                        }));
                    } else if (
                        (progress.event === 'node_success' || progress.event === 'node_failed') &&
                        progress.node_run
                    ) {
                        if (progress.event === 'node_failed') {
                            nodeFailureLogged = true;
                            addLog({
                                source: 'node',
                                nodeName: nodeLabelByDbId(get().nodes, currentIds, progress.node_run.node_id),
                                message: progress.node_run.error ?? 'Unknown error',
                            });
                        }

                        set((state) => {
                            const existing = state.lastRun?.node_runs ?? [];
                            const updated = [
                                ...existing.filter((r) => r.id !== progress.node_run.id),
                                progress.node_run,
                            ];

                            return {
                                runningNodeId: progress.event === 'node_failed' ? null : state.runningNodeId,
                                lastRun: state.lastRun
                                    ? { ...state.lastRun, node_runs: updated }
                                    : {
                                        id: progress.node_run.workflow_run_id,
                                        workflow_id: workflow.id,
                                        status: progress.event === 'node_failed' ? 'failed' : 'running',
                                        trigger_type: 'stream',
                                        trigger_data: {},
                                        error: progress.node_run.error,
                                        node_runs: updated,
                                    },
                            };
                        });
                    } else if (progress.event === 'completed' && progress.run) {
                        set((state) => ({
                            lastRun: progress.run,
                            runningNodeId: null,
                            edges: withRunningAnimation(state.edges, null),
                        }));
                    } else if (progress.event === 'failed') {
                        if (progress.run) set({ lastRun: progress.run });

                        set((state) => ({
                            runningNodeId: null,
                            edges: withRunningAnimation(state.edges, null),
                        }));

                        if (!nodeFailureLogged) {
                            addLog({
                                source: 'run',
                                nodeName: null,
                                message: progress.error ?? 'Execution failed.',
                            });
                        }

                        addToast(progress.error ?? 'Execution failed.', 'error');
                    }
                },
                data,
            );
        } catch (error: any) {
            if (error?.name !== 'AbortError') {
                const message = error?.message ?? 'Network connection error.';
                addLog({ source: 'run', nodeName: null, message });
                addToast(message, 'error');
            }
        } finally {
            set((state) => ({
                isRunning: false,
                runningNodeId: null,
                edges: state.edges.map((edge) => ({ ...edge, animated: false })),
            }));
        }
    },

    cancelWorkflow: async () => {
        const { workflow, isRunning, runningNodeId, addToast } = get();

        if (!workflow || (!isRunning && runningNodeId === null)) return;

        try {
            await cancelWorkflowStream(workflow.id);
            addToast('اجرای ورکفلو متوقف شد.', 'info', 3000);
        } catch {
            addToast('خطا در ارسال درخواست توقف رخ داد.', 'error');
        } finally {
            set((state) => ({
                isRunning: false,
                runningNodeId: null,
                edges: state.edges.map((edge) => ({ ...edge, animated: false })),
            }));
        }
    },

    executeSingleNode: async (nodeKey) => {
        const { workflow, saveStatus, save, isRunning, runningNodeId, addToast, addLog } = get();

        if (!workflow || isRunning || runningNodeId) return;

        if (saveStatus !== 'saved') {
            await save();
        }

        const currentIds = get().ids;
        const dbId = currentIds[nodeKey] ?? (Number.isFinite(Number(nodeKey)) ? Number(nodeKey) : null);

        if (!dbId) {
            addToast('شناسه نود جهت اجرا یافت نشد.', 'error');
            return;
        }

        set({ runningNodeId: nodeKey });

        try {
            const res = await runSingleNode(workflow.id, dbId);
            const runData = (res as any)?.run;
            const singleNodeRun = (res as any)?.node_run;

            if (runData && runData.node_runs) {
                set((state) => {
                    const existingMap = new Map((state.lastRun?.node_runs ?? []).map((r) => [r.id, r]));
                    runData.node_runs.forEach((r: NodeRunData) => existingMap.set(r.id, r));

                    return {
                        lastRun: {
                            id: runData.id,
                            workflow_id: workflow.id,
                            status: runData.status,
                            trigger_type: runData.trigger_type,
                            trigger_data: runData.trigger_data,
                            error: runData.error,
                            node_runs: Array.from(existingMap.values()),
                        },
                    };
                });
            } else if (singleNodeRun) {
                set((state) => {
                    const existingNodeRuns = (state.lastRun?.node_runs ?? []).filter(
                        (r) => r.id !== singleNodeRun.id,
                    );

                    return {
                        lastRun: {
                            id: singleNodeRun.workflow_run_id ?? singleNodeRun.id,
                            workflow_id: workflow.id,
                            status: singleNodeRun.status === 'success' ? 'success' : 'failed',
                            trigger_type: 'single_node',
                            trigger_data: {},
                            error: singleNodeRun.error,
                            node_runs: [...existingNodeRuns, singleNodeRun],
                        },
                    };
                });
            }

            if (singleNodeRun?.status === 'failed' && singleNodeRun?.error) {
                addLog({
                    source: 'node',
                    nodeName: nodeLabelByDbId(get().nodes, get().ids, singleNodeRun.node_id),
                    message: singleNodeRun.error,
                });
                addToast(singleNodeRun.error, 'error', 6000);
            }
        } catch (error) {
            if (error instanceof ApiError) {
                const message =
                    error.status === 422
                        ? `Validation error: ${error.firstError() ?? 'Invalid parameters.'}`
                        : error.message || 'Execution failed.';
                addLog({ source: 'run', nodeName: null, message });
                addToast(message, 'error', 6000);
            } else {
                addLog({ source: 'run', nodeName: null, message: 'Server connection error.' });
                addToast('Server connection error.', 'error');
            }
        } finally {
            set({ runningNodeId: null });
        }
    },
});

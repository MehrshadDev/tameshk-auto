import type { LogEntry } from '@canvas/store/types';
import { LOG_LIMIT, logsStorageKey } from '@canvas/store/helpers';
import type { Slice } from './types';

export type LogsSlice = {
    logs: LogEntry[];
    addLog: (entry: Omit<LogEntry, 'id' | 'at'>) => void;
    clearLogs: () => void;
};

export const createLogsSlice: Slice<LogsSlice> = (set, get) => ({
    logs: [],

    addLog: (entry) => {
        const message = entry.message.trim();

        if (!message) return;

        const { workflow, logs } = get();
        const next = [
            { ...entry, message, id: crypto.randomUUID(), at: new Date().toISOString() },
            ...logs,
        ].slice(0, LOG_LIMIT);

        set({ logs: next });

        if (workflow?.id) {
            try {
                localStorage.setItem(logsStorageKey(workflow.id), JSON.stringify(next));
            } catch {}
        }
    },

    clearLogs: () => {
        const { workflow } = get();

        set({ logs: [] });

        if (workflow?.id) {
            localStorage.removeItem(logsStorageKey(workflow.id));
        }
    },
});

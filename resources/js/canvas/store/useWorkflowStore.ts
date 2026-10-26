import { create } from 'zustand';
import { createGraphSlice } from './slices/graphSlice';
import { createNodeSlice } from './slices/nodeSlice';
import { createClipboardSlice } from './slices/clipboardSlice';
import { createUiSlice } from './slices/uiSlice';
import { createExecutionSlice } from './slices/executionSlice';
import { createChatSlice } from './slices/chatSlice';
import { createLogsSlice } from './slices/logsSlice';
import type { WorkflowState } from './state';
import { createPublishSlice } from './slices/publishSlice';
import { createMetaSlice } from './slices/metaSlice';

export type { SaveStatus, Panel, ChatMessage } from './types';

export const useWorkflowStore = create<WorkflowState>()((...args) => ({
    ...createGraphSlice(...args),
    ...createNodeSlice(...args),
    ...createClipboardSlice(...args),
    ...createUiSlice(...args),
    ...createExecutionSlice(...args),
    ...createChatSlice(...args),
    ...createLogsSlice(...args),
    ...createPublishSlice(...args),
    ...createPublishSlice(...args),
    ...createMetaSlice(...args),
}));

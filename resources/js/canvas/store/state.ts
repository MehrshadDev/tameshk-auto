import type { GraphSlice } from './slices/graphSlice';
import type { NodeSlice } from './slices/nodeSlice';
import type { ClipboardSlice } from './slices/clipboardSlice';
import type { UiSlice } from './slices/uiSlice';
import type { ExecutionSlice } from './slices/executionSlice';
import type { ChatSlice } from './slices/chatSlice';
import type { LogsSlice } from './slices/logsSlice';
import type { PublishSlice } from './slices/publishSlice';
import type { MetaSlice } from './slices/metaSlice';

export type WorkflowState = GraphSlice &
    NodeSlice &
    ClipboardSlice &
    UiSlice &
    ExecutionSlice &
    ChatSlice &
    LogsSlice &
    PublishSlice &
    MetaSlice;

import type { Edge } from '@xyflow/react';
import type { FlowNode } from '@canvas/lib/graph';

export type Status = 'loading' | 'ready' | 'error';

export type SaveStatus = 'saved' | 'dirty' | 'saving' | 'error';

export type Panel = { type: 'picker' } | { type: 'node'; id: string } | null;

export type ChatMessage = {
    id: string;
    role: 'user' | 'assistant' | 'error';
    text: string;
};

export type PendingConnection = {
    sourceNodeId: string;
    sourceHandle: string;
} | null;

export type PendingTargetConnection = {
    targetNodeId: string;
    targetHandle: string;
} | null;

export type ConnectingHandle = {
    nodeId: string;
    handleId: string;
} | null;

export type Snapshot = {
    nodes: FlowNode[];
    edges: Edge[];
};

export type ClipboardData = Snapshot | null;

export type LogEntry = {
    id: string;
    at: string;
    source: 'node' | 'run' | 'save' | 'publish';
    nodeName: string | null;
    message: string;
};

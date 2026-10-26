export type NodeCategory = 'trigger' | 'ai' | 'action' | 'logic' | 'transform';

export type NodeDefinition = {
    type: string;
    name: string;
    category: NodeCategory;
    description: string;
    icon: string | null;
    group: string | null;
    inputs: string[];
    outputs: string[];
    configSchema: Record<string, ConfigField>;
    presets?: NodePreset[];
    publishable?: boolean;
    color?: string | null;
};

export type ServerNode = {
    id: number;
    type: string;
    name: string;
    position: { x: number; y: number };
    config: Record<string, unknown>;
};

export type ServerEdge = {
    id: number;
    source: number;
    target: number;
    sourceHandle: string;
    targetHandle: string | null;
};

export type WorkflowSummary = {
    id: number;
    name: string;
    is_active: boolean;
    published_at: string | null;
};

export type TriggerEndpoint = {
    key: string;
    label: string;
    mode: 'test' | 'production';
    url: string;
    hint?: string;
};

export type GraphResponse = {
    workflow: WorkflowSummary;
    nodes: ServerNode[];
    edges: ServerEdge[];
    definitions: NodeDefinition[];
    endpoints: Record<string, TriggerEndpoint[]>;
};

export type GraphPayload = {
    nodes: {
        key: string;
        id: number | null;
        type: string;
        name: string;
        position: { x: number; y: number };
        config: Record<string, unknown>;
    }[];
    edges: {
        source: string;
        target: string;
        sourceHandle: string;
        targetHandle: string | null;
    }[];
};

export type SaveGraphResponse = {
    ids: Record<string, number>;
    published?: boolean;
    unpublished_reason?: string | null;
};

export type PublishResponse = {
    message: string;
    workflow: WorkflowSummary;
};

export type ConfigField = {
    type: string;
    label?: string;
    required?: boolean;
    placeholder?: string;
    help?: string;
    default?: unknown;
    options?: (string | { value: string; label: string })[];
    min?: number;
    max?: number;
    integer?: boolean;
    credential_type?: string;
};

export type CredentialDefinition = {
    key: string;
    name: string;
    description: string;
    icon: string | null;
    schema: Record<string, ConfigField>;
};

export type NodeRunStatus = 'pending' | 'running' | 'success' | 'failed';

export type NodeRunData = {
    id: number;
    node_id: number;
    status: NodeRunStatus;
    input_data: unknown;
    output_data: unknown;
    error: string | null;
    duration_ms: number | null;
    started_at: string;
    finished_at: string;
};

export type WorkflowRunResult = {
    id: number;
    workflow_id: number;
    status: 'running' | 'success' | 'failed' | 'canceled';
    trigger_type: string;
    trigger_data: unknown;
    error: string | null;
    node_runs: NodeRunData[];
};

export type RunWorkflowResponse = {
    run: WorkflowRunResult;
    message?: string;
};

export type Toast = {
    id: string;
    type: 'error' | 'success' | 'info';
    message: string;
    duration?: number;
};

export type NodePreset = {
    key: string;
    name: string;
    description: string;
    config: Record<string, unknown>;
};

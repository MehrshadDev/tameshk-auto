export const Handle = {
    Main: 'main',
    True: 'true',
    False: 'false',
    ChatModel: 'chat_model',
    Memory: 'memory',
    Tool: 'tool',
} as const;

export type HandleValue = typeof Handle[keyof typeof Handle];

export const SPECIAL_HANDLES = [Handle.ChatModel, Handle.Memory, Handle.Tool] as const;

export const FLOW_HANDLES = [Handle.Main, Handle.True, Handle.False] as const;

export const isSpecialHandle = (handle: string): boolean =>
    (SPECIAL_HANDLES as readonly string[]).includes(handle);

export const isFlowHandle = (handle: string): boolean =>
    (FLOW_HANDLES as readonly string[]).includes(handle);

export const Handle = {
    Main: 'main',
    True: 'true',
    False: 'false',
    Case1: 'case_1',
    Case2: 'case_2',
    Case3: 'case_3',
    Default: 'default',
    ChatModel: 'chat_model',
    Memory: 'memory',
    Tool: 'tool',
} as const;

export type HandleValue = typeof Handle[keyof typeof Handle];

export const SPECIAL_HANDLES = [Handle.ChatModel, Handle.Memory, Handle.Tool] as const;

export const FLOW_HANDLES = [
    Handle.Main,
    Handle.True,
    Handle.False,
    Handle.Case1,
    Handle.Case2,
    Handle.Case3,
    Handle.Default,
] as const;

export const isSpecialHandle = (handle: string): boolean =>
    (SPECIAL_HANDLES as readonly string[]).includes(handle);

export const isFlowHandle = (handle: string): boolean =>
    !isSpecialHandle(handle);

import { Handle } from '@canvas/lib/handles';

export const AGENT_NODE_TYPE = 'ai.agent';
export const AGENT_WIDTH = 288;
export const SUPPLY_NODE_WIDTH = 96;
export const SUPPLY_OFFSET_Y = 176;

export const AGENT_SLOTS = [
    { handle: Handle.ChatModel, label: 'Chat Model', required: true, single: true, ratio: 0.18 },
    { handle: Handle.Memory, label: 'Memory', required: false, single: true, ratio: 0.5 },
    { handle: Handle.Tool, label: 'Tool', required: false, single: false, ratio: 0.82 },
] as const;

export const slotRatio = (handle: string): number =>
    AGENT_SLOTS.find((slot) => slot.handle === handle)?.ratio ?? 0.5;

export const isSingleSlot = (handle: string): boolean =>
    AGENT_SLOTS.some((slot) => slot.handle === handle && slot.single);

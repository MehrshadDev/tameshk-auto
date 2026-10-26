const TESTABLE_TRIGGERS = ['trigger.manual', 'trigger.telegram'];

export const isTestableTrigger = (nodeType: string): boolean => TESTABLE_TRIGGERS.includes(nodeType);

export const isTelegramTrigger = (nodeType: string): boolean => nodeType === 'trigger.telegram';

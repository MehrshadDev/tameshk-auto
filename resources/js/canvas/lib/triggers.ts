const TESTABLE_TRIGGERS = ['trigger.manual', 'trigger.telegram', 'trigger.gmail'];

export const isTestableTrigger = (nodeType: string): boolean => TESTABLE_TRIGGERS.includes(nodeType);

export const isTelegramTrigger = (nodeType: string): boolean => nodeType === 'trigger.telegram';

export const isGmailTrigger = (nodeType: string): boolean => nodeType === 'trigger.gmail';

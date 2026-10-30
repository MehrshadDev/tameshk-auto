const TESTABLE_TRIGGERS = [
    'trigger.manual',
    'trigger.schedule',
    'trigger.webhook',
    'trigger.telegram',
    'trigger.gmail',
    'trigger.google_sheets_row_added',
    'trigger.google_sheets_row_updated',
    'trigger.google_sheets_row_added_or_updated',
];

export const isTestableTrigger = (nodeType: string): boolean => TESTABLE_TRIGGERS.includes(nodeType);

export const isScheduleTrigger = (nodeType: string): boolean => nodeType === 'trigger.schedule';

export const isWebhookTrigger = (nodeType: string): boolean => nodeType === 'trigger.webhook';

export const isTelegramTrigger = (nodeType: string): boolean => nodeType === 'trigger.telegram';

export const isGmailTrigger = (nodeType: string): boolean => nodeType === 'trigger.gmail';

export const isSheetsTrigger = (nodeType: string): boolean =>
    nodeType === 'trigger.google_sheets_row_added'
    || nodeType === 'trigger.google_sheets_row_updated'
    || nodeType === 'trigger.google_sheets_row_added_or_updated';

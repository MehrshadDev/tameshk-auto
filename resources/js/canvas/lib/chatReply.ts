const REPLY_KEYS = ['response', 'text', 'output', 'message'];

export const extractReply = (output: unknown): string => {
    if (typeof output === 'string') {
        return output;
    }

    if (output && typeof output === 'object') {
        const record = output as Record<string, unknown>;

        for (const key of REPLY_KEYS) {
            const value = record[key];
            if (typeof value === 'string' && value !== '') {
                return value;
            }
        }

        return JSON.stringify(output, null, 2);
    }

    return '';
};

import type { ConfigField } from '@canvas/types/graph';

const FIRST_LETTER_RTL = /^[^\p{L}]*[\u0590-\u08FF\uFB1D-\uFDFF\uFE70-\uFEFF]/u;

export type SelectOption = { value: string; label: string };

export const textDir = (value: string, fallback = ''): 'rtl' | 'ltr' => {
    const sample = value.trim() !== '' ? value.trim() : fallback.trim();
    if (sample === '') {
        return 'rtl';
    }
    return FIRST_LETTER_RTL.test(sample) ? 'rtl' : 'ltr';
};

export const asString = (value: unknown): string =>
    typeof value === 'string' ? value : value === null || value === undefined ? '' : String(value);

export const normalizeOptions = (options: ConfigField['options'] = []): SelectOption[] =>
    options.map((option) => (typeof option === 'string' ? { value: option, label: option } : option));

const isFieldVisible = (
    field: ConfigField,
    config: Record<string, unknown>,
    schema: Record<string, ConfigField>,
): boolean => {
    if (!field.depends_on) return true;

    for (const [depKey, depValue] of Object.entries(field.depends_on)) {
        const actual = config[depKey] ?? schema[depKey]?.default ?? '';
        const allowed = Array.isArray(depValue) ? depValue : [depValue];
        if (!allowed.includes(String(actual))) {
            return false;
        }
    }

    return true;
};

export const missingRequiredLabels = (
    schema: Record<string, ConfigField>,
    config: Record<string, unknown>,
): string[] =>
    Object.entries(schema)
        .filter(([key, field]) => {
            if (!field.required || field.advanced) {
                return false;
            }

            if (!isFieldVisible(field, config, schema)) {
                return false;
            }

            const value = config[key] ?? field.default;

            return (
                value === null
                || value === undefined
                || (typeof value === 'string' && value.trim() === '')
                || (Array.isArray(value) && value.length === 0)
            );
        })
        .map(([key, field]) => field.label ?? key);

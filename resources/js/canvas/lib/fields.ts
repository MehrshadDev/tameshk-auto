import type { ConfigField } from '@canvas/types/graph';

const RTL = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/;

export type SelectOption = { value: string; label: string };

export const textDir = (value: string, fallback = ''): 'rtl' | 'ltr' =>
    RTL.test(value || fallback) ? 'rtl' : 'ltr';

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

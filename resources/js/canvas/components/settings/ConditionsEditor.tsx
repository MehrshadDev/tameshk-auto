import { Icon } from '@canvas/components/ui/Icon';
import { Select } from '@canvas/components/ui/Select';
import { ConfigFieldInput } from '@canvas/components/settings/ConfigFieldInput';

export type ConditionRule = {
    id: string;
    field: string;
    operator: string;
    value: string;
};

type ConditionsEditorProps = {
    value: unknown;
    onChange: (value: ConditionRule[]) => void;
    incomingData?: unknown;
};

const operators = [
    { value: 'eq', label: 'برابر است با (==)' },
    { value: 'neq', label: 'برابر نیست با (!=)' },
    { value: 'gt', label: 'بزرگ‌تر از (>)' },
    { value: 'gte', label: 'بزرگ‌تر یا مساوی (>=)' },
    { value: 'lt', label: 'کوچک‌تر از (<)' },
    { value: 'lte', label: 'کوچک‌تر یا مساوی (<=)' },
    { value: 'contains', label: 'شامل متن است' },
    { value: 'empty', label: 'خالی است' },
];

const toRules = (val: unknown): ConditionRule[] => {
    if (!Array.isArray(val) || val.length === 0) {
        return [{ id: crypto.randomUUID(), field: '', operator: 'eq', value: '' }];
    }

    return val.map((item) => ({
        id: item.id ?? crypto.randomUUID(),
        field: String(item.field ?? ''),
        operator: String(item.operator ?? 'eq'),
        value: String(item.value ?? ''),
    }));
};

export function ConditionsEditor({ value, onChange, incomingData }: ConditionsEditorProps) {
    const rules = toRules(value);

    const update = (id: string, patch: Partial<ConditionRule>) => {
        const next = rules.map((r) => (r.id === id ? { ...r, ...patch } : r));
        onChange(next);
    };

    const addRule = () => {
        onChange([...rules, { id: crypto.randomUUID(), field: '', operator: 'eq', value: '' }]);
    };

    const removeRule = (id: string) => {
        if (rules.length <= 1) {
            onChange([{ id: crypto.randomUUID(), field: '', operator: 'eq', value: '' }]);
            return;
        }
        onChange(rules.filter((r) => r.id !== id));
    };

    return (
        <div className="space-y-3">
            {rules.map((rule, idx) => (
                <div
                    key={rule.id}
                    className="group relative flex flex-col gap-3 rounded-2xl border border-line-border/90 bg-surface-card p-4 shadow-sm transition-all duration-200 hover:border-line-border hover:shadow-md"
                >
                    <div className="flex items-center justify-between border-b border-line-border/50 pb-2.5">
                        <div className="flex items-center gap-2">
                            <span className="flex size-5 items-center justify-center rounded-full bg-primary/10 text-[11px] font-bold text-primary">
                                {idx + 1}
                            </span>
                            <span className="text-xs font-bold text-content-main">
                                شرط شماره {idx + 1}
                            </span>
                        </div>

                        <button
                            type="button"
                            onClick={() => removeRule(rule.id)}
                            aria-label="حذف شرط"
                            className="flex size-7 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary"
                        >
                            <Icon name="trash" className="size-3.5" />
                        </button>
                    </div>

                    <div className="space-y-3">
                        <div className="space-y-1">
                            <label className="text-[11px] font-semibold text-content-muted">فیلد ورودی</label>
                            <ConfigFieldInput
                                id={`rule-field-${rule.id}`}
                                field={{ type: 'text', placeholder: 'مثلاً: {{ input.status }}' }}
                                value={rule.field}
                                incomingData={incomingData}
                                onChange={(val) => update(rule.id, { field: String(val ?? '') })}
                            />
                        </div>

                        <div className="space-y-1">
                            <label className="text-[11px] font-semibold text-content-muted">عملگر مقایسه</label>
                            <Select
                                id={`rule-op-${rule.id}`}
                                options={operators}
                                value={rule.operator}
                                onChange={(e) => update(rule.id, { operator: e.target.value })}
                            />
                        </div>

                        {rule.operator !== 'empty' && (
                            <div className="space-y-1">
                                <label className="text-[11px] font-semibold text-content-muted">مقدار مقایسه</label>
                                <ConfigFieldInput
                                    id={`rule-val-${rule.id}`}
                                    field={{ type: 'text', placeholder: 'مقدار مورد نظر' }}
                                    value={rule.value}
                                    incomingData={incomingData}
                                    onChange={(val) => update(rule.id, { value: String(val ?? '') })}
                                />
                            </div>
                        )}
                    </div>
                </div>
            ))}

            <button
                type="button"
                onClick={addRule}
                className="flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-line-border bg-surface-card/50 py-3 text-xs font-bold text-content-muted transition hover:border-primary hover:bg-primary/5 hover:text-primary active:scale-[0.99]"
            >
                <Icon name="plus" className="size-4" />
                <span>افزودن شرط جدید</span>
            </button>
        </div>
    );
}

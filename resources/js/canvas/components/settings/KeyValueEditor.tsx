import { useState } from 'react';
import { inputClass } from '@canvas/components/settings/fieldStyles';
import { Icon } from '@canvas/components/ui/Icon';
import { asString } from '@canvas/lib/fields';

type Row = { id: string; key: string; value: string };

type KeyValueEditorProps = {
    value: unknown;
    onChange: (value: Record<string, string>) => void;
};

const toRows = (value: unknown): Row[] =>
    value && typeof value === 'object' && !Array.isArray(value)
        ? Object.entries(value).map(([key, item]) => ({
            id: crypto.randomUUID(),
            key,
            value: asString(item),
        }))
        : [];

const toObject = (rows: Row[]): Record<string, string> =>
    Object.fromEntries(rows.filter((row) => row.key.trim() !== '').map((row) => [row.key.trim(), row.value]));

export function KeyValueEditor({ value, onChange }: KeyValueEditorProps) {
    const [rows, setRows] = useState<Row[]>(() => toRows(value));

    const commit = (next: Row[]) => {
        setRows(next);
        onChange(toObject(next));
    };

    const update = (id: string, patch: Partial<Row>) =>
        commit(rows.map((row) => (row.id === id ? { ...row, ...patch } : row)));

    return (
        <div className="space-y-2">
            {rows.map((row) => (
                <div key={row.id} className="flex items-center gap-2">
                    <input
                        dir="ltr"
                        value={row.key}
                        maxLength={200}
                        placeholder="نام"
                        onChange={(event) => update(row.id, { key: event.target.value })}
                        className={`${inputClass} min-w-0 flex-1`}
                    />
                    <input
                        dir="ltr"
                        value={row.value}
                        maxLength={2000}
                        placeholder="مقدار"
                        onChange={(event) => update(row.id, { value: event.target.value })}
                        className={`${inputClass} min-w-0 flex-1`}
                    />
                    <button
                        type="button"
                        aria-label="حذف"
                        onClick={() => commit(rows.filter((item) => item.id !== row.id))}
                        className="flex size-9 shrink-0 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary"
                    >
                        <Icon name="x" />
                    </button>
                </div>
            ))}

            <button
                type="button"
                onClick={() => setRows([...rows, { id: crypto.randomUUID(), key: '', value: '' }])}
                className="flex items-center gap-1.5 text-xs font-medium text-primary hover:underline"
            >
                <Icon name="plus" className="size-4" />
                افزودن مورد
            </button>
        </div>
    );
}

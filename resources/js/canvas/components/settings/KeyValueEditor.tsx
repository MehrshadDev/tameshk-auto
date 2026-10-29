import { useState } from 'react';
import { inputClass } from '@canvas/components/settings/fieldStyles';
import { Icon } from '@canvas/components/ui/Icon';
import { asString, textDir } from '@canvas/lib/fields';

type Row = { id: string; key: string; value: string };

type KeyValueEditorProps = {
    value: unknown;
    onChange: (value: Record<string, string>) => void;
};

const toRows = (value: unknown): Row[] => {
    if (value && typeof value === 'object' && !Array.isArray(value)) {
        const entries = Object.entries(value);
        if (entries.length > 0) {
            return entries.map(([key, item]) => ({
                id: crypto.randomUUID(),
                key,
                value: asString(item),
            }));
        }
    }

    return [{ id: crypto.randomUUID(), key: '', value: '' }];
};

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

    const removeRow = (id: string) => {
        if (rows.length <= 1) {
            commit([{ id: crypto.randomUUID(), key: '', value: '' }]);
            return;
        }
        commit(rows.filter((item) => item.id !== id));
    };

    const handleDrop = (row: Row, event: React.DragEvent<HTMLInputElement>) => {
        event.preventDefault();
        const textToInsert = event.dataTransfer.getData('text/plain');
        if (!textToInsert) return;

        const target = event.currentTarget;
        const start = target.selectionStart ?? target.value.length;
        const end = target.selectionEnd ?? target.value.length;
        const nextVal = row.value.substring(0, start) + textToInsert + row.value.substring(end);
        update(row.id, { value: nextVal });
    };

    return (
        <div className="space-y-2.5">
            {rows.map((row) => {
                const isExpr = /\{\{.+?\}\}/.test(row.value);

                return (
                    <div key={row.id} className="flex items-center gap-2">
                        <input
                            dir="ltr"
                            value={row.key}
                            maxLength={200}
                            placeholder="نام فیلد (مثلاً name)"
                            onChange={(event) => update(row.id, { key: event.target.value })}
                            className={`${inputClass} min-w-0 flex-1 font-mono text-xs`}
                        />
                        <input
                            dir={isExpr ? 'ltr' : textDir(row.value)}
                            value={row.value}
                            maxLength={2000}
                            placeholder="مقدار یا {{ input.field }}"
                            onChange={(event) => update(row.id, { value: event.target.value })}
                            onDragOver={(e) => {
                                e.preventDefault();
                                e.dataTransfer.dropEffect = 'copy';
                            }}
                            onDrop={(e) => handleDrop(row, e)}
                            className={`${inputClass} min-w-0 flex-1 text-xs ${
                                isExpr ? 'font-mono text-emerald-400 border-emerald-500/40! bg-emerald-500/10!' : ''
                            }`}
                        />
                        <button
                            type="button"
                            aria-label="حذف"
                            onClick={() => removeRow(row.id)}
                            className="flex size-9 shrink-0 items-center justify-center rounded-xl text-content-muted transition hover:bg-primary/10 hover:text-primary"
                        >
                            <Icon name="trash" className="size-4" />
                        </button>
                    </div>
                );
            })}

            <button
                type="button"
                onClick={() => setRows([...rows, { id: crypto.randomUUID(), key: '', value: '' }])}
                className="flex w-full items-center justify-center gap-1.5 rounded-xl border border-dashed border-line-border bg-surface-card/40 py-2 text-xs font-bold text-content-muted transition hover:border-primary hover:bg-primary/5 hover:text-primary"
            >
                <Icon name="plus" className="size-3.5" />
                <span>افزودن فیلد جدید</span>
            </button>
        </div>
    );
}

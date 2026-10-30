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
        const entries = Object.entries(value).filter(([k, v]) => k !== '' || asString(v) !== '');
        if (entries.length > 0) {
            return entries.map(([key, item]) => ({
                id: crypto.randomUUID(),
                key,
                value: asString(item),
            }));
        }
    }

    return [];
};

const toObject = (rows: Row[]): Record<string, string> =>
    Object.fromEntries(
        rows
            .filter((row) => row.key.trim() !== '')
            .map((row) => [row.key.trim(), row.value]),
    );

const inferKeyFromExpression = (expr: string): string => {
    const match = expr.match(/\{\{\s*(.+?)\s*}}/);
    if (!match) return 'field';

    const inner = match[1].trim();
    const parts = inner.split('.');
    const last = parts[parts.length - 1] ?? 'field';

    return last.replace(/\[\d+]/g, '').replace(/[^a-zA-Z0-9_]/g, '') || 'field';
};

export function KeyValueEditor({ value, onChange }: KeyValueEditorProps) {
    const [rows, setRows] = useState<Row[]>(() => toRows(value));
    const [isDragOverZone, setIsDragOverZone] = useState(false);

    const commit = (next: Row[]) => {
        setRows(next);
        onChange(toObject(next));
    };

    const addEmptyRow = () => {
        setRows((prev) => [...prev, { id: crypto.randomUUID(), key: '', value: '' }]);
    };

    const update = (id: string, patch: Partial<Row>) =>
        commit(rows.map((row) => (row.id === id ? { ...row, ...patch } : row)));

    const removeRow = (id: string) => {
        commit(rows.filter((item) => item.id !== id));
    };

    const handleDropOnValue = (row: Row, event: React.DragEvent<HTMLInputElement>) => {
        event.preventDefault();
        event.stopPropagation();
        const textToInsert = event.dataTransfer.getData('text/plain');
        if (!textToInsert) return;

        const target = event.currentTarget;
        const start = target.selectionStart ?? target.value.length;
        const end = target.selectionEnd ?? target.value.length;
        const nextVal = row.value.substring(0, start) + textToInsert + row.value.substring(end);

        const nextKey = row.key.trim() === '' ? inferKeyFromExpression(textToInsert) : row.key;
        update(row.id, { key: nextKey, value: nextVal });
    };

    const handleDropOnKey = (row: Row, event: React.DragEvent<HTMLInputElement>) => {
        event.preventDefault();
        event.stopPropagation();
        const dropped = event.dataTransfer.getData('text/plain');
        if (!dropped) return;

        if (/\{\{.+?}}/.test(dropped)) {
            const inferredKey = inferKeyFromExpression(dropped);
            update(row.id, {
                key: row.key.trim() === '' ? inferredKey : row.key,
                value: row.value.trim() === '' ? dropped : row.value,
            });
        } else {
            update(row.id, { key: dropped });
        }
    };

    const handleZoneDrop = (event: React.DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setIsDragOverZone(false);

        const dropped = event.dataTransfer.getData('text/plain');
        if (!dropped) return;

        const baseKey = inferKeyFromExpression(dropped);
        const existingKeys = new Set(rows.map((r) => r.key.trim()));
        let uniqueKey = baseKey;
        let counter = 1;
        while (existingKeys.has(uniqueKey)) {
            uniqueKey = `${baseKey}_${counter++}`;
        }

        commit([
            ...rows,
            {
                id: crypto.randomUUID(),
                key: uniqueKey,
                value: dropped,
            },
        ]);
    };

    if (rows.length === 0) {
        return (
            <div
                onDragOver={(e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'copy';
                    setIsDragOverZone(true);
                }}
                onDragLeave={() => setIsDragOverZone(false)}
                onDrop={handleZoneDrop}
                className={`flex flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-10 text-center transition ${
                    isDragOverZone
                        ? 'border-primary bg-primary/5'
                        : 'border-line-border bg-surface-card/40 hover:border-content-muted/40'
                }`}
            >
                <p className="text-xs font-medium text-content-muted">
                    فیلدهای ورودی را به اینجا بکشید و رها کنید
                </p>
                <span className="my-1.5 text-[11px] text-content-muted/60">یا</span>
                <button
                    type="button"
                    onClick={addEmptyRow}
                    className="mt-1 inline-flex items-center gap-1.5 rounded-xl bg-primary/10 px-4 py-2 text-xs font-bold text-primary transition hover:bg-primary/20"
                >
                    <Icon name="plus" className="size-3.5" />
                    <span>افزودن فیلد (Add Field)</span>
                </button>
            </div>
        );
    }

    return (
        <div
            onDragOver={(e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
            }}
            onDrop={handleZoneDrop}
            className="space-y-3"
        >
            {rows.map((row, index) => {
                const isExpr = /\{\{.+?}}/.test(row.value);

                return (
                    <div
                        key={row.id}
                        className="group relative rounded-2xl border border-line-border/80 bg-surface-card p-3.5 shadow-2xs transition hover:border-line-border"
                    >
                        <div className="mb-2.5 flex items-center justify-between">
                            <span className="text-[11px] font-bold text-content-muted">
                                فیلد #{index + 1}
                            </span>
                            <button
                                type="button"
                                aria-label="حذف فیلد"
                                onClick={() => removeRow(row.id)}
                                className="flex size-7 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary"
                            >
                                <Icon name="trash" className="size-3.5" />
                            </button>
                        </div>

                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div className="space-y-1">
                                <label className="block text-[11px] font-medium text-content-muted">
                                    نام فیلد (Name)
                                </label>
                                <input
                                    dir="ltr"
                                    value={row.key}
                                    maxLength={200}
                                    placeholder="name"
                                    onChange={(event) => update(row.id, { key: event.target.value })}
                                    onDragOver={(e) => {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        e.dataTransfer.dropEffect = 'copy';
                                    }}
                                    onDrop={(e) => handleDropOnKey(row, e)}
                                    className={`${inputClass} text-left font-mono text-xs`}
                                />
                            </div>

                            <div className="space-y-1">
                                <div className="flex items-center justify-between">
                                    <label className="block text-[11px] font-medium text-content-muted">
                                        مقدار (Value)
                                    </label>
                                    {isExpr && (
                                        <span
                                            dir="ltr"
                                            className="rounded bg-emerald-500/10 px-1.5 py-0.2 font-mono text-[9px] font-bold text-emerald-500"
                                        >
                                            fx
                                        </span>
                                    )}
                                </div>
                                <input
                                    dir={isExpr ? 'ltr' : textDir(row.value, 'rtl')}
                                    value={row.value}
                                    maxLength={2000}
                                    placeholder="مقدار را وارد کنید..."
                                    onChange={(event) => update(row.id, { value: event.target.value })}
                                    onDragOver={(e) => {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        e.dataTransfer.dropEffect = 'copy';
                                    }}
                                    onDrop={(e) => handleDropOnValue(row, e)}
                                    className={`${inputClass} text-xs ${
                                        isExpr
                                            ? 'border-emerald-500/40! bg-emerald-500/10! text-left font-mono text-emerald-400'
                                            : ''
                                    }`}
                                />
                            </div>
                        </div>
                    </div>
                );
            })}

            <button
                type="button"
                onClick={addEmptyRow}
                className="flex w-full items-center justify-center gap-1.5 rounded-xl border border-dashed border-line-border bg-surface-card/40 py-2.5 text-xs font-bold text-content-muted transition hover:border-primary hover:bg-primary/5 hover:text-primary"
            >
                <Icon name="plus" className="size-3.5" />
                <span>افزودن فیلد جدید (Add Field)</span>
            </button>
        </div>
    );
}

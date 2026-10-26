import { useState } from 'react';
import { Icon } from '@canvas/components/ui/Icon';

type DataTreeViewerProps = {
    data: unknown;
    basePath?: string;
};

export function DataTreeViewer({ data, basePath = 'input' }: DataTreeViewerProps) {
    if (data === null || data === undefined) {
        return (
            <div className="flex flex-col items-center justify-center rounded-2xl border border-line-border/80 bg-surface-card/60 p-6 text-center text-xs text-content-muted">
                <Icon name="brackets" className="mb-2 size-6 opacity-40" />
                <span>داده‌ای در ورودی وجود ندارد.</span>
            </div>
        );
    }

    if (typeof data !== 'object') {
        return (
            <div className="flex items-center gap-2 rounded-xl border border-line-border bg-surface-card p-3 font-mono text-xs text-content-main shadow-xs" dir="ltr">
                <span className="font-semibold text-emerald-500">value:</span>
                <span>{String(data)}</span>
            </div>
        );
    }

    return (
        <div className="space-y-1 font-mono text-xs">
            {Object.entries(data as Record<string, unknown>).map(([key, value]) => (
                <TreeNode key={key} keyName={key} value={value} path={`${basePath}.${key}`} depth={0} />
            ))}
        </div>
    );
}

function TreeNode({
                      keyName,
                      value,
                      path,
                      depth,
                  }: {
    keyName: string;
    value: unknown;
    path: string;
    depth: number;
}) {
    const isObject = value !== null && typeof value === 'object';
    const isArray = Array.isArray(value);
    const [isOpen, setIsOpen] = useState(depth < 2);

    const typeLabel = isArray
        ? `array [${value.length}]`
        : isObject
            ? 'object'
            : typeof value;

    const badgeStyle = isArray
        ? 'bg-amber-500/10 text-amber-500 border-amber-500/20'
        : isObject
            ? 'bg-sky-500/10 text-sky-500 border-sky-500/20'
            : typeof value === 'string'
                ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
                : typeof value === 'number'
                    ? 'bg-purple-500/10 text-purple-500 border-purple-500/20'
                    : 'bg-content-muted/10 text-content-muted border-line-border';

    const expression = `{{ ${path} }}`;

    const handleDragStart = (event: React.DragEvent) => {
        event.dataTransfer.setData('text/plain', expression);
        event.dataTransfer.effectAllowed = 'copy';
    };

    return (
        <div className="select-none">
            <div
                draggable
                onDragStart={handleDragStart}
                className="group flex items-center justify-between gap-2.5 rounded-xl border border-transparent px-2.5 py-1.5 transition-all hover:border-line-border hover:bg-surface-raised cursor-grab active:cursor-grabbing"
            >
                <div className="flex min-w-0 flex-1 items-center gap-2">
                    {isObject ? (
                        <button
                            type="button"
                            onClick={() => setIsOpen(!isOpen)}
                            className="flex size-5 shrink-0 items-center justify-center rounded-md text-content-muted transition hover:bg-line-border/60 hover:text-content-main"
                        >
                            <Icon
                                name="chevron-down"
                                className={`size-3.5 transition-transform duration-200 ${isOpen ? '' : '-rotate-90'}`}
                            />
                        </button>
                    ) : (
                        <span className="size-5 shrink-0" />
                    )}

                    <div className="flex shrink-0 items-center gap-1.5 text-content-muted group-hover:text-primary">
                        <Icon name="grip-vertical" className="size-3.5 opacity-50 group-hover:opacity-100" />
                        <span className="font-semibold text-content-main">{keyName}:</span>
                    </div>

                    {!isObject && (
                        <span
                            className={`min-w-0 flex-1 truncate text-left text-[11px] ${
                                typeof value === 'string'
                                    ? 'text-emerald-500 dark:text-emerald-400'
                                    : typeof value === 'number'
                                        ? 'text-purple-500 dark:text-purple-400 font-bold'
                                        : 'text-content-muted'
                            }`}
                            dir="ltr"
                        >
                            {typeof value === 'string' ? `"${value}"` : String(value)}
                        </span>
                    )}
                </div>

                <span
                    className={`shrink-0 rounded-md border px-1.5 py-0.5 text-[9px] font-semibold leading-none uppercase ${badgeStyle}`}
                    dir="ltr"
                >
                    {typeLabel}
                </span>
            </div>

            {isObject && isOpen && (
                <div className="ms-4 mt-1 space-y-1 border-s-2 border-line-border/70 ps-2.5">
                    {Object.entries(value as Record<string, unknown>).map(([k, v]) => (
                        <TreeNode key={k} keyName={k} value={v} path={`${path}.${k}`} depth={depth + 1} />
                    ))}
                </div>
            )}
        </div>
    );
}

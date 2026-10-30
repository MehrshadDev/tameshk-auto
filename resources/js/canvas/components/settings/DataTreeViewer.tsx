import { useMemo, useState } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';

type DataTreeViewerProps = {
    data: unknown;
    basePath?: string;
};

export function DataTreeViewer({ data, basePath = 'input' }: DataTreeViewerProps) {
    const [search, setSearch] = useState('');
    const [viewMode, setViewMode] = useState<'tree' | 'json'>('tree');
    const addToast = useWorkflowStore((state) => state.addToast);

    const isDataEmpty = data === null || data === undefined;

    const copyRawJson = () => {
        if (isDataEmpty) return;
        navigator.clipboard.writeText(JSON.stringify(data, null, 2));
        addToast('داده‌ها به صورت JSON کپی شدند.', 'success', 2000);
    };

    if (isDataEmpty) {
        return (
            <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-line-border/80 bg-surface-card/40 p-8 text-center text-xs text-content-muted">
                <Icon name="brackets" className="mb-2 size-6 opacity-30" />
                <span>داده‌ای برای نمایش وجود ندارد.</span>
            </div>
        );
    }

    if (typeof data !== 'object') {
        return (
            <div dir="ltr" className="flex items-center justify-between rounded-xl border border-line-border bg-surface-card p-3 font-mono text-xs text-content-main shadow-xs">
                <span className="font-semibold text-emerald-500">value:</span>
                <span className="truncate">{String(data)}</span>
            </div>
        );
    }

    return (
        <div dir="ltr" className="w-full space-y-2.5 font-mono text-xs select-none text-left">
            <div className="flex items-center justify-between gap-2 border-b border-line-border/60 pb-2">
                <div className="relative flex-1">
                    <Icon name="search" className="pointer-events-none absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-content-muted" />
                    <input
                        type="text"
                        value={search}
                        placeholder="Search keys..."
                        onChange={(e) => setSearch(e.target.value)}
                        className="w-full rounded-lg border border-line-border bg-surface-card py-1 pl-8 pr-2 text-[11px] text-content-main placeholder:text-content-muted/50 focus:border-primary focus:outline-none"
                    />
                    {search && (
                        <button
                            type="button"
                            onClick={() => setSearch('')}
                            className="absolute right-2 top-1/2 -translate-y-1/2 text-content-muted hover:text-content-main"
                        >
                            <Icon name="x" className="size-3" />
                        </button>
                    )}
                </div>

                <div className="flex items-center gap-1 rounded-lg border border-line-border bg-surface-card p-0.5">
                    <button
                        type="button"
                        onClick={() => setViewMode('tree')}
                        className={`rounded-md px-2 py-0.5 text-[10px] font-semibold transition ${
                            viewMode === 'tree' ? 'bg-primary text-white shadow-xs' : 'text-content-muted hover:text-content-main'
                        }`}
                    >
                        Tree
                    </button>
                    <button
                        type="button"
                        onClick={() => setViewMode('json')}
                        className={`rounded-md px-2 py-0.5 text-[10px] font-semibold transition ${
                            viewMode === 'json' ? 'bg-primary text-white shadow-xs' : 'text-content-muted hover:text-content-main'
                        }`}
                    >
                        JSON
                    </button>
                </div>

                <button
                    type="button"
                    onClick={copyRawJson}
                    title="Copy JSON"
                    className="flex size-7 shrink-0 items-center justify-center rounded-lg border border-line-border bg-surface-card text-content-muted transition hover:border-primary hover:text-primary"
                >
                    <Icon name="copy" className="size-3.5" />
                </button>
            </div>

            {viewMode === 'json' ? (
                <pre className="custom-scrollbar max-h-96 overflow-auto rounded-xl border border-line-border bg-surface-card p-3 font-mono text-[11px] leading-5 text-content-main select-text">
                    {JSON.stringify(data, null, 2)}
                </pre>
            ) : (
                <div className="custom-scrollbar max-h-[32rem] overflow-y-auto space-y-1 pr-1">
                    {Object.entries(data as Record<string, unknown>).map(([key, value]) => (
                        <TreeNode
                            key={key}
                            keyName={key}
                            value={value}
                            path={`${basePath}.${key}`}
                            depth={0}
                            searchTerm={search.trim().toLowerCase()}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

function TreeNode({
                      keyName,
                      value,
                      path,
                      depth,
                      searchTerm,
                  }: {
    keyName: string;
    value: unknown;
    path: string;
    depth: number;
    searchTerm: string;
}) {
    const isObject = value !== null && typeof value === 'object';
    const isArray = Array.isArray(value);
    const [isOpen, setIsOpen] = useState(depth < 2 || searchTerm !== '');
    const addToast = useWorkflowStore((state) => state.addToast);

    const matchesSearch = useMemo(() => {
        if (!searchTerm) return true;
        if (keyName.toLowerCase().includes(searchTerm)) return true;
        if (!isObject && String(value).toLowerCase().includes(searchTerm)) return true;
        if (isObject) {
            return JSON.stringify(value).toLowerCase().includes(searchTerm);
        }
        return false;
    }, [keyName, value, isObject, searchTerm]);

    if (!matchesSearch) {
        return null;
    }

    const typeBadge = isArray
        ? `arr [${value.length}]`
        : isObject
            ? 'obj'
            : typeof value === 'string'
                ? 'str'
                : typeof value === 'number'
                    ? 'num'
                    : typeof value === 'boolean'
                        ? 'bool'
                        : 'null';

    const badgeColor = isArray
        ? 'bg-amber-500/15 text-amber-500 border-amber-500/30'
        : isObject
            ? 'bg-sky-500/15 text-sky-500 border-sky-500/30'
            : typeof value === 'string'
                ? 'bg-emerald-500/15 text-emerald-500 border-emerald-500/30'
                : typeof value === 'number'
                    ? 'bg-purple-500/15 text-purple-500 border-purple-500/30'
                    : 'bg-content-muted/15 text-content-muted border-line-border';

    const expression = `{{ ${path} }}`;

    const inferredFieldType = isArray
        ? 'array'
        : isObject
            ? 'object'
            : typeof value === 'number'
                ? 'number'
                : typeof value === 'boolean'
                    ? 'boolean'
                    : 'string';

    const handleDragStart = (e: React.DragEvent) => {
        e.dataTransfer.setData('text/plain', expression);
        e.dataTransfer.setData('application/x-tameshk-type', inferredFieldType);
        e.dataTransfer.effectAllowed = 'copy';
    };

    const copyExpression = (e: React.MouseEvent) => {
        e.stopPropagation();
        navigator.clipboard.writeText(expression);
        addToast(`عبارت ${expression} کپی شد.`, 'success', 2000);
    };

    return (
        <div className="w-full text-left font-mono">
            <div
                draggable
                onDragStart={handleDragStart}
                className="group flex w-full items-center justify-between gap-2 rounded-lg border border-transparent px-2 py-1 transition hover:border-line-border hover:bg-surface-raised cursor-grab active:cursor-grabbing"
            >
                <div className="flex min-w-0 flex-1 items-center gap-1.5 overflow-hidden">
                    {isObject ? (
                        <button
                            type="button"
                            onClick={() => setIsOpen(!isOpen)}
                            className="flex size-4 shrink-0 items-center justify-center rounded text-content-muted transition hover:bg-line-border hover:text-content-main"
                        >
                            <Icon
                                name="chevron-down"
                                className={`size-3 transition-transform duration-150 ${isOpen ? '' : '-rotate-90'}`}
                            />
                        </button>
                    ) : (
                        <span className="size-4 shrink-0" />
                    )}

                    <span
                        className={`shrink-0 rounded border px-1 py-0.2 text-[8px] font-bold uppercase leading-tight ${badgeColor}`}
                    >
                        {typeBadge}
                    </span>

                    <span className="shrink-0 font-bold text-content-main">
                        {keyName}:
                    </span>

                    {!isObject && (
                        <span
                            dir="auto"
                            className={`min-w-0 flex-1 truncate text-[11px] ${
                                typeof value === 'string'
                                    ? 'text-emerald-500 dark:text-emerald-400'
                                    : typeof value === 'number'
                                        ? 'text-purple-500 dark:text-purple-400 font-bold'
                                        : typeof value === 'boolean'
                                            ? 'text-sky-500 font-bold'
                                            : 'text-content-muted'
                            }`}
                        >
                            {typeof value === 'string' ? `"${value}"` : String(value)}
                        </span>
                    )}
                </div>

                <div className="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                    <button
                        type="button"
                        onClick={copyExpression}
                        title={`Copy ${expression}`}
                        className="flex size-6 items-center justify-center rounded text-content-muted transition hover:bg-primary/10 hover:text-primary"
                    >
                        <Icon name="copy" className="size-3" />
                    </button>
                </div>
            </div>

            {isObject && isOpen && (
                <div className="ml-3.5 space-y-0.5 border-l border-line-border/60 pl-2 mt-0.5">
                    {Object.entries(value as Record<string, unknown>).map(([k, v]) => (
                        <TreeNode
                            key={k}
                            keyName={k}
                            value={v}
                            path={isArray ? `${path}[${k}]` : `${path}.${k}`}
                            depth={depth + 1}
                            searchTerm={searchTerm}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

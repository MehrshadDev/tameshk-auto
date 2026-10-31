import { Panel, useReactFlow, useStoreApi } from '@xyflow/react';
import { useMemo, useState } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import { nodeAppearance } from '@canvas/lib/nodeStyles';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';
import type { NodeDefinition, NodePreset } from '@canvas/types/graph';
import { Handle, SPECIAL_HANDLES, isFlowHandle, isSpecialHandle } from '@canvas/lib/handles';
import { getFolderMeta } from '@canvas/lib/folders';
import { useResizablePanel } from '@canvas/lib/useResizablePanel';

const GRID = 16;
const NODE_WIDTH = 240;
const NODE_HEIGHT = 64;

const snap = (value: number) => Math.round(value / GRID) * GRID;
const browseCategory = (definition: NodeDefinition): string =>
    definition.category === 'trigger' && definition.group ? 'action' : definition.category;

export function NodePicker() {
    const open = useWorkflowStore((state) => state.panel?.type === 'picker');
    const openPicker = useWorkflowStore((state) => state.openPicker);

    return (
        <>
            <Panel position="top-right">
                <button
                    type="button"
                    onClick={openPicker}
                    aria-label="افزودن نود"
                    className="flex size-10 items-center justify-center rounded-xl bg-primary text-white shadow-lg transition hover:bg-primary/90 active:scale-95"
                >
                    <Icon name="plus" />
                </button>
            </Panel>

            {open && <PickerPanel />}
        </>
    );
}

type Entry = { key: string; definition: NodeDefinition; preset?: NodePreset };

const toEntries = (definitions: NodeDefinition[]): Entry[] =>
    definitions.flatMap((definition) =>
        definition.presets && definition.presets.length > 0
            ? definition.presets.map((preset) => ({
                key: `${definition.type}:${preset.key}`,
                definition,
                preset,
            }))
            : [{ key: definition.type, definition }],
    );

function NodeCard({ entry, onSelect }: { entry: Entry; onSelect: (entry: Entry) => void }) {
    const { definition, preset } = entry;
    const isTrigger = definition.category === 'trigger' || definition.type.startsWith('trigger.');
    const { iconName, iconStyle } = nodeAppearance(definition);

    return (
        <button
            type="button"
            onClick={() => onSelect(entry)}
            className="group relative flex w-full items-center gap-3.5 overflow-hidden rounded-2xl border border-line-border/60 bg-surface-card p-3 text-start shadow-xs transition duration-150 hover:-translate-y-px hover:border-primary/40 hover:shadow-lg active:translate-y-0 active:scale-[0.99]"
        >
            <div
                className={`flex size-11 shrink-0 items-center justify-center rounded-xl shadow-inner transition-transform duration-150 group-hover:scale-105 ${iconStyle}`}
            >
                <Icon name={iconName} className="size-6" />
            </div>

            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-1.5">
                    <span className="block truncate text-xs font-bold text-content-main transition-colors group-hover:text-primary">
                        {preset?.name ?? definition.name}
                    </span>
                    {isTrigger && (
                        <span className="shrink-0 text-amber-500">
                            <Icon name="bolt" className="size-3.5" />
                        </span>
                    )}
                </div>
                <p className="mt-1 line-clamp-2 text-[11px] leading-5 text-content-muted">
                    {preset?.description ?? definition.description}
                </p>
            </div>

            <Icon
                name="plus"
                className="size-4 shrink-0 -translate-x-1 text-content-muted opacity-0 transition-all duration-150 group-hover:translate-x-0 group-hover:text-primary group-hover:opacity-100"
            />
        </button>
    );
}

function PickerPanel() {
    const [query, setQuery] = useState('');
    const [navPath, setNavPath] = useState<string[]>([]);
    const [triggersOpen, setTriggersOpen] = useState(true);

    const definitions = useWorkflowStore((state) => state.definitions);
    const nodes = useWorkflowStore((state) => state.nodes);
    const pendingConnection = useWorkflowStore((state) => state.pendingConnection);
    const pendingTargetConnection = useWorkflowStore((state) => state.pendingTargetConnection);
    const addNode = useWorkflowStore((state) => state.addNode);
    const closePanel = useWorkflowStore((state) => state.closePanel);
    const store = useStoreApi();
    const { getViewport } = useReactFlow();
    const { width, isResizing, onPointerDown, reset } = useResizablePanel({
        storageKey: 'tameshk_picker_width',
        min: 320,
        max: 720,
        initial: 384,
    });

    const hasTrigger = useMemo(() => {
        return nodes.some((node) => {
            const def = definitions.find((d) => d.type === node.data.nodeType);
            return def?.category === 'trigger';
        });
    }, [nodes, definitions]);

    const targetHandle = pendingTargetConnection?.targetHandle;
    const sourceHandle = pendingConnection?.sourceHandle;
    const isSlotMode = Boolean(
        (targetHandle && isSpecialHandle(targetHandle)) || (sourceHandle && isSpecialHandle(sourceHandle)),
    );

    const availableDefinitions = useMemo(() => {
        return definitions.filter((def) => {
            if (def.category === 'trigger' && hasTrigger) {
                return false;
            }

            if (targetHandle) {
                if ((SPECIAL_HANDLES as readonly string[]).includes(targetHandle)) {
                    return def.outputs.includes(targetHandle);
                }
                return def.outputs.some(isFlowHandle);
            }

            if (sourceHandle) {
                if ((SPECIAL_HANDLES as readonly string[]).includes(sourceHandle)) {
                    return def.inputs.includes(sourceHandle);
                }
                return def.inputs.includes(Handle.Main);
            }

            return true;
        });
    }, [definitions, hasTrigger, targetHandle, sourceHandle]);

    const isSearching = query.trim().length > 0;

    const searchResults = useMemo(() => {
        if (!isSearching) return [];
        const term = query.trim().toLowerCase();

        return toEntries(availableDefinitions).filter((entry) => {
            const name = entry.preset?.name ?? entry.definition.name;
            const description = entry.preset?.description ?? entry.definition.description;

            return name.toLowerCase().includes(term) || description.toLowerCase().includes(term);
        });
    }, [availableDefinitions, query, isSearching]);

    const { currentActionNodes, currentTriggerNodes, currentSubfolders } = useMemo(() => {
        if (isSearching || isSlotMode || navPath.length === 0) {
            return { currentActionNodes: [], currentTriggerNodes: [], currentSubfolders: [] };
        }

        const categoryKey = navPath[0];
        const expectedGroup = navPath.length > 1 ? navPath.slice(1).join('/') : null;
        const categoryNodes = availableDefinitions.filter((d) => browseCategory(d) === categoryKey);
        const directNodes = categoryNodes.filter((d) => (d.group?.trim() || null) === expectedGroup);

        const subfolderKeys = new Set<string>();

        categoryNodes.forEach((d) => {
            const grp = d.group?.trim();
            if (!grp) return;

            if (expectedGroup === null) {
                subfolderKeys.add(grp.split('/')[0]);
            } else if (grp.startsWith(`${expectedGroup}/`)) {
                subfolderKeys.add(grp.substring(expectedGroup.length + 1).split('/')[0]);
            }
        });

        const splitTriggers = categoryKey !== 'trigger';

        return {
            currentActionNodes: toEntries(
                splitTriggers ? directNodes.filter((d) => d.category !== 'trigger') : directNodes,
            ),
            currentTriggerNodes: toEntries(
                splitTriggers ? directNodes.filter((d) => d.category === 'trigger') : [],
            ),
            currentSubfolders: Array.from(subfolderKeys),
        };
    }, [availableDefinitions, navPath, isSearching, isSlotMode]);

    const rootCategories = useMemo(() => {
        const order = ['trigger', 'ai', 'action', 'transform', 'logic'];
        return order.filter((cat) => {
            if (cat === 'trigger' && hasTrigger) return false;
            return availableDefinitions.some((d) => browseCategory(d) === cat);
        });
    }, [availableDefinitions, hasTrigger]);

    const select = ({ definition, preset }: Entry) => {
        const { width, height } = store.getState();
        const { x, y, zoom } = getViewport();

        addNode(
            definition,
            {
                x: snap((width / 2 - x) / zoom - NODE_WIDTH / 2),
                y: snap((height / 2 - y) / zoom - NODE_HEIGHT / 2),
            },
            preset,
        );
    };

    const handleBack = () => {
        setNavPath((prev) => prev.slice(0, -1));
    };

    const pathKey = navPath.join('/');
    const currentMeta = getFolderMeta(pathKey, navPath[navPath.length - 1] ?? '');

    const headerTitle = useMemo(() => {
        if (isSearching) return 'نتایج جستجو';
        if (targetHandle === 'chat_model') return 'انتخاب مدل چت (Chat Model)';
        if (targetHandle === 'memory') return 'انتخاب حافظه (Memory)';
        if (targetHandle === 'tool') return 'انتخاب ابزار (Tool)';
        if (navPath.length > 0) return currentMeta.title;
        return 'چه چیزی اضافه شود؟';
    }, [isSearching, targetHandle, navPath.length, currentMeta.title]);

    const headerDescription = useMemo(() => {
        if (isSearching) return `${searchResults.length} نود پیدا شد`;
        if (isSlotMode) return 'یک نود سازگار را برای اتصال انتخاب کنید';
        if (navPath.length > 0) return currentMeta.description;
        return 'یک دسته را برای مشاهده نودها انتخاب کنید';
    }, [isSearching, searchResults.length, isSlotMode, navPath.length, currentMeta.description]);

    return (
        <aside
            dir="rtl"
            style={{ width }}
            className={`absolute inset-y-0 right-0 z-50 flex max-w-[calc(100%-1rem)] flex-col border-l border-line-border bg-surface-raised/95 shadow-2xl backdrop-blur-xl animate-in slide-in-from-right duration-200 ${
                isResizing ? 'select-none' : ''
            }`}
        >
            <div
                role="separator"
                aria-orientation="vertical"
                onPointerDown={onPointerDown}
                onDoubleClick={reset}
                className="group/resize absolute inset-y-0 -left-1.5 z-10 w-3 cursor-col-resize touch-none"
            >
                <span
                    className={`absolute inset-y-0 left-1/2 w-0.5 -translate-x-1/2 rounded-full transition-colors duration-150 ${
                        isResizing ? 'bg-primary' : 'bg-transparent group-hover/resize:bg-primary/60'
                    }`}
                />
                <span
                    className={`absolute left-1/2 top-1/2 h-10 w-1 -translate-x-1/2 -translate-y-1/2 rounded-full transition-all duration-150 ${
                        isResizing
                            ? 'bg-primary opacity-100'
                            : 'bg-content-muted/50 opacity-0 group-hover/resize:opacity-100'
                    }`}
                />
            </div>

            <div className="relative flex items-center justify-between gap-3 border-b border-line-border/60 px-5 py-4">
                <div className="pointer-events-none absolute inset-x-0 top-0 h-24 bg-linear-to-b from-primary/[0.06] to-transparent" />

                <div className="relative flex min-w-0 items-center gap-3">
                    {navPath.length > 0 && !isSearching && !isSlotMode && (
                        <button
                            type="button"
                            onClick={handleBack}
                            aria-label="بازگشت"
                            className="flex size-8 shrink-0 items-center justify-center rounded-xl border border-line-border bg-surface-card text-content-muted transition hover:border-primary hover:text-primary"
                        >
                            <Icon name="arrow-right" className="size-4" />
                        </button>
                    )}
                    <div className="min-w-0">
                        <h2 className="truncate text-sm font-bold text-content-main">{headerTitle}</h2>
                        <p className="mt-0.5 truncate text-[11px] text-content-muted">{headerDescription}</p>
                    </div>
                </div>

                <button
                    type="button"
                    onClick={closePanel}
                    aria-label="بستن"
                    className="relative flex size-8 shrink-0 items-center justify-center rounded-xl text-content-muted transition hover:bg-line-border/60 hover:text-content-main"
                >
                    <Icon name="x" className="size-4" />
                </button>
            </div>

            <div className="border-b border-line-border/40 p-4">
                <div className="relative">
                    <Icon
                        name="search"
                        className="pointer-events-none absolute inset-s-3.5 top-1/2 size-4 -translate-y-1/2 text-content-muted"
                    />
                    <input
                        autoFocus
                        type="text"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="جستجوی نودها، مدل‌ها و سرویس‌ها..."
                        className="w-full rounded-2xl border border-line-border bg-field py-2.5 pe-10 ps-10 text-xs font-medium text-content-main shadow-xs transition placeholder:text-content-muted/60 focus:border-primary focus:bg-surface-card focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                    {query !== '' && (
                        <button
                            type="button"
                            onClick={() => setQuery('')}
                            aria-label="پاک کردن جستجو"
                            className="absolute inset-e-2.5 top-1/2 flex size-6 -translate-y-1/2 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main"
                        >
                            <Icon name="x" className="size-3.5" />
                        </button>
                    )}
                </div>
            </div>

            <div className="custom-scrollbar flex-1 overflow-y-auto p-4 space-y-2.5 select-none">
                {isSearching ? (
                    searchResults.length === 0 ? (
                        <div className="py-16 text-center text-xs text-content-muted space-y-2">
                            <Icon name="search" className="mx-auto size-7 opacity-30" />
                            <p>نودی متناسب با جستجوی شما پیدا نشد.</p>
                        </div>
                    ) : (
                        searchResults.map((entry) => (
                            <NodeCard key={entry.key} entry={entry} onSelect={select} />
                        ))
                    )
                ) : isSlotMode ? (
                    availableDefinitions.length === 0 ? (
                        <div className="py-16 text-center text-xs text-content-muted space-y-2">
                            <Icon name="box" className="mx-auto size-7 opacity-30" />
                            <p>نود سازگاری برای این نقطه اتصال تعریف نشده است.</p>
                        </div>
                    ) : (
                        toEntries(availableDefinitions).map((entry) => (
                            <NodeCard key={entry.key} entry={entry} onSelect={select} />
                        ))
                    )
                ) : navPath.length > 0 ? (
                    <>
                        {currentActionNodes.map((entry) => (
                            <NodeCard key={entry.key} entry={entry} onSelect={select} />
                        ))}

                        {currentSubfolders.map((subfolderKey) => {
                            const subfolderPath = `${navPath.join('/')}/${subfolderKey}`;
                            const meta = getFolderMeta(subfolderPath, subfolderKey);

                            return (
                                <button
                                    key={subfolderKey}
                                    type="button"
                                    onClick={() => setNavPath([...navPath, subfolderKey])}
                                    className="group flex w-full items-center justify-between gap-3.5 rounded-2xl border border-line-border/60 bg-surface-card p-3.5 text-start transition duration-150 shadow-xs hover:border-line-border hover:bg-surface-raised hover:shadow-md active:scale-[0.99]"
                                >
                                    <div className="flex items-center gap-3.5 min-w-0">
                                        <div
                                            className={`flex size-10 shrink-0 items-center justify-center rounded-xl transition-transform group-hover:scale-105 ${meta.iconClass}`}
                                        >
                                            <Icon name={meta.icon} className="size-5" />
                                        </div>
                                        <div className="min-w-0">
                            <span className="block truncate text-xs font-bold text-content-main group-hover:text-primary transition-colors">
                                {meta.title}
                            </span>
                                            <p className="truncate text-[11px] text-content-muted mt-0.5 leading-4">
                                                {meta.description}
                                            </p>
                                        </div>
                                    </div>

                                    <Icon
                                        name="chevron-left"
                                        className="size-4 shrink-0 text-content-muted group-hover:text-primary transition-transform group-hover:-translate-x-0.5"
                                    />
                                </button>
                            );
                        })}

                        {currentTriggerNodes.length > 0 && (
                            <div className="pt-2">
                                <button
                                    type="button"
                                    onClick={() => setTriggersOpen((prev) => !prev)}
                                    className="flex w-full items-center justify-between border-b border-line-border/60 px-1 pb-2"
                                >
                                    <span className="flex items-center gap-1.5 text-xs font-bold text-content-main">
                                        شروع‌کننده‌ها ({currentTriggerNodes.length})
                                        <Icon name="bolt" className="size-3.5 text-amber-500" />
                                    </span>
                                    <Icon
                                        name="chevron-down"
                                        className={`size-4 text-content-muted transition-transform duration-200 ${
                                            triggersOpen ? '' : 'rotate-90'
                                        }`}
                                    />
                                </button>

                                {triggersOpen && (
                                    <div className="mt-2.5 space-y-2.5">
                                        {
                                            currentTriggerNodes.map((entry) => (
                                                <NodeCard key={entry.key} entry={entry} onSelect={select} />
                                            ))
                                        }
                                    </div>
                                )}
                            </div>
                        )}

                        {currentActionNodes.length === 0 && currentTriggerNodes.length === 0 && currentSubfolders.length === 0 && (
                            <div className="py-16 text-center text-xs text-content-muted space-y-2">
                                <Icon name="box" className="mx-auto size-7 opacity-30" />
                                <p>هنوز نودی در این بخش تعریف نشده است.</p>
                            </div>
                        )}
                    </>
                ) : (
                    rootCategories.map((catKey) => {
                        const meta = getFolderMeta(catKey, catKey);

                        return (
                            <button
                                key={catKey}
                                type="button"
                                onClick={() => setNavPath([catKey])}
                                className="group flex w-full items-center justify-between gap-3.5 rounded-2xl border border-line-border/60 bg-surface-card p-3.5 text-start transition duration-150 shadow-xs hover:border-line-border hover:bg-surface-raised hover:shadow-md active:scale-[0.99]"
                            >
                                <div className="flex items-center gap-3.5 min-w-0">
                                    <div
                                        className={`flex size-10 shrink-0 items-center justify-center rounded-xl transition-transform group-hover:scale-105 ${meta.iconClass}`}
                                    >
                                        <Icon name={meta.icon} className="size-5" />
                                    </div>
                                    <div className="min-w-0">
                                        <span className="block truncate text-xs font-bold text-content-main group-hover:text-primary transition-colors">
                                            {meta.title}
                                        </span>
                                        <p className="truncate text-[11px] text-content-muted mt-0.5 leading-4">
                                            {meta.description}
                                        </p>
                                    </div>
                                </div>

                                <Icon
                                    name="chevron-left"
                                    className="size-4 shrink-0 text-content-muted group-hover:text-primary transition-transform group-hover:-translate-x-0.5"
                                />
                            </button>
                        );
                    })
                )}
            </div>
        </aside>
    );
}

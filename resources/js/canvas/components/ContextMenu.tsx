import { useEffect, useRef } from 'react';

type ContextMenuProps = {
    x: number;
    y: number;
    hasTargetNode: boolean;
    hasSelectedNodes: boolean;
    hasClipboard: boolean;
    canDuplicate: boolean;
    onAddNode: () => void;
    onAddNote: () => void;
    onCopy: () => void;
    onPaste: () => void;
    onDuplicate: () => void;
    onDelete: () => void;
    onSelectAll: () => void;
    onClose: () => void;
};

export function ContextMenu({
                                x,
                                y,
                                hasTargetNode,
                                hasSelectedNodes,
                                hasClipboard,
                                canDuplicate,
                                onAddNode,
                                onAddNote,
                                onCopy,
                                onPaste,
                                onDuplicate,
                                onDelete,
                                onSelectAll,
                                onClose,
                            }: ContextMenuProps) {
    const menuRef = useRef<HTMLDivElement>(null);
    const adjustedX = Math.min(x, window.innerWidth - 220);
    const adjustedY = Math.min(y, window.innerHeight - 250);

    const showNodeActions = hasTargetNode || hasSelectedNodes;

    useEffect(() => {
        const handleOutsideClick = (event: MouseEvent) => {
            if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
                onClose();
            }
        };

        window.addEventListener('mousedown', handleOutsideClick, true);

        return () => {
            window.removeEventListener('mousedown', handleOutsideClick, true);
        };
    }, [onClose]);

    return (
        <div
            ref={menuRef}
            dir="rtl"
            onClick={(e) => e.stopPropagation()}
            onContextMenu={(e) => e.preventDefault()}
            style={{ top: adjustedY, left: adjustedX }}
            className="fixed z-50 w-52 overflow-hidden rounded-2xl border border-line-border bg-surface-raised p-1.5 shadow-2xl backdrop-blur-md select-none"
        >
            {showNodeActions ? (
                <>
                    <button
                        type="button"
                        onClick={() => {
                            onCopy();
                            onClose();
                        }}
                        className="flex w-full items-center justify-between rounded-xl px-3 py-2 text-start text-xs font-medium text-content-main transition hover:bg-line-border/60 hover:text-primary"
                    >
                        <span>کپی</span>
                        <span className="text-[10px] text-content-muted">Ctrl + C</span>
                    </button>

                    {canDuplicate && (
                        <button
                            type="button"
                            onClick={() => {
                                onDuplicate();
                                onClose();
                            }}
                            className="flex w-full items-center justify-between rounded-xl px-3 py-2 text-start text-xs font-medium text-content-main transition hover:bg-line-border/60 hover:text-primary"
                        >
                            <span>تکثیر</span>
                            <span className="text-[10px] text-content-muted">Ctrl + D</span>
                        </button>
                    )}

                    <button
                        type="button"
                        onClick={() => {
                            onDelete();
                            onClose();
                        }}
                        className="flex w-full items-center justify-between rounded-xl px-3 py-2 text-start text-xs font-medium text-primary transition hover:bg-primary/10"
                    >
                        <span>حذف</span>
                        <span className="text-[10px] text-primary/70">Del</span>
                    </button>

                    {hasClipboard && (
                        <>
                            <div className="my-1 border-t border-line-border/60" />
                            <button
                                type="button"
                                onClick={() => {
                                    onPaste();
                                    onClose();
                                }}
                                className="flex w-full items-center justify-between rounded-xl px-3 py-2 text-start text-xs font-medium text-content-main transition hover:bg-line-border/60 hover:text-primary"
                            >
                                <span>چسباندن</span>
                                <span className="text-[10px] text-content-muted">Ctrl + V</span>
                            </button>
                        </>
                    )}
                </>
            ) : (
                <>
                    <button
                        type="button"
                        onClick={() => {
                            onAddNode();
                            onClose();
                        }}
                        className="flex w-full items-center justify-between rounded-xl px-3 py-2 text-start text-xs font-medium text-content-main transition hover:bg-line-border/60 hover:text-primary"
                    >
                        <span>افزودن نود</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            onAddNote();
                            onClose();
                        }}
                        className="flex w-full items-center justify-between rounded-xl px-3 py-2 text-start text-xs font-medium text-content-main transition hover:bg-line-border/60 hover:text-primary"
                    >
                        <span>افزودن یادداشت</span>
                    </button>

                    {hasClipboard && (
                        <>
                            <div className="my-1 border-t border-line-border/60" />
                            <button
                                type="button"
                                onClick={() => {
                                    onPaste();
                                    onClose();
                                }}
                                className="flex w-full items-center justify-between rounded-xl px-3 py-2 text-start text-xs font-medium text-content-main transition hover:bg-line-border/60 hover:text-primary"
                            >
                                <span>چسباندن</span>
                                <span className="text-[10px] text-content-muted">Ctrl + V</span>
                            </button>
                        </>
                    )}
                </>
            )}

            <div className="my-1 border-t border-line-border/60" />

            <button
                type="button"
                onClick={() => {
                    onSelectAll();
                    onClose();
                }}
                className="flex w-full items-center justify-between rounded-xl px-3 py-2 text-start text-xs font-medium text-content-main transition hover:bg-line-border/60 hover:text-primary"
            >
                <span>انتخاب همه</span>
                <span className="text-[10px] text-content-muted">Ctrl + A</span>
            </button>
        </div>
    );
}

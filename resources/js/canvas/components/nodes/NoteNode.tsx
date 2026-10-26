import { NodeResizer, NodeToolbar, Position, type NodeProps } from '@xyflow/react';
import { useLayoutEffect, useRef, useState } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import { MarkdownRenderer } from '@canvas/components/ui/MarkdownRenderer';
import type { FlowNode } from '@canvas/lib/graph';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';

const RTL_REGEX = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/;

export function NoteNode({ id, data, selected }: NodeProps<FlowNode>) {
    const updateNode = useWorkflowStore((state) => state.updateNode);
    const duplicateSelected = useWorkflowStore((state) => state.duplicateSelected);
    const deleteNode = () => {
        useWorkflowStore.setState((state) => ({
            nodes: state.nodes.filter((item) => item.id !== id),
            edges: state.edges.filter((item) => item.source !== id && item.target !== id),
        }));
    };

    const [isEditing, setIsEditing] = useState(false);
    const content = typeof data.config?.content === 'string' ? data.config.content : '';

    const isRtl = RTL_REGEX.test(content);
    const direction = isRtl ? 'rtl' : 'ltr';

    const containerRef = useRef<HTMLDivElement>(null);
    const [minHeight, setMinHeight] = useState(70);

    useLayoutEffect(() => {
        if (containerRef.current) {
            const currentHeight = containerRef.current.scrollHeight;
            if (currentHeight > 0) {
                setMinHeight(currentHeight);
            }
        }
    }, [content, isEditing]);

    return (
        <div
            ref={containerRef}
            dir={direction}
            onDoubleClick={() => setIsEditing(true)}
            className={`group relative flex h-full min-h-fit w-full min-w-52 flex-col justify-start rounded-2xl border bg-surface-card/95 p-4 shadow-lg backdrop-blur-md transition-all duration-200 ${
                selected ? 'border-primary ring-4 ring-primary/20' : 'border-line-border hover:border-content-muted/40'
            }`}
        >
            <NodeResizer
                isVisible={true}
                minWidth={200}
                minHeight={minHeight}
                lineStyle={{
                    borderColor: 'transparent',
                    borderWidth: 8,
                }}
                handleStyle={{
                    width: 16,
                    height: 16,
                    borderColor: 'transparent',
                    backgroundColor: 'transparent',
                }}
            />

            <NodeToolbar
                position={Position.Top}
                offset={8}
                className="flex items-center gap-1 rounded-xl border border-line-border bg-surface-raised p-1 shadow-xl backdrop-blur-md"
            >
                <button
                    type="button"
                    onClick={(e) => {
                        e.stopPropagation();
                        duplicateSelected();
                    }}
                    title="تکثیر یادداشت"
                    className="flex size-6 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main"
                >
                    <Icon name="copy" className="size-3.5" />
                </button>

                <button
                    type="button"
                    onClick={(e) => {
                        e.stopPropagation();
                        deleteNode();
                    }}
                    title="حذف یادداشت"
                    className="flex size-6 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary"
                >
                    <Icon name="trash" className="size-3.5" />
                </button>
            </NodeToolbar>

            {isEditing ? (
                <textarea
                    autoFocus
                    value={content}
                    dir={direction}
                    rows={Math.max(3, content.split('\n').length)}
                    onChange={(e) =>
                        updateNode(id, {
                            config: { ...data.config, content: e.target.value },
                        })
                    }
                    onBlur={() => setIsEditing(false)}
                    onKeyDown={(e) => {
                        if (e.key === 'Escape') {
                            setIsEditing(false);
                        }
                    }}
                    placeholder={isRtl ? 'متن مارک‌داون را بنویسید...' : 'Write your markdown note...'}
                    className="nodrag nopan w-full resize-none overflow-hidden bg-transparent text-xs leading-6 text-content-main focus:outline-none"
                />
            ) : (
                <div className="w-full">
                    {content.trim() ? (
                        <MarkdownRenderer content={content} />
                    ) : (
                        <p className="text-xs italic text-content-muted">
                            {isRtl ? 'برای ویرایش دو بار کلیک کنید...' : 'Double click to edit me...'}
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}

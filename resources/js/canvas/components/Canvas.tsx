import {
    Background,
    BackgroundVariant,
    ConnectionMode,
    MarkerType,
    MiniMap,
    PanOnScrollMode,
    Position,
    ReactFlow,
    ReactFlowProvider,
    SelectionMode,
    useReactFlow,
    type Connection,
    type ConnectionLineComponentProps,
    type Edge,
    type Node,
} from '@xyflow/react';
import {useEffect, useRef, useState} from 'react';
import {ContextMenu} from '@canvas/components/ContextMenu';
import {WorkflowEdge} from '@canvas/components/edges/WorkflowEdge';
import {NodePicker} from '@canvas/components/NodePicker';
import {NoteNode} from '@canvas/components/nodes/NoteNode';
import {WorkflowNode} from '@canvas/components/nodes/WorkflowNode';
import {Icon} from '@canvas/components/ui/Icon';
import type {FlowNodeData} from '@canvas/lib/graph';
import { nodeColor } from '@canvas/lib/nodeStyles';
import {useColorMode} from '@canvas/lib/useColorMode';
import {useWorkflowStore} from '@canvas/store/useWorkflowStore';
import {Handle, isSpecialHandle} from '@canvas/lib/handles';
import {roundedPolylinePath} from '@canvas/components/edges/WorkflowEdge';
import {isSingleSlot} from '@canvas/lib/agentLayout';
import {Tooltip} from "@canvas/components/ui/Tooltip";

const nodeTypes = {
    workflow: WorkflowNode,
    note: NoteNode,
};

const edgeTypes = {
    workflow: WorkflowEdge,
    smoothstep: WorkflowEdge,
    bezier: WorkflowEdge,
};

const deleteKeys = ['Backspace', 'Delete'];

const isValidConnection = (connection: Connection | Edge) => {
    if (!connection.source || !connection.target || connection.source === connection.target) {
        return false;
    }

    const state = useWorkflowStore.getState();
    const nodeA = state.nodes.find((n) => n.id === connection.source);
    const nodeB = state.nodes.find((n) => n.id === connection.target);

    if (!nodeA || !nodeB) return false;

    const defA = state.definitions.find((d) => d.type === nodeA.data.nodeType);
    const defB = state.definitions.find((d) => d.type === nodeB.data.nodeType);

    if (!defA || !defB) return false;

    const sourceHandle = connection.sourceHandle ?? defA.outputs[0] ?? Handle.Main;
    const targetHandle = connection.targetHandle ?? defB.inputs[0] ?? Handle.Main;

    if (!defA.outputs.includes(sourceHandle) || !defB.inputs.includes(targetHandle)) {
        return false;
    }

    const sourceSpecial = isSpecialHandle(sourceHandle);
    const targetSpecial = isSpecialHandle(targetHandle);

    if (sourceSpecial !== targetSpecial) return false;
    if (sourceSpecial && sourceHandle !== targetHandle) return false;

    if (isSingleSlot(targetHandle)) {
        return !state.edges.some(
            (edge) => edge.target === nodeB.id && edge.targetHandle === targetHandle,
        );
    }

    return true;
};

const defaultEdgeOptions = {
    type: 'workflow',
    style: {strokeWidth: 2},
    interactionWidth: 24,
    markerEnd: {
        type: MarkerType.ArrowClosed,
        width: 10,
        height: 10,
    },
};

function ConnectionLine({fromX, fromY, toX, toY, fromPosition}: ConnectionLineComponentProps) {
    let path = '';

    if (fromPosition === Position.Bottom || fromPosition === Position.Top) {
        const dy = toY - fromY;
        const isDownward = dy >= 0;
        const controlOffset = Math.max(Math.abs(dy) * 0.45, 30);

        const c1x = fromX;
        const c1y = fromPosition === Position.Bottom ? fromY + controlOffset : fromY - controlOffset;

        const c2x = toX;
        const c2y = isDownward ? toY - controlOffset : toY + controlOffset;

        path = `M ${fromX} ${fromY} C ${c1x} ${c1y}, ${c2x} ${c2y}, ${toX} ${toY}`;
    } else {
        const dir = fromPosition === Position.Left ? -1 : 1;
        const dx = (toX - fromX) * dir;
        const isBackward = dx < 10;

        if (isBackward) {
            const isUpward = toY < fromY - 10;
            const exitX = fromX + dir * 36;
            const enterX = toX - dir * 36;
            const laneY = isUpward
                ? Math.min(fromY - 60, toY - 15)
                : Math.max(fromY + 85, toY + 15);

            path = roundedPolylinePath(
                [
                    {x: fromX, y: fromY},
                    {x: exitX, y: fromY},
                    {x: exitX, y: laneY},
                    {x: enterX, y: laneY},
                    {x: enterX, y: toY},
                    {x: toX, y: toY},
                ],
                14,
            );
        } else {
            const controlOffset = Math.max(dx * 0.45, 30);
            const c1x = fromX + dir * controlOffset;
            const c2x = toX - dir * controlOffset;

            path = `M ${fromX} ${fromY} C ${c1x} ${fromY}, ${c2x} ${toY}, ${toX} ${toY}`;
        }
    }

    return (
        <g>
            <path
                fill="none"
                stroke="#e21c48"
                strokeWidth={2.5}
                strokeLinecap="round"
                strokeLinejoin="round"
                d={path}
                markerEnd="url(#tameshk-drag-arrow)"
            />
        </g>
    );
}

function CanvasControls() {
    const {zoomIn, zoomOut, fitView} = useReactFlow();

    return (
        <div
            className="absolute bottom-5 left-5 z-20 flex flex-col items-center gap-1 rounded-2xl border border-line-border bg-surface-raised/90 p-1.5 shadow-2xl backdrop-blur-md">
            <Tooltip label="بزرگ‌نمایی" shortcut="+" side="right">
                <button
                    type="button"
                    onClick={() => void zoomIn({duration: 150})}
                    className="flex size-8 items-center justify-center rounded-xl text-content-muted transition hover:bg-surface-card hover:text-content-main active:scale-95"
                >
                    <Icon name="plus" className="size-4"/>
                </button>
            </Tooltip>

            <Tooltip label="کوچک‌نمایی" shortcut="-" side="right">
                <button
                    type="button"
                    onClick={() => void zoomOut({duration: 150})}
                    className="flex size-8 items-center justify-center rounded-xl text-content-muted transition hover:bg-surface-card hover:text-content-main active:scale-95"
                >
                    <Icon name="minus" className="size-4"/>
                </button>
            </Tooltip>

            <span className="my-0.5 h-px w-5 bg-line-border"/>

            <Tooltip label="تنظیم خودکار تصویر" side="right">
                <button
                    type="button"
                    onClick={() => void fitView({padding: 0.35, duration: 250})}
                    className="flex size-8 items-center justify-center rounded-xl text-content-muted transition hover:bg-surface-card hover:text-content-main active:scale-95"
                >
                    <Icon name="scan" className="size-4"/>
                </button>
            </Tooltip>
        </div>
    );
}

const miniMapColor = (node: Node): string => {
    const { nodeType } = node.data as FlowNodeData;
    if (nodeType === 'sticky_note') {
        return '#f59e0b';
    }
    const definition = useWorkflowStore.getState().definitions.find((item) => item.type === nodeType);

    return nodeColor(definition);
};

function CanvasInner() {
    const colorMode = useColorMode();
    const nodes = useWorkflowStore((state) => state.nodes);
    const edges = useWorkflowStore((state) => state.edges);
    const definitions = useWorkflowStore((state) => state.definitions);
    const clipboard = useWorkflowStore((state) => state.clipboard);
    const onNodesChange = useWorkflowStore((state) => state.onNodesChange);
    const onEdgesChange = useWorkflowStore((state) => state.onEdgesChange);
    const onConnect = useWorkflowStore((state) => state.onConnect);
    const openNode = useWorkflowStore((state) => state.openNode);
    const openPicker = useWorkflowStore((state) => state.openPicker);
    const closePanel = useWorkflowStore((state) => state.closePanel);
    const addNote = useWorkflowStore((state) => state.addNote);
    const selectAll = useWorkflowStore((state) => state.selectAll);
    const copySelected = useWorkflowStore((state) => state.copySelected);
    const paste = useWorkflowStore((state) => state.paste);
    const duplicateSelected = useWorkflowStore((state) => state.duplicateSelected);
    const deleteNode = useWorkflowStore((state) => state.deleteNode);
    const undo = useWorkflowStore((state) => state.undo);
    const redo = useWorkflowStore((state) => state.redo);
    const setConnectingHandle = useWorkflowStore((state) => state.setConnectingHandle);

    const {screenToFlowPosition, fitView, zoomIn, zoomOut} = useReactFlow();

    const [isCanvasReady, setIsCanvasReady] = useState(false);
    const [showMiniMap, setShowMiniMap] = useState(false);
    const miniMapTimeoutRef = useRef<number | null>(null);

    const [contextMenu, setContextMenu] = useState<{
        x: number;
        y: number;
        position: { x: number; y: number };
        targetNodeId?: string;
    } | null>(null);

    const handleContextMenu = (clientX: number, clientY: number, targetNodeId?: string) => {
        setContextMenu({
            x: clientX,
            y: clientY,
            position: screenToFlowPosition({x: clientX, y: clientY}),
            targetNodeId,
        });
    };

    const handleMoveStart = () => {
        if (miniMapTimeoutRef.current) {
            window.clearTimeout(miniMapTimeoutRef.current);
        }
        setShowMiniMap(true);
    };

    const handleMoveEnd = () => {
        miniMapTimeoutRef.current = window.setTimeout(() => {
            setShowMiniMap(false);
        }, 600);
    };

    useEffect(() => {
        if (nodes.length > 0) {
            const timer = window.setTimeout(() => {
                fitView({padding: 0.35, maxZoom: 1, duration: 0});
                setIsCanvasReady(true);
            }, 60);

            return () => window.clearTimeout(timer);
        } else {
            setIsCanvasReady(true);
        }
    }, [fitView, nodes.length]);

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                closePanel();
                setContextMenu(null);
            }

            const active = document.activeElement;
            const isTyping =
                active instanceof HTMLInputElement ||
                active instanceof HTMLTextAreaElement ||
                active?.getAttribute('contenteditable') === 'true';

            const selection = window.getSelection()?.toString() ?? '';
            const isInsideStudio = Boolean(active?.closest('.studio-modal'));

            if (isTyping || (isInsideStudio && selection.length > 0)) {
                return;
            }

            if (event.key === '=' || event.key === '+' || event.code === 'Equal') {
                event.preventDefault();
                void zoomIn({duration: 150});
                return;
            }

            if (event.key === '-' || event.key === '_' || event.code === 'Minus') {
                event.preventDefault();
                void zoomOut({duration: 150});
                return;
            }

            const isCtrl = event.ctrlKey || event.metaKey;

            const isKeyZ = event.code === 'KeyZ' || event.key.toLowerCase() === 'z' || event.key === 'ظ';
            if (isCtrl && isKeyZ) {
                event.preventDefault();
                event.stopPropagation();
                if (event.shiftKey) {
                    redo();
                } else {
                    undo();
                }
                return;
            }

            const isKeyY = event.code === 'KeyY' || event.key.toLowerCase() === 'y' || event.key === 'غ';
            if (isCtrl && isKeyY) {
                event.preventDefault();
                event.stopPropagation();
                redo();
                return;
            }

            const isKeyA = event.code === 'KeyA' || event.key.toLowerCase() === 'a' || event.key === 'ش';
            if (isCtrl && isKeyA) {
                event.preventDefault();
                event.stopPropagation();
                window.getSelection()?.removeAllRanges();
                selectAll();
                return;
            }

            const isKeyC = event.code === 'KeyC' || event.key.toLowerCase() === 'c' || event.key === 'ز';
            if (isCtrl && isKeyC) {
                if (selection.trim().length > 0) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                copySelected();
                return;
            }

            const isKeyV = event.code === 'KeyV' || event.key.toLowerCase() === 'v' || event.key === 'ر';
            if (isCtrl && isKeyV) {
                event.preventDefault();
                event.stopPropagation();
                paste();
                return;
            }

            const isKeyD = event.code === 'KeyD' || event.key.toLowerCase() === 'd' || event.key === 'ی';
            if (isCtrl && isKeyD) {
                event.preventDefault();
                event.stopPropagation();
                duplicateSelected();
                return;
            }
        };

        window.addEventListener('keydown', onKeyDown, true);

        return () => window.removeEventListener('keydown', onKeyDown, true);
    }, [closePanel, selectAll, copySelected, paste, duplicateSelected, undo, redo, zoomIn, zoomOut]);

    const targetNode = contextMenu?.targetNodeId
        ? nodes.find((n) => n.id === contextMenu.targetNodeId)
        : null;

    const hasSelectedNodes = nodes.some((n) => n.selected);

    const isTargetTrigger = targetNode
        ? definitions.find((d) => d.type === targetNode.data.nodeType)?.category === 'trigger'
        : false;

    const selectedHasTrigger = nodes
        .filter((n) => n.selected)
        .some((n) => definitions.find((d) => d.type === n.data.nodeType)?.category === 'trigger');

    const canDuplicate = Boolean(
        (targetNode && !isTargetTrigger) || (hasSelectedNodes && !selectedHasTrigger),
    );

    return (
        <div
            className={`relative h-full w-full transition-opacity duration-200 ${
                isCanvasReady ? 'opacity-100' : 'opacity-0'
            }`}
            style={{touchAction: 'none', overscrollBehavior: 'none'}}
            dir="ltr"
            onContextMenu={(e) => {
                const target = e.target as HTMLElement;
                if (target.closest('.react-flow__nodesselection, .react-flow__selectionpane, .react-flow__selection')) {
                    e.preventDefault();
                    handleContextMenu(e.clientX, e.clientY);
                }
            }}
        >
            <svg style={{position: 'absolute', top: 0, left: 0, width: 0, height: 0, pointerEvents: 'none'}}>
                <defs>
                    <marker
                        id="tameshk-drag-arrow"
                        viewBox="0 0 10 10"
                        refX="6"
                        refY="5"
                        markerWidth="4.5"
                        markerHeight="4.5"
                        orient="auto"
                    >
                        <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="#e21c48"/>
                    </marker>
                </defs>
            </svg>

            <style>{`
                .react-flow.connecting .tameshk-plus-wrapper,
                .react-flow--connecting .tameshk-plus-wrapper {
                    display: none !important;
                }
                .react-flow__edge-path {
                    stroke: var(--color-content-muted);
                    stroke-width: 2px;
                    transition: stroke 0.15s ease, stroke-width 0.15s ease, filter 0.15s ease;
                }
                .react-flow__edge.selected .react-flow__edge-path {
                    stroke: var(--color-primary) !important;
                    stroke-width: 3px !important;
                    filter: drop-shadow(0 0 6px rgba(226, 28, 72, 0.5));
                }
                .react-flow__edge.selected marker path {
                    fill: var(--color-primary) !important;
                }
            `}</style>

            <ReactFlow
                nodes={nodes}
                edges={edges}
                nodeTypes={nodeTypes}
                edgeTypes={edgeTypes}
                defaultEdgeOptions={defaultEdgeOptions}
                connectionMode={ConnectionMode.Strict}
                connectionLineComponent={ConnectionLine}
                onConnectStart={(_, params) => {
                    if (params.nodeId && params.handleId) {
                        setConnectingHandle({nodeId: params.nodeId, handleId: params.handleId});
                    }
                }}
                onConnectEnd={() => setConnectingHandle(null)}
                onNodesChange={onNodesChange}
                onEdgesChange={onEdgesChange}
                onConnect={onConnect}
                onMoveStart={handleMoveStart}
                onMoveEnd={handleMoveEnd}
                onNodeDoubleClick={(_, node) => {
                    if (node.type === 'workflow') {
                        openNode(node.id);
                    }
                }}
                onNodeContextMenu={(event, node) => {
                    event.preventDefault();
                    useWorkflowStore.setState((state) => ({
                        nodes: state.nodes.map((n) => ({
                            ...n,
                            selected: n.id === node.id || (node.selected && n.selected),
                        })),
                    }));
                    handleContextMenu(event.clientX, event.clientY, node.id);
                }}
                onPaneContextMenu={(event) => {
                    event.preventDefault();
                    handleContextMenu(event.clientX, event.clientY);
                }}
                onPaneClick={() => setContextMenu(null)}
                isValidConnection={isValidConnection}
                deleteKeyCode={deleteKeys}
                colorMode={colorMode}
                minZoom={0.2}
                maxZoom={2}
                snapToGrid
                snapGrid={[16, 16]}
                proOptions={{hideAttribution: true}}
                panOnDrag={[1]}
                panOnScroll={true}
                panOnScrollMode={PanOnScrollMode.Free}
                panOnScrollSpeed={0.5}
                preventScrolling={true}
                zoomOnScroll={false}
                zoomOnPinch={true}
                selectionOnDrag={true}
                selectionMode={SelectionMode.Partial}
                panActivationKeyCode="Space"
            >
                <Background
                    variant={BackgroundVariant.Dots}
                    gap={22}
                    size={1.2}
                    color={colorMode === 'dark' ? '#2e2e2e' : '#cbd5e1'}
                />

                <CanvasControls/>

                <div
                    className={`transition-opacity duration-300 ${
                        showMiniMap ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'
                    }`}
                >
                    <MiniMap pannable zoomable position="bottom-right" nodeColor={miniMapColor}/>
                </div>

                <NodePicker/>
            </ReactFlow>

            {contextMenu && (
                <ContextMenu
                    x={contextMenu.x}
                    y={contextMenu.y}
                    hasTargetNode={Boolean(targetNode)}
                    hasSelectedNodes={hasSelectedNodes}
                    hasClipboard={Boolean(clipboard && clipboard.nodes.length > 0)}
                    canDuplicate={canDuplicate}
                    onAddNode={openPicker}
                    onAddNote={() => addNote(contextMenu.position)}
                    onCopy={copySelected}
                    onPaste={() => paste(contextMenu.position)}
                    onDuplicate={duplicateSelected}
                    onDelete={() => {
                        if (targetNode) {
                            deleteNode(targetNode.id);
                        } else {
                            nodes.filter((n) => n.selected).forEach((n) => deleteNode(n.id));
                        }
                    }}
                    onSelectAll={selectAll}
                    onClose={() => setContextMenu(null)}
                />
            )}
        </div>
    );
}

export function Canvas() {
    return (
        <ReactFlowProvider>
            <CanvasInner/>
        </ReactFlowProvider>
    );
}

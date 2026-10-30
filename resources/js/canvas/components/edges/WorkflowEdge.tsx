import { BaseEdge, getBezierPath, Position, type EdgeProps } from '@xyflow/react';

type Point = { x: number; y: number };

export function roundedPolylinePath(points: Point[], radius = 14): string {
    if (points.length < 2) return '';

    let d = `M ${points[0].x} ${points[0].y}`;

    for (let i = 1; i < points.length - 1; i++) {
        const prev = points[i - 1];
        const curr = points[i];
        const next = points[i + 1];

        const inLen = Math.hypot(curr.x - prev.x, curr.y - prev.y);
        const outLen = Math.hypot(next.x - curr.x, next.y - curr.y);

        const r = Math.min(radius, inLen / 2, outLen / 2);

        if (r <= 0) {
            d += ` L ${curr.x} ${curr.y}`;
            continue;
        }

        const inDirX = (curr.x - prev.x) / inLen;
        const inDirY = (curr.y - prev.y) / inLen;
        const outDirX = (next.x - curr.x) / outLen;
        const outDirY = (next.y - curr.y) / outLen;

        const startX = curr.x - inDirX * r;
        const startY = curr.y - inDirY * r;
        const endX = curr.x + outDirX * r;
        const endY = curr.y + outDirY * r;

        d += ` L ${startX} ${startY} Q ${curr.x} ${curr.y} ${endX} ${endY}`;
    }

    const last = points[points.length - 1];
    d += ` L ${last.x} ${last.y}`;

    return d;
}

function getLoopPath(
    sourceX: number,
    sourceY: number,
    targetX: number,
    targetY: number,
    radius = 14,
): { path: string; midX: number; midY: number } {
    const exitOffset = 36;
    const enterOffset = 36;
    const loopDropY = Math.max(sourceY, targetY) + 115;

    const p1x = sourceX + exitOffset;
    const p3x = targetX - enterOffset;

    const points: Point[] = [
        { x: sourceX, y: sourceY },
        { x: p1x, y: sourceY },
        { x: p1x, y: loopDropY },
        { x: p3x, y: loopDropY },
        { x: p3x, y: targetY },
        { x: targetX, y: targetY },
    ];

    return {
        path: roundedPolylinePath(points, radius),
        midX: (p1x + p3x) / 2,
        midY: loopDropY,
    };
}

export function WorkflowEdge({
                                 id,
                                 sourceX,
                                 sourceY,
                                 targetX,
                                 targetY,
                                 sourcePosition = Position.Right,
                                 targetPosition = Position.Left,
                                 style = {},
                                 markerEnd,
                                 interactionWidth = 24,
                             }: EdgeProps) {
    if (
        (sourcePosition === Position.Top && targetPosition === Position.Bottom) ||
        (sourcePosition === Position.Bottom && targetPosition === Position.Top)
    ) {
        const [verticalPath] = getBezierPath({
            sourceX,
            sourceY,
            sourcePosition,
            targetX,
            targetY,
            targetPosition,
            curvature: 0.4,
        });

        return (
            <BaseEdge
                id={id}
                path={verticalPath}
                style={{ ...style, strokeDasharray: '6 5', animation: 'none' }}
                interactionWidth={interactionWidth}
            />
        );
    }

    if (targetPosition === Position.Bottom || sourcePosition === Position.Bottom) {
        const [bottomPath] = getBezierPath({
            sourceX,
            sourceY,
            sourcePosition,
            targetX,
            targetY,
            targetPosition,
            curvature: 0.5,
        });

        return (
            <BaseEdge
                id={id}
                path={bottomPath}
                markerEnd={markerEnd}
                style={style}
                interactionWidth={interactionWidth}
            />
        );
    }

    const isBackward = sourceX >= targetX - 20;

    if (isBackward) {
        const { path, midX, midY } = getLoopPath(sourceX, sourceY, targetX, targetY, 14);

        return (
            <g className="react-flow__edge-group">
                <BaseEdge
                    id={id}
                    path={path}
                    markerEnd={markerEnd}
                    style={style}
                    interactionWidth={interactionWidth}
                />
                <path
                    d={`M ${midX + 4} ${midY - 4} L ${midX - 3} ${midY} L ${midX + 4} ${midY + 4} Z`}
                    className="fill-content-muted pointer-events-none"
                />
            </g>
        );
    }

    if (Math.abs(targetY - sourceY) <= 6) {
        return (
            <BaseEdge
                id={id}
                path={`M ${sourceX} ${targetY} L ${targetX} ${targetY}`}
                markerEnd={markerEnd}
                style={style}
                interactionWidth={interactionWidth}
            />
        );
    }

    const [edgePath] = getBezierPath({
        sourceX,
        sourceY,
        sourcePosition: Position.Right,
        targetX,
        targetY,
        targetPosition: Position.Left,
        curvature: 0.45,
    });

    return (
        <BaseEdge
            id={id}
            path={edgePath}
            markerEnd={markerEnd}
            style={style}
            interactionWidth={interactionWidth}
        />
    );
}

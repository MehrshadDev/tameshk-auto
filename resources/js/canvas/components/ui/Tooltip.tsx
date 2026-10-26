import { cloneElement, useEffect, useRef, useState, type ReactElement } from 'react';
import { createPortal } from 'react-dom';

type Side = 'top' | 'bottom' | 'left' | 'right';

type TooltipProps = {
    label: string;
    side?: Side;
    shortcut?: string;
    delay?: number;
    children: ReactElement<any>;
};

const GAP = 10;
const EDGE = 130;

const placement = (rect: DOMRect, side: Side) => {
    const centerX = Math.min(Math.max(rect.left + rect.width / 2, EDGE), window.innerWidth - EDGE);
    const centerY = rect.top + rect.height / 2;

    switch (side) {
        case 'bottom':
            return { left: centerX, top: rect.bottom + GAP, transform: 'translate(-50%, 0)' };
        case 'left':
            return { left: rect.left - GAP, top: centerY, transform: 'translate(-100%, -50%)' };
        case 'right':
            return { left: rect.right + GAP, top: centerY, transform: 'translate(0, -50%)' };
        default:
            return { left: centerX, top: rect.top - GAP, transform: 'translate(-50%, -100%)' };
    }
};

export function Tooltip({ label, side = 'top', shortcut, delay = 300, children }: TooltipProps) {
    const [rect, setRect] = useState<DOMRect | null>(null);
    const timer = useRef<number | null>(null);

    const clear = () => {
        if (timer.current !== null) {
            window.clearTimeout(timer.current);
            timer.current = null;
        }
    };

    const show = (element: HTMLElement) => {
        clear();
        timer.current = window.setTimeout(() => setRect(element.getBoundingClientRect()), delay);
    };

    const hide = () => {
        clear();
        setRect(null);
    };

    useEffect(() => clear, []);

    const original = children.props;

    const trigger = cloneElement(children, {
        onMouseEnter: (event: React.MouseEvent<HTMLElement>) => {
            original.onMouseEnter?.(event);
            show(event.currentTarget);
        },
        onMouseLeave: (event: React.MouseEvent<HTMLElement>) => {
            original.onMouseLeave?.(event);
            hide();
        },
        onMouseDown: (event: React.MouseEvent<HTMLElement>) => {
            original.onMouseDown?.(event);
            hide();
        },
        onFocus: (event: React.FocusEvent<HTMLElement>) => {
            original.onFocus?.(event);
            show(event.currentTarget);
        },
        onBlur: (event: React.FocusEvent<HTMLElement>) => {
            original.onBlur?.(event);
            hide();
        },
    });

    return (
        <>
            {trigger}
            {rect &&
                createPortal(
                    <div
                        dir="rtl"
                        role="tooltip"
                        style={placement(rect, side)}
                        className="pointer-events-none fixed z-100 flex max-w-60 items-center gap-2 rounded-xl border border-line-border bg-surface-raised/95 px-3 py-1.5 text-[11px] font-semibold leading-5 text-content-main shadow-2xl backdrop-blur-md animate-in fade-in zoom-in-95 duration-100"
                    >
                        <span>{label}</span>
                        {shortcut && (
                            <kbd
                                dir="ltr"
                                className="rounded-md border border-line-border bg-surface-card px-1.5 py-px font-mono text-[10px] font-medium text-content-muted"
                            >
                                {shortcut}
                            </kbd>
                        )}
                    </div>,
                    document.body,
                )}
        </>
    );
}

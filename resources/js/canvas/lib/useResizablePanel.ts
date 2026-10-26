import { useCallback, useEffect, useRef, useState, type PointerEvent as ReactPointerEvent } from 'react';

type Options = {
    storageKey: string;
    min: number;
    max: number;
    initial: number;
};

const readWidth = ({ storageKey, min, max, initial }: Options): number => {
    const saved = Number(localStorage.getItem(storageKey));

    return Number.isFinite(saved) && saved >= min && saved <= max ? saved : initial;
};

export function useResizablePanel(options: Options) {
    const { storageKey, min, max, initial } = options;

    const [width, setWidth] = useState(() => readWidth(options));
    const [isResizing, setIsResizing] = useState(false);
    const origin = useRef({ x: 0, width: 0 });
    const latest = useRef(width);

    const onPointerDown = useCallback((event: ReactPointerEvent) => {
        if (event.button !== 0) return;

        event.preventDefault();
        origin.current = { x: event.clientX, width: latest.current };
        setIsResizing(true);
    }, []);

    const reset = useCallback(() => {
        latest.current = initial;
        setWidth(initial);
        localStorage.setItem(storageKey, String(initial));
    }, [initial, storageKey]);

    useEffect(() => {
        if (!isResizing) return;

        const onMove = (event: PointerEvent) => {
            const next = origin.current.width + (origin.current.x - event.clientX);
            const clamped = Math.min(max, Math.max(min, next));

            latest.current = clamped;
            setWidth(clamped);
        };

        const onUp = () => {
            setIsResizing(false);
            localStorage.setItem(storageKey, String(latest.current));
        };

        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
        window.addEventListener('pointermove', onMove);
        window.addEventListener('pointerup', onUp);
        window.addEventListener('pointercancel', onUp);

        return () => {
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
            window.removeEventListener('pointercancel', onUp);
        };
    }, [isResizing, min, max, storageKey]);

    return { width, isResizing, onPointerDown, reset };
}

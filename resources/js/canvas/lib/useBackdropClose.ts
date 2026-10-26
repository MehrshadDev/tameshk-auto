import { useRef, type MouseEvent } from 'react';

export function useBackdropClose(onClose: () => void) {
    const pressedOnBackdrop = useRef(false);

    return {
        onMouseDown: (event: MouseEvent<HTMLElement>) => {
            pressedOnBackdrop.current = event.button === 0 && event.target === event.currentTarget;
        },
        onClick: (event: MouseEvent<HTMLElement>) => {
            const shouldClose = pressedOnBackdrop.current && event.target === event.currentTarget;
            pressedOnBackdrop.current = false;

            if (shouldClose) {
                onClose();
            }
        },
    };
}

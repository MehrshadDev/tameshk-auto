import { type ReactNode, useEffect } from 'react';
import { useBackdropClose } from '@canvas/lib/useBackdropClose';

type ModalProps = {
    isOpen: boolean;
    onClose: () => void;
    children: ReactNode;
    maxWidth?: string;
};

export function Modal({ isOpen, onClose, children, maxWidth = 'max-w-md' }: ModalProps) {
    const backdrop = useBackdropClose(onClose);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [isOpen, onClose]);

    if (!isOpen) {
        return null;
    }

    return (
        <div
            {...backdrop}
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
        >
            <div
                dir="rtl"
                className={`w-full ${maxWidth} max-h-[calc(100vh-2rem)] overflow-y-auto overflow-x-hidden custom-scrollbar rounded-2xl border border-line-border bg-surface-raised shadow-2xl transition-all`}
            >
                {children}
            </div>
        </div>
    );
}

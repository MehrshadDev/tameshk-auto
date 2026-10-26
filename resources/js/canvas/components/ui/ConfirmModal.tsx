import {Icon} from '@canvas/components/ui/Icon';
import {Modal} from '@canvas/components/ui/Modal';

type ConfirmModalProps = {
    isOpen: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    isLoading?: boolean;
    onConfirm: () => void;
    onCancel: () => void;
};

export function ConfirmModal({
                                 isOpen,
                                 title,
                                 message,
                                 confirmLabel = 'حذف',
                                 cancelLabel = 'انصراف',
                                 isLoading = false,
                                 onConfirm,
                                 onCancel,
                             }: ConfirmModalProps) {
    return (
        <Modal isOpen={isOpen} onClose={isLoading ? () => {
        } : onCancel} maxWidth="max-w-sm">
            <div className="p-6">
                <div className="mb-4 flex items-center gap-3">
                    <div
                        className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <Icon name="trash" className="size-6"/>
                    </div>
                    <h3 className="text-base font-bold text-content-main">{title}</h3>
                </div>

                <p className="mb-6 text-xs leading-6 text-content-muted">{message}</p>

                <div className="flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        onClick={onCancel}
                        disabled={isLoading}
                        className="rounded-xl border border-line-border px-4 py-2.5 text-xs font-medium text-content-main transition hover:bg-line-border/60 disabled:opacity-50"
                    >
                        {cancelLabel}
                    </button>

                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={isLoading}
                        className="rounded-xl bg-primary px-5 py-2.5 text-xs font-medium text-white transition hover:bg-primary/90 disabled:opacity-50"
                    >
                        {isLoading ? 'در حال حذف...' : confirmLabel}
                    </button>
                </div>
            </div>
        </Modal>
    );
}

import { useState } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import { Modal } from '@canvas/components/ui/Modal';
import { bypassSaveGuard } from '@canvas/lib/useSaveGuard';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';

type UnsavedChangesModalProps = {
    backUrl: string;
};

export function UnsavedChangesModal({ backUrl }: UnsavedChangesModalProps) {
    const isOpen = useWorkflowStore((state) => state.exitPromptOpen);
    const closePrompt = useWorkflowStore((state) => state.closeExitPrompt);
    const save = useWorkflowStore((state) => state.save);

    const [isSaving, setIsSaving] = useState(false);

    const handleDiscard = () => {
        bypassSaveGuard();
        closePrompt();
        window.location.href = backUrl;
    };

    const handleSaveAndExit = async () => {
        setIsSaving(true);
        try {
            await save();
            const currentStatus = useWorkflowStore.getState().saveStatus;
            if (currentStatus === 'saved') {
                bypassSaveGuard();
                closePrompt();
                window.location.href = backUrl;
            }
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <Modal isOpen={isOpen} onClose={closePrompt} maxWidth="max-w-md">
            <div className="p-6">
                <div className="flex items-start justify-between mb-4">
                    <div className="flex items-center gap-3">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500">
                            <Icon name="alert-triangle" className="size-6" />
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-content-main">تغییرات ذخیره‌نشده</h3>
                            <p className="text-xs text-content-muted mt-0.5">ورکفلو دارای تغییرات ذخیره‌نشده است</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={closePrompt}
                        aria-label="ادامه ویرایش"
                        className="flex size-8 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main"
                    >
                        <Icon name="x" className="size-4" />
                    </button>
                </div>

                <p className="text-xs leading-6 text-content-muted mb-6">
                    شما در این ورکفلو تغییراتی ایجاد کرده‌اید که هنوز ذخیره نشده‌اند. مایلید تغییرات را ذخیره کنید یا بدون ذخیره خارج شوید؟
                </p>

                <div className="flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5">
                    <button
                        type="button"
                        onClick={handleDiscard}
                        disabled={isSaving}
                        className="w-full sm:w-auto rounded-xl border border-line-border px-4 py-2.5 text-xs font-medium text-content-main transition hover:bg-line-border/60 disabled:opacity-50"
                    >
                        خروج بدون ذخیره
                    </button>

                    <button
                        type="button"
                        onClick={() => void handleSaveAndExit()}
                        disabled={isSaving}
                        className="w-full sm:w-auto rounded-xl bg-primary px-5 py-2.5 text-xs font-medium text-white transition hover:bg-primary/90 disabled:opacity-50"
                    >
                        {isSaving ? 'در حال ذخیره...' : 'ذخیره و خروج'}
                    </button>
                </div>
            </div>
        </Modal>
    );
}

import { Icon } from '@canvas/components/ui/Icon';
import { useWorkflowStore, type SaveStatus } from '@canvas/store/useWorkflowStore';
import { Tooltip } from "./ui/Tooltip";

const labels: Record<SaveStatus, string> = {
    saved: 'ذخیره شد',
    dirty: 'تغییرات ذخیره‌نشده',
    saving: 'در حال ذخیره...',
    error: 'خطا در ذخیره',
};

const dots: Record<SaveStatus, string> = {
    saved: 'bg-emerald-500',
    dirty: 'bg-amber-500',
    saving: 'animate-pulse bg-sky-500',
    error: 'bg-primary',
};

export function SaveStatusLabel() {
    const status = useWorkflowStore((state) => state.saveStatus);

    return (
        <span className="flex items-center gap-1.5 text-[11px] leading-none text-content-muted">
            <span className={`size-1.5 rounded-full ${dots[status]}`} />
            {labels[status]}
        </span>
    );
}

export function SaveButton() {
    const status = useWorkflowStore((state) => state.saveStatus);
    const save = useWorkflowStore((state) => state.save);

    const canSave = status === 'dirty' || status === 'error';
    const isSaving = status === 'saving';

    return (
        <Tooltip label="ذخیره تغییرات" shortcut="Ctrl + S" side="bottom">
            <button
                type="button"
                disabled={!canSave}
                onClick={() => void save()}
                className="flex h-9 items-center gap-2 rounded-xl border border-line-border bg-surface-card px-3.5 text-xs font-semibold text-content-main transition hover:bg-line-border/60 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <Icon name={isSaving ? 'loader' : 'device-floppy'} className={`size-4 ${isSaving ? 'animate-spin' : ''}`} />
                <span className="hidden sm:inline">ذخیره</span>
            </button>
        </Tooltip>
    );
}


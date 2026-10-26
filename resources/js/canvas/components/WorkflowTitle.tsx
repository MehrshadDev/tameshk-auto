import { useRef, useState } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import { Tooltip } from '@canvas/components/ui/Tooltip';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';

export function WorkflowTitle() {
    const name = useWorkflowStore((state) => state.workflow?.name ?? '');
    const isRenaming = useWorkflowStore((state) => state.isRenaming);
    const rename = useWorkflowStore((state) => state.rename);

    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState('');
    const cancelled = useRef(false);

    const startEditing = () => {
        cancelled.current = false;
        setDraft(name);
        setEditing(true);
    };

    const finish = async () => {
        setEditing(false);

        if (cancelled.current) {
            cancelled.current = false;
            return;
        }

        const next = draft.trim();

        if (next === '' || next === name) return;

        await rename(next);
    };

    if (editing) {
        return (
            <input
                autoFocus
                dir="auto"
                value={draft}
                maxLength={100}
                autoComplete="off"
                aria-label="نام ورکفلو"
                onFocus={(event) => event.currentTarget.select()}
                onChange={(event) => setDraft(event.target.value)}
                onBlur={() => void finish()}
                onKeyDown={(event) => {
                    if (event.key === 'Enter' && !event.nativeEvent.isComposing) {
                        event.currentTarget.blur();
                    }

                    if (event.key === 'Escape') {
                        event.stopPropagation();
                        cancelled.current = true;
                        event.currentTarget.blur();
                    }
                }}
                className="h-9 w-full max-w-sm rounded-lg border border-primary bg-surface-card px-3 text-sm font-bold text-content-main shadow-xs focus:outline-none focus:ring-2 focus:ring-primary/30"
            />
        );
    }

    return (
        <Tooltip label="برای تغییر نام کلیک کنید" side="bottom">
            <button
                type="button"
                onClick={startEditing}
                disabled={isRenaming}
                className="group/title flex h-9 max-w-sm items-center gap-2 rounded-lg px-3 text-start transition hover:bg-line-border/60 disabled:opacity-60"
            >
                <span dir="auto" className="min-w-0 truncate text-sm font-bold text-content-main">
                    {name}
                </span>
                <Icon
                    name={isRenaming ? 'loader' : 'pencil'}
                    className={`size-3.5 shrink-0 text-content-muted opacity-0 transition group-hover/title:opacity-100 ${
                        isRenaming ? 'animate-spin opacity-100' : ''
                    }`}
                />
            </button>
        </Tooltip>
    );
}

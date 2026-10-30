import { Icon } from '@canvas/components/ui/Icon';
import type { NodeRunStatus } from '@canvas/types/graph';

export const HANDLE_RESET = 'relative! left-auto! right-auto! top-auto! bottom-auto! transform-none!';

export const DOT_HANDLE = `${HANDLE_RESET} pointer-events-auto size-3.5! rounded-full! border-2! border-surface-card! bg-content-muted/70! shadow-sm ring-1 ring-line-border transition-all duration-150 cursor-crosshair hover:scale-125 hover:bg-primary! hover:ring-primary/40`;

export const DIAMOND_HANDLE = `${HANDLE_RESET} pointer-events-auto size-6! rounded-none! border-0! bg-transparent! cursor-pointer flex items-center justify-center`;

const BADGE_BASE =
    'absolute -top-1.5 -right-1.5 z-20 flex h-5 min-w-5 items-center justify-center gap-0.5 rounded-full px-1 text-white shadow-md ring-2 ring-surface-card';

export type NodeBadgeProps = {
    disabled: boolean;
    executing: boolean;
    missing: string[];
    status?: NodeRunStatus;
    successCount: number;
};

export const handleTop = (index: number, total: number) => {
    if (total <= 1) {
        return '50%';
    }
    if (total === 2) {
        return index === 0 ? '30%' : '70%';
    }
    return `${((index + 1) / (total + 1)) * 100}%`;
};

export function SidePlus({ onClick, labeled = false }: { onClick: () => void; labeled?: boolean }) {
    return (
        <div
            onClick={(e) => {
                e.stopPropagation();
                onClick();
            }}
            className="tameshk-plus-wrapper group/plus absolute inset-s-full top-1/2 flex -translate-y-1/2 cursor-pointer items-center pointer-events-auto"
        >
            <span
                className={`pointer-events-none h-px bg-linear-to-r from-content-muted/60 to-content-muted/20 transition-all duration-150 group-hover/plus:from-primary group-hover/plus:to-primary/40 ${
                    labeled ? 'w-12 group-hover/plus:w-13' : 'w-5 group-hover/plus:w-7'
                }`}
            />
            <span className="flex size-6 shrink-0 items-center justify-center rounded-full border border-line-border bg-surface-raised text-content-muted shadow-md transition-all duration-150 group-hover/plus:scale-110 group-hover/plus:border-primary group-hover/plus:bg-primary group-hover/plus:text-white group-hover/plus:shadow-lg group-hover/plus:shadow-primary/30">
                <Icon name="plus" className="pointer-events-none size-3.5" />
            </span>
        </div>
    );
}

export function NodeBadge({ disabled, executing, missing, status, successCount }: NodeBadgeProps) {
    if (disabled) {
        return null;
    }

    if (executing) {
        return (
            <span className={`${BADGE_BASE} bg-amber-500`}>
                <Icon name="loader" className="size-3 animate-spin" />
            </span>
        );
    }

    if (status === 'failed') {
        return (
            <span className={`${BADGE_BASE} bg-primary`}>
                <Icon name="x" className="size-3" />
            </span>
        );
    }

    if (status === 'success') {
        return (
            <span className={`${BADGE_BASE} bg-emerald-500`}>
                <Icon name="check" className="size-3" />
                {successCount > 1 && (
                    <span className="font-mono text-[9px] font-bold leading-none" dir="ltr">
                        {successCount}
                    </span>
                )}
            </span>
        );
    }

    if (missing.length > 0) {
        return (
            <span title={`فیلدهای ناقص: ${missing.join('، ')}`} className={`${BADGE_BASE} bg-amber-500`}>
                <Icon name="alert-triangle" className="size-3" />
            </span>
        );
    }

    return null;
}

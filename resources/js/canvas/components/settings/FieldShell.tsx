import type { ReactNode } from 'react';

type FieldShellProps = {
    id?: string;
    label: string;
    required?: boolean;
    help?: string;
    children: ReactNode;
};

export function FieldShell({ label, required, help, children }: FieldShellProps) {
    return (
        <div className="space-y-2">
            <span className="flex items-center gap-1 text-xs font-semibold text-content-main select-none w-fit cursor-default">
                {label}
                {required && <span className="text-primary">*</span>}
            </span>

            {children}

            {help && <p className="text-[11px] leading-5 text-content-muted">{help}</p>}
        </div>
    );
}

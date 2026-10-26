import type { ReactNode } from 'react';

type FieldShellProps = {
    id: string;
    label: string;
    required?: boolean;
    help?: string;
    children: ReactNode;
};

export function FieldShell({ id, label, required, help, children }: FieldShellProps) {
    return (
        <div>
            <label htmlFor={id} className="mb-1.5 flex items-center gap-1 text-xs font-medium text-content-main">
                {label}
                {required && <span className="text-primary">*</span>}
            </label>

            {children}

            {help && <p className="mt-1.5 text-xs leading-5 text-content-muted">{help}</p>}
        </div>
    );
}

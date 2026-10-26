import { useEffect, useMemo, useRef, useState } from 'react';
import { Icon } from '@canvas/components/ui/Icon';
import { normalizeOptions } from '@canvas/lib/fields';
import type { ConfigField } from '@canvas/types/graph';

type MultiSelectFieldProps = {
    field: ConfigField;
    value: unknown;
    onChange: (value: string[]) => void;
};

export function MultiSelectField({ field, value, onChange }: MultiSelectFieldProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [openUpward, setOpenUpward] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    const options = useMemo(() => normalizeOptions(field.options), [field.options]);
    const selected = Array.isArray(value)
        ? value.filter((item): item is string => typeof item === 'string')
        : [];

    const selectedOptions = options.filter((option) => selected.includes(option.value));

    const toggleOpen = () => {
        if (!isOpen && containerRef.current) {
            const rect = containerRef.current.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;
            setOpenUpward(spaceBelow < 320 && spaceAbove > spaceBelow);
        }

        setIsOpen((prev) => !prev);
    };

    const commit = (next: Set<string>) => {
        onChange(options.map((option) => option.value).filter((key) => next.has(key)));
    };

    const toggle = (optionValue: string) => {
        const next = new Set(selected);

        if (next.has(optionValue)) {
            next.delete(optionValue);
        } else {
            next.add(optionValue);
        }

        commit(next);
    };

    const remove = (optionValue: string) => {
        const next = new Set(selected);
        next.delete(optionValue);
        commit(next);
    };

    useEffect(() => {
        const onMouseDown = (event: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', onMouseDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('mousedown', onMouseDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, []);

    return (
        <div ref={containerRef} className="relative w-full" dir="rtl">
            <div
                role="button"
                tabIndex={0}
                onClick={toggleOpen}
                onKeyDown={(event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        toggleOpen();
                    }
                }}
                className={`flex min-h-10.5 w-full cursor-pointer items-center justify-between gap-2 rounded-xl border px-2.5 py-1.5 text-xs transition focus:outline-none ${
                    isOpen
                        ? 'border-primary bg-surface-card'
                        : 'border-line-border bg-field hover:border-content-muted/40 focus-visible:border-primary'
                }`}
            >
                <div className="flex min-w-0 flex-1 flex-wrap items-center gap-1.5">
                    {selectedOptions.length === 0 ? (
                        <span className="px-1 text-content-muted">انتخاب کنید</span>
                    ) : (
                        selectedOptions.map((option) => (
                            <span
                                key={option.value}
                                className="flex max-w-full items-center gap-1 rounded-lg bg-primary/10 py-1 ps-2 pe-1 font-semibold text-primary"
                            >
                                <span dir="auto" className="truncate">
                                    {option.label}
                                </span>
                                <button
                                    type="button"
                                    aria-label="حذف"
                                    onClick={(event) => {
                                        event.stopPropagation();
                                        remove(option.value);
                                    }}
                                    className="flex size-4 shrink-0 items-center justify-center rounded-md transition hover:bg-primary/20"
                                >
                                    <Icon name="x" className="size-3" />
                                </button>
                            </span>
                        ))
                    )}
                </div>

                <Icon
                    name="chevron-down"
                    className={`size-4 shrink-0 text-content-muted transition-transform duration-200 ${
                        isOpen ? (openUpward ? 'rotate-0 text-primary' : 'rotate-180 text-primary') : ''
                    }`}
                />
            </div>

            {isOpen && (
                <div
                    className={`custom-scrollbar absolute inset-s-0 z-50 max-h-72 w-full space-y-0.5 overflow-y-auto rounded-xl border border-line-border bg-surface-raised p-1.5 shadow-2xl animate-in fade-in zoom-in-95 duration-100 ${
                        openUpward ? 'bottom-full mb-1.5' : 'top-full mt-1.5'
                    }`}
                >
                    {options.map((option) => {
                        const isOn = selected.includes(option.value);

                        return (
                            <button
                                key={option.value}
                                type="button"
                                onClick={() => toggle(option.value)}
                                className={`flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-xs font-medium transition ${
                                    isOn
                                        ? 'bg-primary/10 font-bold text-primary'
                                        : 'text-content-main hover:bg-line-border/60 hover:text-primary'
                                }`}
                            >
                                <span
                                    className={`flex size-4 shrink-0 items-center justify-center rounded-md border transition ${
                                        isOn
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-line-border bg-surface-card text-transparent'
                                    }`}
                                >
                                    <Icon name="check" className="size-3" />
                                </span>
                                <span dir="auto" className="min-w-0 flex-1 truncate">
                                    {option.label}
                                </span>
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

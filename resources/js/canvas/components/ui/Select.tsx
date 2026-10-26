import { useEffect, useRef, useState, useMemo } from 'react';
import { Icon } from '@canvas/components/ui/Icon';

type Option = string | { value: string; label: string; description?: string };

type SelectProps = {
    id?: string;
    options: Option[];
    placeholder?: string;
    value: unknown;
    onChange: (event: { target: { value: string } }) => void;
    className?: string;
    searchable?: boolean;
};

export function Select({
                           id,
                           options,
                           placeholder = 'انتخاب کنید',
                           value,
                           onChange,
                           className = '',
                           searchable = true,
                       }: SelectProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [openUpward, setOpenUpward] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const containerRef = useRef<HTMLDivElement>(null);
    const searchInputRef = useRef<HTMLInputElement>(null);

    const stringValue = value === null || value === undefined ? '' : String(value);

    const normalizedOptions = useMemo(() => {
        return options.map((option) => {
            if (typeof option === 'string') {
                return { value: option, label: option };
            }
            return option;
        });
    }, [options]);

    const filteredOptions = useMemo(() => {
        if (!searchQuery.trim()) {
            return normalizedOptions;
        }
        const query = searchQuery.trim().toLowerCase();
        return normalizedOptions.filter(
            (opt) =>
                opt.label.toLowerCase().includes(query) ||
                opt.value.toLowerCase().includes(query) ||
                (opt.description && opt.description.toLowerCase().includes(query)),
        );
    }, [normalizedOptions, searchQuery]);

    const selectedOption = normalizedOptions.find((opt) => opt.value === stringValue);

    const toggleOpen = () => {
        if (!isOpen && containerRef.current) {
            const rect = containerRef.current.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;
            setOpenUpward(spaceBelow < 250 && spaceAbove > spaceBelow);
            setSearchQuery('');
            setTimeout(() => searchInputRef.current?.focus(), 50);
        }
        setIsOpen(!isOpen);
    };

    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, []);

    return (
        <div ref={containerRef} className={`relative w-full ${className}`} dir="rtl">
            <button
                type="button"
                id={id}
                onClick={toggleOpen}
                className="flex w-full items-center justify-between rounded-xl border border-line-border bg-field px-3.5 py-2.5 text-xs font-medium text-content-main shadow-xs transition hover:border-content-muted/40 focus:border-primary focus:bg-surface-card focus:outline-none focus:ring-2 focus:ring-primary/20"
            >
                <span className={selectedOption ? 'text-content-main font-semibold' : 'text-content-muted'}>
                    {selectedOption ? selectedOption.label : placeholder}
                </span>

                <Icon
                    name="chevron-down"
                    className={`size-4 text-content-muted transition-transform duration-200 ${
                        isOpen ? (openUpward ? 'rotate-0 text-primary' : 'rotate-180 text-primary') : ''
                    }`}
                />
            </button>

            {isOpen && (
                <div
                    className={`custom-scrollbar absolute inset-s-0 z-50 flex max-h-72 w-full flex-col rounded-xl border border-line-border bg-surface-raised p-1.5 shadow-xl dark:shadow-primary/5 backdrop-blur-md animate-in fade-in zoom-in-95 duration-100 ${
                        openUpward ? 'bottom-full mb-1.5' : 'top-full mt-1.5'
                    }`}
                >
                    {searchable && normalizedOptions.length > 4 && (
                        <div className="mb-1 border-b border-line-border/60 pb-1.5">
                            <div className="relative flex items-center">
                                <Icon
                                    name="search"
                                    className="pointer-events-none absolute inset-s-2.5 size-3.5 text-content-muted"
                                />
                                <input
                                    ref={searchInputRef}
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    placeholder="جستجو در گزینه‌ها..."
                                    className="w-full rounded-lg border border-line-border bg-surface-card py-1.5 pe-2.5 ps-8 text-xs text-content-main placeholder:text-content-muted/50 focus:border-primary focus:outline-none"
                                />
                            </div>
                        </div>
                    )}

                    <div className="custom-scrollbar flex-1 overflow-y-auto space-y-0.5 pe-0.5">
                        {filteredOptions.length === 0 ? (
                            <div className="py-3 text-center text-xs text-content-muted">موردی یافت نشد</div>
                        ) : (
                            filteredOptions.map((option) => {
                                const isSelected = option.value === stringValue;
                                return (
                                    <button
                                        key={option.value}
                                        type="button"
                                        onClick={() => {
                                            onChange({ target: { value: option.value } });
                                            setIsOpen(false);
                                        }}
                                        className={`flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-medium text-start transition ${
                                            isSelected
                                                ? 'bg-primary/10 text-primary font-bold'
                                                : 'text-content-main hover:bg-line-border/60 hover:text-primary'
                                        }`}
                                    >
                                        <div className="min-w-0 flex-1">
                                            <span className="block truncate">{option.label}</span>
                                            {option.description && (
                                                <span className="block truncate text-[10px] text-content-muted font-normal mt-0.5">
                                                    {option.description}
                                                </span>
                                            )}
                                        </div>
                                        {isSelected && (
                                            <Icon name="check" className="size-3.5 shrink-0 text-primary ms-2" />
                                        )}
                                    </button>
                                );
                            })
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

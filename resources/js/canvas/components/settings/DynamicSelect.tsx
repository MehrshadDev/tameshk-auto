import { useEffect, useMemo, useState } from 'react';
import { request } from '@canvas/api/client';
import { inputClass } from '@canvas/components/settings/fieldStyles';
import { Icon } from '@canvas/components/ui/Icon';
import { Select } from '@canvas/components/ui/Select';
import type { ConfigField } from '@canvas/types/graph';

type DynamicSelectProps = {
    id: string;
    field: ConfigField;
    value: unknown;
    onChange: (value: unknown) => void;
    fullConfig?: Record<string, unknown>;
};

type Option = { value: string; label: string };
type Payload = { data?: Option[]; message?: string };

const extractSpreadsheetId = (raw: unknown): string => {
    const value = raw === null || raw === undefined ? '' : String(raw).trim();
    const match = value.match(/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/);

    return match ? match[1] : value;
};

export function DynamicSelect({ id, field, value, onChange, fullConfig }: DynamicSelectProps) {
    const credentialId = fullConfig?.credential_id ? Number(fullConfig.credential_id) : null;
    const spreadsheet = extractSpreadsheetId(fullConfig?.spreadsheet_id);

    const stringValue = value === null || value === undefined ? '' : String(value);
    const looksManual = stringValue.startsWith('http') || stringValue.includes('{{') || stringValue.includes('!');

    const [isManual, setIsManual] = useState<boolean>(looksManual);
    const [options, setOptions] = useState<Option[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [reloadKey, setReloadKey] = useState(0);

    useEffect(() => {
        setOptions([]);
        setError(null);

        if (!credentialId) {
            return;
        }

        let url: string | null = null;

        if (field.source === 'google_sheets_documents') {
            url = `/app/api/credentials/${credentialId}/google/spreadsheets`;
        } else if (field.source === 'google_sheets_sheets' && spreadsheet !== '' && !spreadsheet.includes('{{')) {
            url = `/app/api/credentials/${credentialId}/google/spreadsheets/${encodeURIComponent(spreadsheet)}/sheets`;
        }

        if (!url) {
            return;
        }

        let cancelled = false;
        setIsLoading(true);

        request<Payload>(url)
            .then((payload) => {
                if (!cancelled) {
                    setOptions(payload.data ?? []);
                }
            })
            .catch((err: { message?: string }) => {
                if (!cancelled) {
                    setError(err?.message ?? 'دریافت لیست از گوگل ناموفق بود.');
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setIsLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [credentialId, field.source, spreadsheet, reloadKey]);

    const optionsWithValue = useMemo(() => {
        if (stringValue !== '' && !options.some((option) => option.value === stringValue)) {
            return [{ value: stringValue, label: stringValue }, ...options];
        }

        return options;
    }, [options, stringValue]);

    if (!credentialId) {
        return (
            <div className="flex items-center gap-2 rounded-xl border border-dashed border-line-border bg-surface-card/50 px-3.5 py-2.5 text-xs text-content-muted">
                <Icon name="key" className="size-4 shrink-0" />
                <span>ابتدا اتصال حساب گوگل را انتخاب کنید.</span>
            </div>
        );
    }

    if (field.source === 'google_sheets_sheets' && spreadsheet === '' && !isManual) {
        return (
            <div className="flex items-center gap-2 rounded-xl border border-dashed border-line-border bg-surface-card/50 px-3.5 py-2.5 text-xs text-content-muted">
                <Icon name="file-text" className="size-4 shrink-0" />
                <span>ابتدا اسپردشیت (سند) را انتخاب کنید.</span>
            </div>
        );
    }

    return (
        <div className="space-y-1.5">
            <div className="flex items-center justify-end">
                <div className="flex items-center rounded-lg border border-line-border bg-surface-card p-0.5 text-[10px] font-semibold">
                    <button
                        type="button"
                        onClick={() => setIsManual(false)}
                        className={`rounded-md px-2 py-0.5 transition ${
                            !isManual
                                ? 'bg-primary text-white shadow-xs'
                                : 'text-content-muted hover:text-content-main'
                        }`}
                    >
                        از لیست
                    </button>
                    <button
                        type="button"
                        onClick={() => setIsManual(true)}
                        className={`rounded-md px-2 py-0.5 transition ${
                            isManual
                                ? 'bg-primary text-white shadow-xs'
                                : 'text-content-muted hover:text-content-main'
                        }`}
                    >
                        آدرس یا شناسه
                    </button>
                </div>
            </div>

            {isManual ? (
                <input
                    id={id}
                    type="text"
                    dir="ltr"
                    autoComplete="off"
                    spellCheck={false}
                    value={stringValue}
                    placeholder={
                        field.placeholder ??
                        (field.source === 'google_sheets_documents'
                            ? 'https://docs.google.com/spreadsheets/d/...'
                            : 'Sheet1')
                    }
                    onChange={(e) => onChange(e.target.value)}
                    className={`${inputClass} font-mono text-xs text-left`}
                />
            ) : (
                <div className="flex items-center gap-2">
                    <div className="min-w-0 flex-1">
                        <Select
                            id={id}
                            value={stringValue}
                            options={optionsWithValue}
                            searchable
                            placeholder={
                                isLoading
                                    ? 'در حال دریافت لیست از گوگل...'
                                    : options.length === 0
                                        ? 'موردی در حساب گوگل یافت نشد'
                                        : 'انتخاب کنید'
                            }
                            onChange={(event) => onChange(event.target.value)}
                        />
                    </div>

                    <button
                        type="button"
                        onClick={() => setReloadKey((k) => k + 1)}
                        disabled={isLoading}
                        title="بروزرسانی لیست"
                        className="flex size-10 shrink-0 items-center justify-center rounded-xl border border-line-border bg-surface-card text-content-muted transition hover:border-primary hover:text-primary disabled:opacity-50"
                    >
                        <Icon name="loader" className={`size-4 ${isLoading ? 'animate-spin text-primary' : ''}`} />
                    </button>
                </div>
            )}

            {error && !isManual && (
                <div className="flex items-center justify-between gap-3 rounded-xl border border-primary/20 bg-primary/10 px-3 py-2 text-[11px] text-primary">
                    <span dir="auto">{error}</span>
                    <button
                        type="button"
                        onClick={() => setReloadKey((key) => key + 1)}
                        className="shrink-0 rounded-lg bg-primary/15 px-2.5 py-1 font-semibold transition hover:bg-primary/25"
                    >
                        تلاش مجدد
                    </button>
                </div>
            )}
        </div>
    );
}

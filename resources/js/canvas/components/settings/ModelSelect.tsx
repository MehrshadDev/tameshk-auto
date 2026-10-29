import { useEffect, useState } from 'react';
import { request } from '@canvas/api/client';
import { Select } from '@canvas/components/ui/Select';
import { Icon } from '@canvas/components/ui/Icon';

type ModelSelectProps = {
    credentialId?: number | null;
    value: unknown;
    onChange: (value: string) => void;
};

type ModelItem = {
    value: string;
    label: string;
    description?: string;
};

export function ModelSelect({ credentialId, value, onChange }: ModelSelectProps) {
    const [models, setModels] = useState<ModelItem[] | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!credentialId) {
            setModels(null);
            setError(null);
            setLoading(false);
            return;
        }

        let cancelled = false;

        setLoading(true);
        setError(null);
        setModels(null);

        request<{ data: ModelItem[] }>(`/app/api/credentials/${credentialId}/models`)
            .then((res) => {
                if (!cancelled) setModels(res.data ?? []);
            })
            .catch(() => {
                if (!cancelled) setError('دریافت لیست مدل‌ها با خطا مواجه شد.');
            })
            .finally(() => {
                if (!cancelled) setLoading(false);
            });

        return () => {
            cancelled = true;
        };
    }, [credentialId]);

    if (!credentialId) {
        return (
            <div className="flex items-center gap-2 rounded-xl border border-dashed border-line-border bg-surface-card/50 px-3.5 py-2.5 text-xs text-content-muted">
                <Icon name="key" className="size-4 shrink-0" />
                <span>ابتدا اعتبارنامه API را انتخاب کنید تا لیست مدل‌ها نمایش داده شود.</span>
            </div>
        );
    }

    if (loading) {
        return (
            <div className="flex h-10 items-center justify-between rounded-xl border border-line-border bg-surface-card px-3.5 text-xs text-content-muted">
                <span>در حال دریافت لیست مدل‌ها...</span>
                <Icon name="loader" className="size-4 animate-spin text-primary" />
            </div>
        );
    }

    if (error) {
        return (
            <div className="flex items-center gap-2 rounded-xl border border-primary/20 bg-primary/10 px-3.5 py-2.5 text-xs text-primary">
                <Icon name="alert-triangle" className="size-4 shrink-0" />
                <span dir="auto" className="text-start">{error}</span>
            </div>
        );
    }

    if (models && models.length === 0) {
        return (
            <div className="flex items-center gap-2 rounded-xl border border-amber-500/20 bg-amber-500/10 px-3.5 py-2.5 text-xs text-amber-500">
                <Icon name="alert-circle" className="size-4 shrink-0" />
                <span>هیچ مدلی با این کلید دریافت نشد. صحت API Key را بررسی کنید.</span>
            </div>
        );
    }

    return (
        <Select
            options={models ?? []}
            value={value}
            searchable={true}
            placeholder="مدل هوش مصنوعی را انتخاب کنید"
            onChange={(e) => onChange(e.target.value)}
        />
    );
}

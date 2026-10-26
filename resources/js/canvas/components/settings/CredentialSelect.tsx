import { useEffect, useState } from 'react';
import { fetchCredentials, type CredentialDefinition, type CredentialItem } from '@canvas/api/credentials';
import { CredentialModal } from '@canvas/components/credentials/CredentialModal';
import { Select } from '@canvas/components/ui/Select';
import { Icon } from '@canvas/components/ui/Icon';

type CredentialSelectProps = {
    id: string;
    credentialType: string;
    value: unknown;
    onChange: (value: number | null) => void;
};

export function CredentialSelect({ id, credentialType, value, onChange }: CredentialSelectProps) {
    const [items, setItems] = useState<CredentialItem[] | null>(null);
    const [definition, setDefinition] = useState<CredentialDefinition | null>(null);
    const [failed, setFailed] = useState(false);
    const [modalOpen, setModalOpen] = useState(false);

    const load = () => {
        fetchCredentials(credentialType)
            .then(({ items: fetchedItems, definitions }) => {
                setItems(fetchedItems);
                setDefinition(definitions.find((d) => d.key === credentialType) ?? null);
            })
            .catch(() => setFailed(true));
    };

    useEffect(() => {
        load();
    }, [credentialType]);

    if (failed) {
        return <p dir="auto" className="text-start text-xs text-primary">بارگذاری اعتبارنامه‌ها با خطا مواجه شد.</p>;
    }

    if (items === null) {
        return <div className="h-10 animate-pulse rounded-xl bg-line-border/60" />;
    }

    const options = items.map((item) => ({ value: String(item.id), label: item.name }));

    const handleCreated = (newItem: CredentialItem) => {
        setItems((prev) => (prev ? [...prev, newItem] : [newItem]));
        onChange(newItem.id);
    };

    const handleDeleted = (deletedId: number) => {
        setItems((prev) => (prev ? prev.filter((i) => i.id !== deletedId) : []));
        if (value === deletedId) onChange(null);
    };

    return (
        <div className="space-y-2">
            <div className="flex items-center gap-2">
                <div className="min-w-0 flex-1">
                    <Select
                        id={id}
                        value={value === null || value === undefined ? '' : String(value)}
                        placeholder="انتخاب اعتبارنامه"
                        options={options}
                        onChange={(e) => onChange(e.target.value === '' ? null : Number(e.target.value))}
                    />
                </div>

                <button
                    type="button"
                    onClick={() => setModalOpen(true)}
                    title="مدیریت یا افزودن کلید جدید"
                    className="flex size-10 shrink-0 items-center justify-center rounded-xl border border-line-border bg-surface-card text-content-muted hover:border-primary hover:text-primary transition shadow-xs"
                >
                    <Icon name="key" className="size-4" />
                </button>
            </div>

            {definition && (
                <CredentialModal
                    isOpen={modalOpen}
                    onClose={() => setModalOpen(false)}
                    definition={definition}
                    existingItems={items}
                    onCreated={handleCreated}
                    onDeleted={handleDeleted}
                />
            )}
        </div>
    );
}

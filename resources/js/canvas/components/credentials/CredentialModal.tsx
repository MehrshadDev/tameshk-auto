import { useEffect, useState } from 'react';
import { request } from '@canvas/api/client';
import { ConfirmModal } from '@canvas/components/ui/ConfirmModal';
import { Icon } from '@canvas/components/ui/Icon';
import { Modal } from '@canvas/components/ui/Modal';
import { PasswordField } from '@canvas/components/ui/PasswordField';
import { Select } from '@canvas/components/ui/Select';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';
import type { CredentialDefinition, CredentialItem } from '@canvas/api/credentials';
import type { ConfigField } from '@canvas/types/graph';

type CredentialModalProps = {
    isOpen: boolean;
    onClose: () => void;
    definition: CredentialDefinition;
    onCreated: (item: CredentialItem) => void;
    onDeleted?: (id: number) => void;
    existingItems: CredentialItem[];
};

const baseInputClass =
    'w-full rounded-xl border border-line-border bg-field px-3.5 py-2.5 text-xs text-content-main transition focus:border-primary focus:bg-surface-card focus:outline-none focus:ring-2 focus:ring-primary/20 shadow-xs';

const initialFormData = (schema: Record<string, ConfigField>): Record<string, string> =>
    Object.fromEntries(
        Object.entries(schema).map(([key, field]) => [
            key,
            field.default !== undefined && field.default !== null ? String(field.default) : '',
        ]),
    );

function FieldLabel({ label }: { label: string }) {
    return (
        <label className="text-xs font-semibold text-content-main">
            {label}
        </label>
    );
}

export function CredentialModal({
                                    isOpen,
                                    onClose,
                                    definition,
                                    onCreated,
                                    onDeleted,
                                    existingItems,
                                }: CredentialModalProps) {
    const [view, setView] = useState<'create' | 'list'>('create');
    const [name, setName] = useState('');
    const [formData, setFormData] = useState<Record<string, string>>(() =>
        initialFormData(definition.schema),
    );
    const [isSaving, setIsSaving] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [pendingDelete, setPendingDelete] = useState<CredentialItem | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const addToast = useWorkflowStore((state) => state.addToast);

    useEffect(() => {
        if (isOpen) {
            setView('create');
            setName('');
            setFormData(initialFormData(definition.schema));
            setErrorMessage(null);
            setPendingDelete(null);
        }
    }, [isOpen, definition.key]);

    const updateField = (key: string, value: string) => {
        setFormData((prev) => ({ ...prev, [key]: value }));
    };

    const handleCreate = async (e: React.SyntheticEvent) => {
        e.preventDefault();
        setErrorMessage(null);

        if (!name.trim()) {
            setErrorMessage('نام اعتبارنامه الزامی است.');
            return;
        }

        for (const [key, field] of Object.entries(definition.schema)) {
            if (field.required && !formData[key]?.trim()) {
                setErrorMessage(`${field.label ?? key} الزامی است.`);
                return;
            }
        }

        setIsSaving(true);

        try {
            const res = await request<{ data: CredentialItem }>('/app/api/credentials', {
                method: 'POST',
                body: JSON.stringify({
                    type: definition.key,
                    name: name.trim(),
                    data: formData,
                }),
            });

            addToast('اعتبارنامه با موفقیت ذخیره شد.', 'success', 2500);
            onCreated(res.data);
            onClose();
        } catch (error: any) {
            setErrorMessage(error?.message ?? 'خطا در اعتبارسنجی و ذخیره رخ داد.');
        } finally {
            setIsSaving(false);
        }
    };

    const confirmDelete = async () => {
        if (!pendingDelete) {
            return;
        }

        setIsDeleting(true);

        try {
            await request(`/app/api/credentials/${pendingDelete.id}`, { method: 'DELETE' });
            addToast('اعتبارنامه حذف شد.', 'info', 2500);
            onDeleted?.(pendingDelete.id);
            setPendingDelete(null);
        } catch {
            addToast('خطا در حذف اعتبارنامه رخ داد.', 'error');
        } finally {
            setIsDeleting(false);
        }
    };

    const renderField = (key: string, field: ConfigField) => {
        const value = formData[key] ?? '';
        const inputName = `credential_${key}_field`;
        const label = field.label ?? key;

        const control = (() => {
            switch (field.type) {
                case 'password':
                    return (
                        <PasswordField
                            name={inputName}
                            value={value}
                            placeholder={field.placeholder}
                            onChange={(val) => updateField(key, val)}
                        />
                    );

                case 'select': {
                    const options = (field.options ?? []).map((opt) =>
                        typeof opt === 'string' ? { value: opt, label: opt } : opt,
                    );
                    return (
                        <Select
                            value={value}
                            placeholder={field.placeholder ?? 'انتخاب کنید'}
                            options={options}
                            onChange={(e) => updateField(key, e.target.value)}
                        />
                    );
                }

                case 'textarea':
                case 'code':
                    return (
                        <textarea
                            name={inputName}
                            rows={3}
                            value={value}
                            maxLength={10000}
                            placeholder={field.placeholder}
                            dir={field.type === 'code' ? 'ltr' : undefined}
                            onChange={(e) => updateField(key, e.target.value)}
                            className={`${baseInputClass} resize-none ${field.type === 'code' ? 'font-mono' : ''}`}
                        />
                    );

                case 'number':
                    return (
                        <input
                            type="text"
                            name={inputName}
                            inputMode={field.integer ? 'numeric' : 'decimal'}
                            dir="ltr"
                            autoComplete="off"
                            spellCheck={false}
                            value={value}
                            maxLength={20}
                            placeholder={field.placeholder}
                            onChange={(e) => updateField(key, e.target.value)}
                            className={baseInputClass}
                        />
                    );

                case 'text':
                default:
                    return (
                        <input
                            type="text"
                            name={inputName}
                            autoComplete="off"
                            spellCheck={false}
                            value={value}
                            maxLength={2000}
                            placeholder={field.placeholder}
                            onChange={(e) => updateField(key, e.target.value)}
                            className={baseInputClass}
                        />
                    );
            }
        })();

        return (
            <div key={key} className="space-y-1.5">
                <FieldLabel label={label} />
                {control}
                {field.help && <p className="text-[11px] text-content-muted">{field.help}</p>}
            </div>
        );
    };

    return (
        <>
            <Modal
                isOpen={isOpen}
                onClose={() => {
                    if (!pendingDelete) onClose();
                }}
                maxWidth="max-w-lg"
            >
                <div className="p-6" dir="rtl">
                    <div className="flex items-center justify-between border-b border-line-border/60 pb-4 mb-5">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-xl bg-purple-500/10 text-purple-500">
                                <Icon name="key" className="size-5" />
                            </div>
                            <div>
                                <h3 className="text-sm font-bold text-content-main">مدیریت {definition.name}</h3>
                                <p className="text-[11px] text-content-muted mt-0.5">{definition.description}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-1.5 bg-surface-card p-1 rounded-xl border border-line-border">
                            <button
                                type="button"
                                onClick={() => setView('create')}
                                className={`rounded-lg px-2.5 py-1 text-xs font-semibold transition ${
                                    view === 'create'
                                        ? 'bg-primary text-white shadow-xs'
                                        : 'text-content-muted hover:text-content-main'
                                }`}
                            >
                                کلید جدید
                            </button>
                            <button
                                type="button"
                                onClick={() => setView('list')}
                                className={`rounded-lg px-2.5 py-1 text-xs font-semibold transition ${
                                    view === 'list'
                                        ? 'bg-primary text-white shadow-xs'
                                        : 'text-content-muted hover:text-content-main'
                                }`}
                            >
                                کلیدهای من ({existingItems.length})
                            </button>
                        </div>
                    </div>

                    {errorMessage && (
                        <div dir="auto" className="mb-4 rounded-xl border border-primary/20 bg-primary/10 p-3 text-start text-xs text-primary">
                            {errorMessage}
                        </div>
                    )}

                    {view === 'create' ? (
                        <form onSubmit={handleCreate} autoComplete="off" className="space-y-4">
                            <div className="space-y-1.5">
                                <FieldLabel label="نام حساب یا کلید" />
                                <input
                                    autoFocus
                                    type="text"
                                    name="credential_name_field"
                                    autoComplete="off"
                                    data-lpignore="true"
                                    data-1p-ignore="true"
                                    spellCheck={false}
                                    value={name}
                                    maxLength={100}
                                    onChange={(e) => setName(e.target.value)}
                                    className={baseInputClass}
                                />
                            </div>

                            {Object.entries(definition.schema).map(([key, field]) =>
                                renderField(key, field),
                            )}

                            <div className="flex items-center justify-end gap-2.5 pt-3 border-t border-line-border/60">
                                <button
                                    type="button"
                                    onClick={onClose}
                                    disabled={isSaving}
                                    className="rounded-xl border border-line-border px-4 py-2 text-xs font-semibold text-content-main hover:bg-line-border/60 transition"
                                >
                                    انصراف
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSaving}
                                    className="rounded-xl bg-primary px-5 py-2 text-xs font-bold text-white hover:bg-primary/90 transition shadow-md disabled:opacity-50"
                                >
                                    {isSaving ? 'در حال ذخیره‌سازی...' : 'ذخیره و اتصال'}
                                </button>
                            </div>
                        </form>
                    ) : (
                        <div className="space-y-2.5 max-h-72 overflow-y-auto custom-scrollbar pe-1">
                            {existingItems.length === 0 ? (
                                <p className="py-8 text-center text-xs text-content-muted">
                                    هنوز هیچ کلیدی برای این سرویس ثبت نشده است.
                                </p>
                            ) : (
                                existingItems.map((item) => (
                                    <div
                                        key={item.id}
                                        className="flex items-center justify-between rounded-xl border border-line-border/80 bg-surface-card p-3 shadow-xs"
                                    >
                                        <div className="flex items-center gap-2.5 min-w-0">
                                            <Icon name="key" className="size-4 text-primary shrink-0" />
                                            <span className="text-xs font-bold text-content-main truncate">
                                                {item.name}
                                            </span>
                                        </div>

                                        <button
                                            type="button"
                                            onClick={() => setPendingDelete(item)}
                                            title="حذف کلید"
                                            className="flex size-7 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary"
                                        >
                                            <Icon name="trash" className="size-3.5" />
                                        </button>
                                    </div>
                                ))
                            )}
                        </div>
                    )}
                </div>
            </Modal>

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title="حذف اعتبارنامه"
                message={`«${pendingDelete?.name ?? ''}» برای همیشه حذف می‌شود. نودهایی که از این کلید استفاده می‌کنند هنگام اجرا خطا می‌دهند.`}
                isLoading={isDeleting}
                onConfirm={() => void confirmDelete()}
                onCancel={() => setPendingDelete(null)}
            />
        </>
    );
}

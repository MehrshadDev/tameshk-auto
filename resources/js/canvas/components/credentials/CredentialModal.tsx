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
    onUpdated?: (item: CredentialItem) => void;
    onDeleted?: (id: number) => void;
    existingItems: CredentialItem[];
};

const baseInputClass =
    'w-full rounded-xl border border-line-border bg-field px-4 py-2.5 text-sm text-content-main transition focus:border-primary focus:bg-surface-card focus:outline-none focus:ring-2 focus:ring-primary/20 shadow-xs';

const getDefaultName = (def: CredentialDefinition): string => {
    switch (def.key) {
        case 'gmail':
            return 'Gmail account';
        case 'google':
            return 'Google account';
        case 'telegram':
            return 'Telegram account';
        case 'gemini':
            return 'Gemini account';
        case 'http_bearer':
            return 'Bearer token';
        case 'basic_auth':
            return 'Basic auth';
        default:
            return `${def.name} account`;
    }
};

const initialFormData = (schema: Record<string, ConfigField>): Record<string, string> =>
    Object.fromEntries(
        Object.entries(schema).map(([key, field]) => [
            key,
            field.default !== undefined && field.default !== null ? String(field.default) : '',
        ]),
    );

export function CredentialModal({
                                    isOpen,
                                    onClose,
                                    definition,
                                    onCreated,
                                    onUpdated,
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

    const schemaEntries = Object.entries(definition.schema);
    const isSingleField = schemaEntries.length === 1;

    useEffect(() => {
        if (isOpen) {
            setView('create');
            setName(getDefaultName(definition));
            setFormData(initialFormData(definition.schema));
            setErrorMessage(null);
            setPendingDelete(null);
        }
    }, [isOpen, definition.key]);

    const updateField = (key: string, value: string) => {
        setFormData((prev) => ({ ...prev, [key]: value }));
    };

    const openGoogleOAuthPopup = (credentialId: number, service: string) => {
        window.open(
            `/app/api/oauth/google/redirect?credential_id=${credentialId}`,
            'google_oauth',
            'width=550,height=650',
        );

        const expectedType = service === 'google' ? 'GOOGLE_OAUTH_RESULT' : 'GMAIL_OAUTH_RESULT';

        const listener = async (event: MessageEvent) => {
            if (event.data?.type === expectedType) {
                window.removeEventListener('message', listener);
                if (event.data.status === 'success') {
                    addToast(event.data.message, 'success', 4000);
                    try {
                        const res = await request<{ data: CredentialItem }>(`/app/api/credentials/${credentialId}`);
                        onUpdated?.(res.data);
                    } catch {}
                } else {
                    addToast(event.data.message, 'error', 5000);
                }
            }
        };

        window.addEventListener('message', listener);
    };

    const handleCreate = async (e: React.SyntheticEvent) => {
        e.preventDefault();
        setErrorMessage(null);

        if (!name.trim()) {
            setErrorMessage('نام حساب الزامی است.');
            return;
        }

        for (const [key, field] of schemaEntries) {
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

            if (definition.key === 'gmail' || definition.key === 'google') {
                openGoogleOAuthPopup(res.data.id, definition.key);
            }

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
            addToast('حساب حذف شد.', 'info', 2500);
            onDeleted?.(pendingDelete.id);
            setPendingDelete(null);
        } catch {
            addToast('خطا در حذف رخ داد.', 'error');
        } finally {
            setIsDeleting(false);
        }
    };

    const renderField = (key: string, field: ConfigField) => {
        const value = formData[key] ?? '';
        const inputName = `credential_${key}_field`;
        const label = field.label ?? key;
        const spanFull = isSingleField || field.type === 'textarea' || field.type === 'code';

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
                            dir="auto"
                            maxLength={10000}
                            placeholder={field.placeholder}
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
                            dir="auto"
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
            <div key={key} className={`space-y-2 ${spanFull ? 'sm:col-span-2' : ''}`}>
                <label className="block text-xs font-semibold text-content-main">
                    {label}
                </label>
                {control}
                {field.help && (
                    <p className="text-[11px] leading-5 text-content-muted">{field.help}</p>
                )}
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
                maxWidth="max-w-3xl"
            >
                <div className="p-7 sm:p-8" dir="rtl">
                    <div className="flex items-center justify-between gap-6 border-b border-line-border/60 pb-5 mb-6">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-xl bg-purple-500/10 text-purple-500">
                                <Icon name={definition.icon ?? 'key'} className="size-5" />
                            </div>
                            <div>
                                <h3 className="text-base font-bold text-content-main">{definition.name}</h3>
                                <p className="text-xs text-content-muted mt-1">{definition.description}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-1 bg-surface-card p-1 rounded-xl border border-line-border">
                            <button
                                type="button"
                                onClick={() => setView('create')}
                                className={`rounded-lg px-3 py-1 text-xs font-semibold transition ${
                                    view === 'create'
                                        ? 'bg-primary text-white shadow-xs'
                                        : 'text-content-muted hover:text-content-main'
                                }`}
                            >
                                حساب جدید
                            </button>
                            <button
                                type="button"
                                onClick={() => setView('list')}
                                className={`rounded-lg px-3 py-1 text-xs font-semibold transition ${
                                    view === 'list'
                                        ? 'bg-primary text-white shadow-xs'
                                        : 'text-content-muted hover:text-content-main'
                                }`}
                            >
                                حساب‌های من ({existingItems.length})
                            </button>
                        </div>
                    </div>

                    {errorMessage && (
                        <div dir="auto" className="mb-4 rounded-xl border border-primary/20 bg-primary/10 p-3 text-start text-xs text-primary">
                            {errorMessage}
                        </div>
                    )}

                    {view === 'create' ? (
                        <form onSubmit={handleCreate} autoComplete="off" className="space-y-6">
                            <div className="space-y-2">
                                <label className="block text-xs font-semibold text-content-main">
                                    نام حساب
                                </label>
                                <input
                                    autoFocus
                                    type="text"
                                    dir="auto"
                                    name="credential_name_field"
                                    autoComplete="off"
                                    spellCheck={false}
                                    value={name}
                                    maxLength={100}
                                    onChange={(e) => setName(e.target.value)}
                                    className={baseInputClass}
                                />
                            </div>

                            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                {schemaEntries.map(([key, field]) => renderField(key, field))}
                            </div>

                            {(definition.key === 'gmail' || definition.key === 'google') && (
                                <div className="space-y-2.5 rounded-xl border border-line-border/70 bg-surface-card p-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-semibold text-content-main">Redirect URI</span>
                                        <span className="text-[10px] text-content-muted">برای تنظیم در Google Cloud Console</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <input
                                            readOnly
                                            dir="ltr"
                                            value={`${window.location.origin}/oauth/google/callback`}
                                            onFocus={(e) => e.currentTarget.select()}
                                            className="w-full rounded-lg border border-line-border bg-field px-3.5 py-2.5 font-mono text-xs text-content-main select-all focus:outline-none"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => {
                                                navigator.clipboard.writeText(`${window.location.origin}/oauth/google/callback`);
                                                addToast('کپی شد.', 'success', 2000);
                                            }}
                                            className="flex size-8 shrink-0 items-center justify-center rounded-lg border border-line-border bg-surface-raised text-content-muted hover:border-primary hover:text-primary transition"
                                        >
                                            <Icon name="copy" className="size-3.5" />
                                        </button>
                                    </div>
                                </div>
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
                        <div className="space-y-2.5 max-h-112 overflow-y-auto custom-scrollbar pe-1">
                            {existingItems.length === 0 ? (
                                <p className="py-8 text-center text-xs text-content-muted">
                                    هنوز هیچ حسابی ثبت نشده است.
                                </p>
                            ) : (
                                existingItems.map((item) => (
                                    <div
                                        key={item.id}
                                        className="flex items-center justify-between gap-4 rounded-xl border border-line-border bg-surface-card p-3.5 shadow-xs"
                                    >
                                        <div className="flex items-center gap-3 min-w-0">
                                            <div className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-500">
                                                <Icon name={definition.icon ?? 'key'} className="size-4.5" />
                                            </div>
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-2">
                                                    <span dir="auto" className="text-xs font-bold text-content-main truncate">
                                                        {item.name}
                                                    </span>
                                                    {item.is_connected && (
                                                        <span className="flex items-center gap-1 rounded-md bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-500">
                                                            <span className="size-1.5 rounded-full bg-emerald-500" />
                                                            متصل
                                                        </span>
                                                    )}
                                                </div>
                                                {item.connected_email && (
                                                    <p dir="ltr" className="text-[11px] font-mono text-content-muted mt-0.5 text-right truncate">
                                                        {item.connected_email}
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2 shrink-0">
                                            {(definition.key === 'gmail' || definition.key === 'google') && (
                                                <button
                                                    type="button"
                                                    onClick={() => openGoogleOAuthPopup(item.id, definition.key)}
                                                    className={`flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition ${
                                                        item.is_connected
                                                            ? 'border border-line-border text-content-muted hover:text-content-main'
                                                            : 'bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500/20'
                                                    }`}
                                                >
                                                    <Icon name="external-link" className="size-3.5" />
                                                    <span>{item.is_connected ? 'اتصال مجدد' : 'اتصال به گوگل'}</span>
                                                </button>
                                            )}

                                            <button
                                                type="button"
                                                onClick={() => setPendingDelete(item)}
                                                title="حذف حساب"
                                                className="flex size-8 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary"
                                            >
                                                <Icon name="trash" className="size-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    )}
                </div>
            </Modal>

            <ConfirmModal
                isOpen={pendingDelete !== null}
                title="حذف حساب"
                message={`حساب «${pendingDelete?.name ?? ''}» حذف می‌شود. نودهای متصل به آن با خطا مواجه خواهند شد.`}
                isLoading={isDeleting}
                onConfirm={() => void confirmDelete()}
                onCancel={() => setPendingDelete(null)}
            />
        </>
    );
}

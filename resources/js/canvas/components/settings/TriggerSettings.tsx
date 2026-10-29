import { useState, useRef, useEffect } from 'react';
import { ConfigFieldInput } from '@canvas/components/settings/ConfigFieldInput';
import { FieldShell } from '@canvas/components/settings/FieldShell';
import { Icon } from '@canvas/components/ui/Icon';
import type { FlowNode } from '@canvas/lib/graph';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';
import type { ConfigField, NodeDefinition } from '@canvas/types/graph';
import { TriggerEndpointList } from '@canvas/components/settings/TriggerEndpointList';

type TriggerSettingsProps = {
    node: FlowNode;
    definition?: NodeDefinition;
};

const isFieldVisible = (
    field: ConfigField,
    config: Record<string, unknown>,
    schema: Record<string, ConfigField>,
): boolean => {
    if (!field.depends_on) return true;

    for (const [depKey, depValue] of Object.entries(field.depends_on)) {
        const actual = config[depKey] ?? schema[depKey]?.default ?? '';
        const allowed = Array.isArray(depValue) ? depValue : [depValue];
        if (!allowed.includes(String(actual))) {
            return false;
        }
    }

    return true;
};

export function TriggerSettings({ node, definition }: TriggerSettingsProps) {
    const updateNode = useWorkflowStore((state) => state.updateNode);
    const setChatOpen = useWorkflowStore((state) => state.setChatOpen);
    const closePanel = useWorkflowStore((state) => state.closePanel);

    const [optionsMenuOpen, setOptionsMenuOpen] = useState(false);
    const optionsContainerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!optionsMenuOpen) return;

        const handleOutsideClick = (e: MouseEvent) => {
            if (optionsContainerRef.current && !optionsContainerRef.current.contains(e.target as Node)) {
                setOptionsMenuOpen(false);
            }
        };

        document.addEventListener('mousedown', handleOutsideClick);
        return () => document.removeEventListener('mousedown', handleOutsideClick);
    }, [optionsMenuOpen]);

    const schema = definition?.configSchema ?? {};
    const config = node.data.config;
    const isChat = node.data.nodeType === 'trigger.chat';

    const visibleFields = Object.entries(schema).filter(([, field]) =>
        isFieldVisible(field, config, schema),
    );

    const primaryFields = visibleFields.filter(([, field]) => !field.advanced);
    const availableAdvancedFields = visibleFields.filter(([, field]) => field.advanced);

    const activeAdvancedFields = availableAdvancedFields.filter(
        ([key]) => config[key] !== undefined && config[key] !== null,
    );
    const inactiveAdvancedFields = availableAdvancedFields.filter(
        ([key]) => config[key] === undefined || config[key] === null,
    );

    const setValue = (key: string, value: unknown) => {
        const nextConfig: Record<string, unknown> = { ...config, [key]: value };

        if (key === 'credential_id') {
            delete nextConfig.spreadsheet_id;
            delete nextConfig.sheet;
        } else if (key === 'spreadsheet_id') {
            delete nextConfig.sheet;
        }

        updateNode(node.id, { config: nextConfig });
    };

    const addAdvancedField = (key: string, field: ConfigField) => {
        updateNode(node.id, {
            config: {
                ...config,
                [key]: field.default ?? '',
            },
        });
        setOptionsMenuOpen(false);
    };

    const removeAdvancedField = (key: string) => {
        const nextConfig = { ...config };
        delete nextConfig[key];
        updateNode(node.id, { config: nextConfig });
    };

    const openChatTest = () => {
        closePanel();
        setChatOpen(true);
    };

    return (
        <div className="space-y-5">
            {definition?.description && (
                <p className="text-xs leading-6 text-content-muted">{definition.description}</p>
            )}

            <TriggerEndpointList nodeType={node.data.nodeType} />

            {isChat && (
                <div className="flex items-center justify-between rounded-2xl border border-primary/20 bg-primary/5 p-4">
                    <div className="flex items-center gap-3">
                        <div className="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Icon name="message-circle" className="size-5" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-content-main">پنجره اختصاصی گفتگو</p>
                            <p className="text-[11px] text-content-muted mt-0.5">
                                تست و ارسال پیام در نوار پایین بوم قرار دارد.
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={openChatTest}
                        className="flex items-center gap-1.5 rounded-xl bg-primary px-3.5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-primary/90"
                    >
                        <Icon name="player-play" className="size-3.5" />
                        <span>باز کردن چت</span>
                    </button>
                </div>
            )}

            {visibleFields.length === 0 && (
                <p className="py-12 text-center text-xs text-content-muted">این شروع‌کننده تنظیماتی ندارد.</p>
            )}

            {primaryFields.map(([key, field]) => {
                const id = `trigger-field-${node.id}-${key}`;

                return (
                    <FieldShell key={key} id={id} label={field.label ?? key} required={field.required} help={field.help}>
                        <ConfigFieldInput
                            id={id}
                            field={field}
                            value={config[key] ?? field.default ?? null}
                            fullConfig={config}
                            onChange={(value) => setValue(key, value)}
                        />
                    </FieldShell>
                );
            })}

            {activeAdvancedFields.map(([key, field]) => {
                const id = `trigger-field-${node.id}-${key}`;

                return (
                    <div key={key} className="relative rounded-2xl border border-line-border/70 bg-surface-card/60 p-4 pt-3">
                        <div className="flex items-center justify-between mb-2 pb-1.5 border-b border-line-border/50">
                            <span className="text-xs font-bold text-content-main">
                                {field.label ?? key}
                            </span>
                            <button
                                type="button"
                                onClick={() => removeAdvancedField(key)}
                                title="حذف این گزینه"
                                className="flex size-6 items-center justify-center rounded-lg text-content-muted transition hover:bg-primary/10 hover:text-primary"
                            >
                                <Icon name="x" className="size-3.5" />
                            </button>
                        </div>

                        <ConfigFieldInput
                            id={id}
                            field={field}
                            value={config[key] ?? field.default ?? null}
                            fullConfig={config}
                            onChange={(value) => setValue(key, value)}
                        />
                    </div>
                );
            })}

            {inactiveAdvancedFields.length > 0 && (
                <div className="pt-2">
                    <div ref={optionsContainerRef} className="relative">
                        <button
                            type="button"
                            onClick={() => setOptionsMenuOpen((prev) => !prev)}
                            className="flex w-full items-center justify-center gap-2 rounded-2xl border border-line-border bg-surface-card py-2.5 text-xs font-semibold text-content-main hover:bg-line-border/40 transition shadow-xs"
                        >
                            <Icon name="plus" className="size-3.5" />
                            <span>افزودن گزینه (گزینه‌های بیشتر)</span>
                        </button>

                        {optionsMenuOpen && (
                            <div className="absolute right-0 bottom-full mb-1.5 w-full rounded-2xl border border-line-border bg-surface-raised p-1.5 shadow-xl backdrop-blur-md z-30 space-y-0.5">
                                {inactiveAdvancedFields.map(([key, field]) => (
                                    <button
                                        key={key}
                                        type="button"
                                        onClick={() => addAdvancedField(key, field)}
                                        className="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-start text-xs font-medium text-content-main hover:bg-primary/10 hover:text-primary transition"
                                    >
                                        <Icon name="plus" className="size-3.5 text-primary" />
                                        <span>{field.label ?? key}</span>
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

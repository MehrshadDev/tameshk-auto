import { ConfigFieldInput } from '@canvas/components/settings/ConfigFieldInput';
import { FieldShell } from '@canvas/components/settings/FieldShell';
import { inputClass } from '@canvas/components/settings/fieldStyles';
import { Icon } from '@canvas/components/ui/Icon';
import { asString, textDir } from '@canvas/lib/fields';
import type { FlowNode } from '@canvas/lib/graph';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';
import type { ConfigField, NodeDefinition } from '@canvas/types/graph';
import { TriggerEndpointList } from '@canvas/components/settings/TriggerEndpointList';

type TriggerSettingsProps = {
    node: FlowNode;
    definition?: NodeDefinition;
};

type TriggerFieldProps = {
    id: string;
    field: ConfigField;
    value: unknown;
    onChange: (value: unknown) => void;
};

function TriggerField({ id, field, value, onChange }: TriggerFieldProps) {
    const text = asString(value);

    if (field.type === 'textarea') {
        return (
            <textarea
                id={id}
                rows={3}
                autoComplete="off"
                spellCheck={false}
                value={text}
                maxLength={10000}
                placeholder={field.placeholder}
                dir={textDir(text, field.placeholder)}
                onChange={(event) => onChange(event.target.value)}
                className={`${inputClass} resize-none`}
            />
        );
    }

    if (field.type === 'text') {
        return (
            <input
                id={id}
                type="text"
                autoComplete="off"
                spellCheck={false}
                value={text}
                maxLength={2000}
                placeholder={field.placeholder}
                dir={textDir(text, field.placeholder)}
                onChange={(event) => onChange(event.target.value)}
                className={inputClass}
            />
        );
    }

    return <ConfigFieldInput id={id} field={field} value={value} onChange={onChange} />;
}

export function TriggerSettings({ node, definition }: TriggerSettingsProps) {
    const updateNode = useWorkflowStore((state) => state.updateNode);
    const setChatOpen = useWorkflowStore((state) => state.setChatOpen);
    const closePanel = useWorkflowStore((state) => state.closePanel);

    const fields = Object.entries(definition?.configSchema ?? {});
    const isChat = node.data.nodeType === 'trigger.chat';
    const config = node.data.config;

    const setValue = (key: string, value: unknown) =>
        updateNode(node.id, { config: { ...node.data.config, [key]: value } });

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

            {fields.length === 0 && (
                <p className="py-12 text-center text-xs text-content-muted">این شروع‌کننده تنظیماتی ندارد.</p>
            )}

            {fields.map(([key, field]) => {
                const id = `trigger-field-${node.id}-${key}`;

                return (
                    <FieldShell key={key} id={id} label={field.label ?? key} required={field.required} help={field.help}>
                        <TriggerField
                            id={id}
                            field={field}
                            value={config[key] ?? field.default ?? null}
                            onChange={(value) => setValue(key, value)}
                        />
                    </FieldShell>
                );
            })}
        </div>
    );
}

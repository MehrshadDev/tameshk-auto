import { useState } from 'react';
import { ConditionsEditor } from '@canvas/components/settings/ConditionsEditor';
import { CredentialSelect } from '@canvas/components/settings/CredentialSelect';
import { inputClass } from '@canvas/components/settings/fieldStyles';
import { KeyValueEditor } from '@canvas/components/settings/KeyValueEditor';
import { ModelSelect } from '@canvas/components/settings/ModelSelect';
import { PasswordField } from '@canvas/components/ui/PasswordField';
import { Select } from '@canvas/components/ui/Select';
import { asString, normalizeOptions, textDir } from '@canvas/lib/fields';
import type { ConfigField } from '@canvas/types/graph';
import { MultiSelectField } from '@canvas/components/settings/MultiSelectField';

type ConfigFieldInputProps = {
    id: string;
    field: ConfigField;
    value: unknown;
    onChange: (value: unknown) => void;
    incomingData?: unknown;
    fullConfig?: Record<string, unknown>;
};

const resolveExpression = (data: unknown, text: string): { found: boolean; value: unknown } => {
    const matches = Array.from(text.matchAll(/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/g));

    if (matches.length === 0) {
        return { found: true, value: undefined };
    }

    let allFound = true;
    let lastValue: unknown = undefined;

    for (const match of matches) {
        const expression = match[1].trim();

        if (expression !== 'input' && !expression.startsWith('input.')) {
            allFound = false;
            continue;
        }

        const parts = expression === 'input' ? [] : expression.slice('input.'.length).split('.');

        let current: unknown = data;
        let stepFound = true;

        for (const part of parts) {
            if (current !== null && typeof current === 'object' && Object.prototype.hasOwnProperty.call(current, part)) {
                current = (current as Record<string, unknown>)[part];
            } else {
                stepFound = false;
                break;
            }
        }

        if (stepFound) {
            lastValue = current;
        } else {
            allFound = false;
        }
    }

    return { found: allFound, value: lastValue };
};

function NumberField({ id, field, value, onChange }: ConfigFieldInputProps) {
    const [draft, setDraft] = useState(asString(value));
    const allowDecimal = !field.integer;
    const pattern = allowDecimal ? /^-?\d*\.?\d*$/ : /^-?\d*$/;

    const edit = (raw: string) => {
        if (!pattern.test(raw)) return;
        setDraft(raw);

        if (raw === '' || raw === '-' || raw === '.' || raw === '-.') {
            onChange(null);
            return;
        }

        const parsed = Number(raw);
        if (!Number.isFinite(parsed)) {
            onChange(null);
            return;
        }

        if (!allowDecimal && !Number.isInteger(parsed)) {
            onChange(null);
            return;
        }

        onChange(parsed);
    };

    const clamp = () => {
        const parsed = Number(draft);
        if (draft === '' || !Number.isFinite(parsed)) {
            setDraft('');
            onChange(null);
            return;
        }
        const next = Math.min(field.max ?? Infinity, Math.max(field.min ?? -Infinity, parsed));
        setDraft(String(next));
        onChange(next);
    };

    return (
        <input
            id={id}
            type="text"
            inputMode={allowDecimal ? 'decimal' : 'numeric'}
            autoComplete="off"
            dir="ltr"
            value={draft}
            placeholder={field.placeholder}
            onChange={(e) => edit(e.target.value)}
            onBlur={clamp}
            className={inputClass}
        />
    );
}

export function ConfigFieldInput(props: ConfigFieldInputProps) {
    const { id, field, value, onChange, incomingData, fullConfig } = props;
    const text = asString(value);
    const hasExpressionSyntax = /\{\{.+?}}/.test(text);
    const [isExpressionMode, setIsExpressionMode] = useState<boolean>(() => hasExpressionSyntax);
    const isExpression = isExpressionMode || hasExpressionSyntax;
    const hasText = text.trim() !== '';

    const hasIncomingRun = incomingData !== undefined && incomingData !== null;
    const evaluation = isExpression && hasText && hasIncomingRun ? resolveExpression(incomingData, text) : null;
    const isValid = evaluation ? evaluation.found : true;

    const expressionStyles = isExpression && hasText
        ? (hasIncomingRun
            ? (isValid
                ? 'border-emerald-500/50! bg-emerald-500/10! focus:bg-emerald-500/10! text-emerald-400 font-mono focus:border-emerald-500!'
                : 'border-primary/50! bg-primary/10! focus:bg-primary/10! text-primary font-mono focus:border-primary!')
            : 'border-content-muted/30! bg-content-muted/10! focus:bg-content-muted/10! font-mono focus:border-content-muted/60!')
        : '';

    const handleDrop = (event: React.DragEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        event.preventDefault();
        const textToInsert = event.dataTransfer.getData('text/plain');
        if (!textToInsert) return;

        setIsExpressionMode(true);
        const target = event.currentTarget;
        const start = target.selectionStart ?? target.value.length;
        const end = target.selectionEnd ?? target.value.length;
        const current = target.value;
        onChange(current.substring(0, start) + textToInsert + current.substring(end));
    };

    const handleDragOver = (event: React.DragEvent) => {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
    };

    if (field.type === 'conditions') {
        return <ConditionsEditor value={value} onChange={onChange} incomingData={incomingData} />;
    }

    if (field.type === 'keyvalue') {
        return <KeyValueEditor value={value} onChange={onChange} />;
    }

    if (field.type === 'credential') {
        return (
            <CredentialSelect
                id={id}
                credentialType={field.credential_type ?? ''}
                value={value}
                onChange={onChange}
            />
        );
    }

    if (field.type === 'model_select') {
        const credentialId = fullConfig?.credential_id ? Number(fullConfig.credential_id) : null;
        return <ModelSelect credentialId={credentialId} value={value} onChange={(val) => onChange(val)} />;
    }

    if (field.type === 'multiselect') {
        return <MultiSelectField field={field} value={value} onChange={onChange} />;
    }

    if (field.type === 'password') {
        return (
            <PasswordField
                id={id}
                value={text}
                placeholder={field.placeholder}
                onChange={(val) => onChange(val)}
            />
        );
    }

    if (field.type === 'number') {
        return <NumberField {...props} />;
    }

    if (field.type === 'select') {
        return (
            <Select
                id={id}
                value={text}
                placeholder="انتخاب کنید"
                options={normalizeOptions(field.options)}
                onChange={(event) => onChange(event.target.value)}
            />
        );
    }

    const supportsExpressionToggle = field.type === 'text' || field.type === 'textarea' || field.type === 'code';
    const isMultiline = field.type === 'textarea' || field.type === 'code';

    return (
        <div className="space-y-1.5">
            {supportsExpressionToggle && (
                <div className="flex items-center justify-between">
                    <span className="text-[10px] text-content-muted">
                        {isExpression && hasText && hasIncomingRun && (
                            isValid
                                ? <span className="text-emerald-500 font-bold">معتبر</span>
                                : <span className="text-primary font-bold">نامعتبر</span>
                        )}
                    </span>

                    <div className="flex items-center rounded-lg border border-line-border bg-surface-card p-0.5 text-[10px] font-semibold">
                        <button
                            type="button"
                            onClick={() => setIsExpressionMode(false)}
                            className={`rounded-md px-2 py-0.5 transition ${
                                !isExpression
                                    ? 'bg-line-border text-content-main shadow-xs'
                                    : 'text-content-muted hover:text-content-main'
                            }`}
                        >
                            ثابت
                        </button>
                        <button
                            type="button"
                            onClick={() => setIsExpressionMode(true)}
                            className={`rounded-md px-2 py-0.5 transition ${
                                isExpression
                                    ? 'bg-primary text-white shadow-xs'
                                    : 'text-content-muted hover:text-content-main'
                            }`}
                        >
                            متغیر (fx)
                        </button>
                    </div>
                </div>
            )}

            <div className="relative">
                {isExpression && (
                    <span
                        className={`pointer-events-none absolute inset-y-0 left-0 z-10 flex ps-3 text-xs font-black italic leading-5 text-content-muted opacity-60 ${
                            isMultiline ? 'items-start pt-2' : 'items-center'
                        }`}
                        dir="ltr"
                    >
                        fx
                    </span>
                )}

                {field.type === 'textarea' ? (
                    <textarea
                        id={id}
                        rows={4}
                        autoComplete="off"
                        spellCheck={false}
                        value={text}
                        maxLength={10000}
                        placeholder={isExpression ? '{{ input.property }}' : field.placeholder}
                        dir={isExpression && hasText ? 'ltr' : textDir(text, field.placeholder)}
                        onChange={(event) => onChange(event.target.value)}
                        onDragOver={handleDragOver}
                        onDrop={handleDrop}
                        className={`${inputClass} resize-none ${isExpression ? 'pl-9 text-left' : ''} ${expressionStyles}`}
                    />
                ) : field.type === 'code' ? (
                    <textarea
                        id={id}
                        rows={8}
                        dir="ltr"
                        autoComplete="off"
                        spellCheck={false}
                        value={text}
                        maxLength={10000}
                        placeholder={field.placeholder}
                        onChange={(event) => onChange(event.target.value)}
                        onDragOver={handleDragOver}
                        onDrop={handleDrop}
                        className={`${inputClass} resize-none font-mono text-xs ${isExpression ? 'pl-9 text-left' : ''} ${expressionStyles}`}
                    />
                ) : (
                    <input
                        id={id}
                        type="text"
                        autoComplete="off"
                        spellCheck={false}
                        value={text}
                        maxLength={2000}
                        placeholder={isExpression ? '{{ input.property }}' : field.placeholder}
                        dir={isExpression && hasText ? 'ltr' : textDir(text, field.placeholder)}
                        onChange={(event) => onChange(event.target.value)}
                        onDragOver={handleDragOver}
                        onDrop={handleDrop}
                        className={`${inputClass} ${isExpression ? 'pl-9 text-left' : ''} ${expressionStyles}`}
                    />
                )}
            </div>
        </div>
    );
}

import { useState, type ReactNode } from 'react';
import { Icon } from '@canvas/components/ui/Icon';

const RTL_REGEX = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/;

type MarkdownRendererProps = {
    content: string;
    className?: string;
};

function CodeBlock({ code, language }: { code: string; language?: string }) {
    const [copied, setCopied] = useState(false);

    const handleCopy = () => {
        navigator.clipboard.writeText(code);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <div className="my-2.5 overflow-hidden rounded-xl border border-line-border bg-surface-card" dir="ltr">
            <div className="flex h-8 items-center justify-between border-b border-line-border/60 bg-surface-raised px-3 text-[11px] text-content-muted">
                <span className="font-mono uppercase">{language || 'code'}</span>
                <button
                    type="button"
                    onClick={handleCopy}
                    className="flex items-center gap-1 text-[11px] text-content-muted transition hover:text-content-main"
                >
                    <Icon name={copied ? 'check' : 'copy'} className="size-3.5" />
                    <span>{copied ? 'کپی شد' : 'کپی'}</span>
                </button>
            </div>
            <pre className="custom-scrollbar overflow-x-auto p-3 font-mono text-[11px] leading-5 text-content-main select-text">
                <code>{code}</code>
            </pre>
        </div>
    );
}

function parseInline(text: string): ReactNode[] {
    const regex = /(\[.*?\]\(.*?\)|\*\*.*?\*\*|\*.*?\*|`.*?`|\$.*?\$)/g;
    const parts = text.split(regex);

    return parts.map((part, index) => {
        if (part.startsWith('[') && part.includes('](') && part.endsWith(')')) {
            const match = part.match(/^\[(.*?)\]\((.*?)\)$/);
            if (match) {
                return (
                    <a
                        key={index}
                        href={match[2]}
                        target="_blank"
                        rel="noopener noreferrer"
                        onClick={(e) => e.stopPropagation()}
                        className="text-primary underline underline-offset-4 transition hover:opacity-80"
                    >
                        {match[1]}
                    </a>
                );
            }
        }

        if (part.startsWith('**') && part.endsWith('**') && part.length >= 4) {
            return (
                <strong key={index} className="font-bold text-content-main">
                    {part.slice(2, -2)}
                </strong>
            );
        }

        if (part.startsWith('*') && part.endsWith('*') && part.length >= 2) {
            return (
                <em key={index} className="italic text-content-main">
                    {part.slice(1, -1)}
                </em>
            );
        }

        if (part.startsWith('`') && part.endsWith('`') && part.length >= 2) {
            return (
                <code
                    key={index}
                    className="mx-0.5 rounded-md border border-line-border bg-surface-raised px-1.5 py-0.5 font-mono text-[11px] text-primary"
                    dir="ltr"
                >
                    {part.slice(1, -1)}
                </code>
            );
        }

        if (part.startsWith('$') && part.endsWith('$') && part.length >= 2) {
            return (
                <span
                    key={index}
                    className="mx-1 inline-block rounded-md bg-purple-500/10 px-1.5 py-0.5 font-mono text-[11px] text-purple-400"
                    dir="ltr"
                >
                    {part.slice(1, -1)}
                </span>
            );
        }

        return part;
    });
}

export function MarkdownRenderer({ content, className = '' }: MarkdownRendererProps) {
    if (!content.trim()) {
        return null;
    }

    const lines = content.split('\n');
    const elements: ReactNode[] = [];
    let inCodeBlock = false;
    let codeBlockLang = '';
    let codeBlockLines: string[] = [];

    lines.forEach((line, index) => {
        const trimmed = line.trim();

        if (trimmed.startsWith('```')) {
            if (inCodeBlock) {
                elements.push(
                    <CodeBlock
                        key={`code-${index}`}
                        code={codeBlockLines.join('\n')}
                        language={codeBlockLang}
                    />
                );
                inCodeBlock = false;
                codeBlockLang = '';
                codeBlockLines = [];
            } else {
                inCodeBlock = true;
                codeBlockLang = trimmed.substring(3).trim();
                codeBlockLines = [];
            }
            return;
        }

        if (inCodeBlock) {
            codeBlockLines.push(line);
            return;
        }

        if (!trimmed) {
            elements.push(<div key={`spacer-${index}`} className="h-2" />);
            return;
        }

        if (trimmed === '---' || trimmed === '***') {
            elements.push(<hr key={`hr-${index}`} className="my-2 border-line-border/60" />);
            return;
        }

        if (trimmed.startsWith('# ')) {
            elements.push(
                <h1 key={`h1-${index}`} className="my-1.5 text-base font-black text-content-main">
                    {parseInline(trimmed.substring(2))}
                </h1>
            );
            return;
        }

        if (trimmed.startsWith('## ')) {
            elements.push(
                <h2 key={`h2-${index}`} className="my-1.5 text-sm font-bold text-content-main">
                    {parseInline(trimmed.substring(3))}
                </h2>
            );
            return;
        }

        if (trimmed.startsWith('### ')) {
            elements.push(
                <h3 key={`h3-${index}`} className="my-1 text-xs font-semibold text-content-main">
                    {parseInline(trimmed.substring(4))}
                </h3>
            );
            return;
        }

        if (trimmed.startsWith('> ')) {
            elements.push(
                <blockquote
                    key={`quote-${index}`}
                    className="my-1.5 border-s-2 border-primary ps-3 italic text-content-muted"
                >
                    {parseInline(trimmed.substring(2))}
                </blockquote>
            );
            return;
        }

        if (trimmed.startsWith('- ') || trimmed.startsWith('* ')) {
            elements.push(
                <div key={`li-${index}`} className="flex items-start gap-2 ps-1.5 leading-6">
                    <span className="mt-2.5 size-1.5 shrink-0 rounded-full bg-primary" />
                    <span className="flex-1">{parseInline(trimmed.substring(2))}</span>
                </div>
            );
            return;
        }

        const numMatch = trimmed.match(/^([0-9\u06F0-\u06F9]+)[.)]\s+(.*)/);
        if (numMatch) {
            elements.push(
                <div key={`num-${index}`} className="flex items-start gap-2 ps-1.5 leading-6">
                    <span className="shrink-0 font-mono text-[11px] font-bold text-purple-400">
                        {numMatch[1]}.
                    </span>
                    <span className="flex-1">{parseInline(numMatch[2])}</span>
                </div>
            );
            return;
        }

        const isLineRtl = RTL_REGEX.test(line);

        elements.push(
            <p key={`p-${index}`} dir={isLineRtl ? 'rtl' : 'ltr'} className="leading-6">
                {parseInline(line)}
            </p>
        );
    });

    if (inCodeBlock && codeBlockLines.length > 0) {
        elements.push(
            <CodeBlock
                key="code-unfinished"
                code={codeBlockLines.join('\n')}
                language={codeBlockLang}
            />
        );
    }

    const isOverallRtl = RTL_REGEX.test(content);

    return (
        <div
            dir={isOverallRtl ? 'rtl' : 'ltr'}
            className={`space-y-1 select-text text-start text-xs text-content-main ${className}`}
        >
            {elements}
        </div>
    );
}

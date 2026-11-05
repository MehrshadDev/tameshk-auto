<?php

namespace App\Tickets\Support;

use Illuminate\Support\Str;

final class TicketMarkdown
{
    public static function render(string $text): string
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return '';
        }

        $codeBlocks = [];

        $protected = preg_replace_callback(
            '/```([a-zA-Z0-9_-]*)[ \t]*\r?\n?([\s\S]*?)```/',
            function (array $matches) use (&$codeBlocks): string {
                $placeholder = '@@CODEBLOCK_' . count($codeBlocks) . '@@';
                $lang = strtolower(trim($matches[1]));
                $code = trim($matches[2], "\r\n");
                $codeBlocks[$placeholder] = self::renderCodeBlock($code, $lang);

                return "\n\n" . $placeholder . "\n\n";
            },
            $trimmed
        ) ?? $trimmed;

        $html = Str::markdown($protected, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'renderer' => [
                'soft_break' => "<br />\n",
            ],
        ]);

        $html = preg_replace_callback(
            '/<a\s+href="([^"]+)"[^>]*>(.*?)<\/a>/is',
            function (array $m): string {
                $href = $m[1];
                if (! preg_match('/^https?:\/\//i', $href) && ! str_starts_with($href, 'mailto:')) {
                    return $m[2];
                }

                return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer nofollow" dir="ltr" style="unicode-bidi: isolate;" class="inline-block font-semibold text-primary underline decoration-primary/50 underline-offset-4 transition hover:opacity-80">' . $m[2] . '</a>';
            },
            $html
        ) ?? $html;

        $html = preg_replace(
            '/<code>(.*?)<\/code>/is',
            '<code dir="ltr" style="unicode-bidi: isolate;" class="mx-0.5 inline-block rounded-lg border border-line-border bg-surface-base px-1.5 py-0.5 font-mono text-[11px] leading-normal font-semibold text-primary">$1</code>',
            $html
        ) ?? $html;

        $html = preg_replace(
            '/<(p|li|blockquote|h[1-6])>/i',
            '<$1 dir="auto" style="unicode-bidi: plaintext;" class="text-start">',
            $html
        ) ?? $html;

        foreach ($codeBlocks as $placeholder => $renderedBlock) {
            $html = preg_replace(
                '/<p[^>]*>\s*' . preg_quote($placeholder, '/') . '\s*<\/p>/i',
                $renderedBlock,
                $html
            ) ?? $html;
            $html = str_replace($placeholder, $renderedBlock, $html);
        }

        return $html;
    }

    private static function renderCodeBlock(string $rawCode, string $lang): string
    {
        $label = $lang !== '' ? strtoupper($lang) : 'CODE';
        $highlighted = self::highlightCode($rawCode);

        return <<<HTML
<div x-data="{ copied: false }" dir="ltr" style="unicode-bidi: isolate;" class="my-3 overflow-hidden rounded-2xl border border-line-border bg-surface-base text-left leading-normal shadow-2xs">
    <div class="flex h-9 items-center justify-between border-b border-line-border/80 bg-surface-raised px-3.5 leading-none">
        <span class="font-mono text-[11px] font-bold tracking-wide text-content-muted">{$label}</span>
        <button type="button"
                dir="rtl"
                @click="navigator.clipboard.writeText(\$refs.code.textContent); copied = true; setTimeout(() => copied = false, 2000)"
                class="inline-flex h-6 items-center justify-center rounded-lg border border-line-border/80 bg-surface-card px-2.5 font-sans text-[11px] leading-none font-semibold text-content-muted transition hover:border-primary/40 hover:text-content-main cursor-pointer">
            <span x-text="copied ? 'کپی شد ✓' : 'کپی'">کپی</span>
        </button>
    </div>
    <pre dir="ltr" class="custom-scrollbar overflow-x-auto p-3.5 text-left font-mono text-xs leading-6 text-content-main select-all"><code x-ref="code" class="docs-block-code">{$highlighted}</code></pre>
</div>
HTML;
    }

    private static function highlightCode(string $rawCode): string
    {
        $escaped = htmlspecialchars($rawCode, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $tokens = [];

        $escaped = preg_replace_callback(
            '/("(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\')/',
            function (array $m) use (&$tokens): string {
                $key = '@@TOK_' . count($tokens) . '@@';
                $tokens[$key] = '<span class="text-emerald-600 dark:text-emerald-400">' . $m[0] . '</span>';

                return $key;
            },
            $escaped
        ) ?? $escaped;

        $escaped = preg_replace(
            '/\b(function|return|if|else|elseif|foreach|for|while|match|switch|case|default|break|continue|class|interface|trait|enum|final|public|private|protected|static|readonly|new|try|catch|finally|throw|use|namespace|const|let|var|async|await|import|export|from|print|echo|def|true|false|null)\b/',
            '<span class="font-bold text-primary">$1</span>',
            $escaped
        ) ?? $escaped;

        $escaped = preg_replace(
            '/\b(\d+(?:\.\d+)?)\b/',
            '<span class="text-amber-600 dark:text-amber-400">$1</span>',
            $escaped
        ) ?? $escaped;

        return strtr($escaped, $tokens);
    }
}

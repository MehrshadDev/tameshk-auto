@props([
    'label' => 'نمونه',
    'lines' => [],
])

@php
    $openBrace = '{' . '{ ';
    $closeBrace = ' }' . '}';

    $normalizedLines = array_map(function (string $line) use ($openBrace, $closeBrace) {
        if (str_starts_with($line, 'fx:')) {
            return $openBrace . trim(substr($line, 3)) . $closeBrace;
        }

        return preg_replace_callback('/\[\[\s*(.+?)\s*\]\]/', fn ($m) => $openBrace . $m[1] . $closeBrace, $line);
    }, $lines);

    $content = ! empty($normalizedLines)
        ? implode("\n", $normalizedLines)
        : trim((string) $slot);

    $isRtlLabel = (bool) preg_match('/[\x{0600}-\x{06FF}]/u', $label);
@endphp

<div x-data="{
         copied: false,
         copy() {
             const text = this.$refs.codeBlock.textContent;
             navigator.clipboard.writeText(text);
             this.copied = true;
             setTimeout(() => this.copied = false, 2000);
         }
     }"
     class="my-4 overflow-hidden rounded-2xl border border-line-border bg-surface-base shadow-2xs">

    <div dir="{{ $isRtlLabel ? 'rtl' : 'ltr' }}"
         class="flex h-10 items-center justify-between border-b border-line-border/80 bg-surface-raised px-4 text-xs text-content-muted">
        <span class="{{ $isRtlLabel ? 'font-sans font-bold text-content-main' : 'font-mono font-semibold text-content-main' }}">
            {{ $label }}
        </span>

        <button type="button"
                dir="rtl"
                @click="copy()"
                class="inline-flex items-center gap-1.5 rounded-lg border border-line-border/80 bg-surface-card px-2.5 py-1 text-[11px] font-semibold text-content-muted transition hover:border-primary/40 hover:text-content-main cursor-pointer">
            <x-ui.icon name="copy" x-show="!copied" class="size-3.5"/>
            <x-ui.icon name="check" x-show="copied" x-cloak class="size-3.5 text-emerald-500"/>
            <span x-text="copied ? 'کپی شد' : 'کپی'">کپی</span>
        </button>
    </div>

    <pre dir="ltr" class="custom-scrollbar overflow-x-auto p-4 text-left font-mono text-xs leading-6 text-content-main select-all"><code x-ref="codeBlock" class="docs-block-code">{{ $content }}</code></pre>
</div>

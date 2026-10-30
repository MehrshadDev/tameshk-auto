@props([
    'name',
    'type' => 'text',
    'label' => null,
    'placeholder' => '',
    'smartDir' => true,
    'dir' => null,
])

@php
    $isPassword = $type === 'password';

    $baseClasses = 'autofill-reset w-full px-4 py-2.5 rounded-xl bg-surface-base border border-line-border text-content-main placeholder:text-content-muted/60 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition';

    if ($isPassword) {
        $baseClasses .= ' pl-12';
    }
@endphp

<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-content-main mb-1.5">
            {{ $label }}
        </label>
    @endif

    <div
        x-data="{
            dir: @js($dir ?? 'ltr'),
            visible: false,
            smartDir: @js($smartDir),
            isRTL(text) {
                return /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/.test(text);
            },
            update() {
                if (!this.smartDir) return;

                const input = this.$refs.input;
                const text = input.value || input.placeholder || '';

                this.dir = this.isRTL(text) ? 'rtl' : 'ltr';
            }
        }"
        x-init="update()"
        class="relative"
    >
        <input
            x-ref="input"
            id="{{ $name }}"
            :type="visible ? 'text' : @js($type)"
            :dir="dir"
            @input="update()"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            {{ $attributes->merge(['class' => $baseClasses]) }}
        >

        @if ($isPassword)
            <button
                type="button"
                @click="visible = !visible"
                tabindex="-1"
                :aria-label="visible ? 'مخفی کردن رمز' : 'نمایش رمز'"
                class="absolute inset-y-0 left-3 flex items-center text-content-muted hover:text-content-main transition"
            >
                <x-ui.icon name="eye" x-show="!visible"/>
                <x-ui.icon name="eye-closed" x-show="visible" x-cloak/>
            </button>
        @endif
    </div>

    @error($name)
    <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
    @enderror
</div>

@once
    <style>
        input.autofill-reset:-webkit-autofill,
        input.autofill-reset:-webkit-autofill:hover,
        input.autofill-reset:-webkit-autofill:focus,
        input.autofill-reset:-webkit-autofill:active {
            background-color: var(--color-surface-base) !important;
            background-image: none !important;
            -webkit-text-fill-color: var(--color-content-main) !important;
            color: var(--color-content-main) !important;
            caret-color: var(--color-content-main) !important;

            -webkit-box-shadow:
                inset 0 0 0 1000px var(--color-surface-base),
                var(--tw-ring-offset-shadow, 0 0 #0000),
                var(--tw-ring-shadow, 0 0 #0000),
                var(--tw-shadow, 0 0 #0000) !important;

            box-shadow:
                inset 0 0 0 1000px var(--color-surface-base),
                var(--tw-inset-shadow, 0 0 #0000),
                var(--tw-inset-ring-shadow, 0 0 #0000),
                var(--tw-ring-offset-shadow, 0 0 #0000),
                var(--tw-ring-shadow, 0 0 #0000),
                var(--tw-shadow, 0 0 #0000) !important;
        }

        input.autofill-reset:autofill {
            background-color: var(--color-surface-base) !important;
            background-image: none !important;
            color: var(--color-content-main) !important;
            caret-color: var(--color-content-main) !important;
            box-shadow:
                inset 0 0 0 1000px var(--color-surface-base),
                var(--tw-inset-shadow, 0 0 #0000),
                var(--tw-inset-ring-shadow, 0 0 #0000),
                var(--tw-ring-offset-shadow, 0 0 #0000),
                var(--tw-ring-shadow, 0 0 #0000),
                var(--tw-shadow, 0 0 #0000) !important;
            -webkit-text-fill-color: var(--color-content-main) !important;
        }
    </style>
@endonce

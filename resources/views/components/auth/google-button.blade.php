@props(['label' => 'ورود با حساب گوگل'])

<a href="{{ route('auth.google.redirect') }}"
   class="flex w-full items-center justify-center gap-3 rounded-xl border border-line-border bg-surface-card py-2.5 px-4 text-xs font-bold text-content-main shadow-2xs transition hover:border-line-border/90 hover:bg-surface-raised cursor-pointer">
    <x-ui.icon name="brand:google" class="size-5 shrink-0"/>
    <span>{{ $label }}</span>
</a>

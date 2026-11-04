<footer class="relative w-full border-t border-line-border bg-surface-raised/85 font-vazirmatn-rd backdrop-blur-xl">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-primary/35 to-transparent"></div>

    <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-6 px-6 py-8 sm:flex-row">
        <div class="flex flex-col items-center gap-2 sm:items-start">
            <x-layouts.logo />

            <p class="text-sm text-content-muted">
                ساخته شده با ❤️ برای تیم‌های ایرانی
            </p>
        </div>

        <nav aria-label="پیوندهای مرتبط" class="flex items-center gap-6 text-sm text-content-muted">
            <a href="#" class="transition-colors hover:text-primary">
                گیت‌هاب
            </a>

            <a href="#" class="transition-colors hover:text-primary">
                لینکدین
            </a>

            <a href="{{ route('docs.index') }}" class="transition-colors hover:text-primary">
                مستندات
            </a>
        </nav>
    </div>

    <div class="border-t border-line-border/70 bg-surface-card/40 py-4">
        <p class="text-center text-xs text-content-muted">
            © {{ date('Y') }} تمشک — تمامی حقوق محفوظ است.
        </p>
    </div>
</footer>

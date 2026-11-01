<x-ui.modal name="confirm-logout" title="خروج از حساب کاربری" maxWidth="sm">
    <div class="flex items-start gap-3.5">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <x-ui.icon name="logout" class="size-5"/>
        </div>
        <div class="space-y-1">
            <p class="text-sm font-bold text-content-main">آیا می‌خواهید از حساب خود خارج شوید؟</p>
            <p class="text-xs leading-6 text-content-muted">
                با خروج از حساب، برای دسترسی مجدد به پنل مدیریت تغییر و ویرایش ورکفلوها نیاز به ورود دوباره خواهید داشت.
            </p>
        </div>
    </div>

    <x-slot:footer>
        <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'confirm-logout')" class="cursor-pointer">
            انصراف
        </x-ui.button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button type="submit" class="cursor-pointer">
                <x-ui.icon name="logout" class="size-4"/>
                بله، خارج شو
            </x-ui.button>
        </form>
    </x-slot:footer>
</x-ui.modal>

<x-layouts.dashboard
    :title="'تاریخچه اجراها - ' . config('app.name')"
    heading="تاریخچه اجراها"
    subheading="گزارش کامل، زمان‌بندی و بررسی گام‌به‌گام اجرای نودها در ورکفلوها">
    <livewire:runs.run-list />
</x-layouts.dashboard>

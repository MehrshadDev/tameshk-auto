@props([
    'deletingName' => null,
])

<x-ui.modal name="edit-workflow" title="ویرایش مشخصات ورکفلو">
    <form wire:submit="saveEdit" class="space-y-4">
        <x-ui.input name="editName" label="نام ورکفلو" wire:model="editName" maxlength="100" placeholder="مثلاً: دستیار هوشمند تلگرام"/>

        <div>
            <label for="editDescription" class="mb-1.5 block text-sm font-medium text-content-main">توضیحات (اختیاری)</label>
            <textarea id="editDescription"
                      rows="3"
                      maxlength="500"
                      wire:model="editDescription"
                      placeholder="توضیح کوتاهی درباره عملکرد این ورکفلو بنویسید..."
                      class="w-full resize-none rounded-xl border border-line-border bg-surface-base px-4 py-2.5 text-sm text-content-main transition placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/40"></textarea>
            @error('editDescription')
            <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
            <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'edit-workflow')" class="cursor-pointer">انصراف</x-ui.button>
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="saveEdit" class="cursor-pointer">ذخیره تغییرات</x-ui.button>
        </div>
    </form>
</x-ui.modal>

<x-ui.modal name="delete-workflow" title="حذف ورکفلو" maxWidth="sm">
    <div class="flex items-start gap-3.5">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <x-ui.icon name="trash" class="size-5"/>
        </div>
        <p class="text-xs leading-6 text-content-muted">
            ورکفلوی
            <span class="wrap-break-word font-bold text-content-main">«{{ \Illuminate\Support\Str::limit($deletingName, 60) }}»</span>
            حذف می‌شود و در صورت فعال بودن، وب‌هوک ها و زمان‌بندی‌های آن متوقف خواهد شد. آیا مطمئن هستید؟
        </p>
    </div>

    <x-slot:footer>
        <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'delete-workflow')" class="cursor-pointer">انصراف</x-ui.button>
        <x-ui.button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete" class="cursor-pointer">بله، حذف کن</x-ui.button>
    </x-slot:footer>
</x-ui.modal>

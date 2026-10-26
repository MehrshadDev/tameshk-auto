<div x-data="{
         items: [],
         push(detail) {
             const id = Date.now() + Math.random();
             this.items.push({ id, message: detail.message, type: detail.type ?? 'success' });
             setTimeout(() => this.items = this.items.filter(item => item.id !== id), 3500);
         }
     }"
     @notify.window="push($event.detail)"
     class="pointer-events-none fixed bottom-4 left-4 z-60 flex flex-col gap-2">
    <template x-for="item in items" :key="item.id">
        <div class="pointer-events-auto flex max-w-sm items-center gap-2.5 rounded-xl border border-line-border bg-surface-raised px-4 py-3 text-sm shadow-xl">
            <span class="size-2 shrink-0 rounded-full" :class="item.type === 'error' ? 'bg-primary' : 'bg-emerald-500'"></span>
            <span x-text="item.message"></span>
        </div>
    </template>
</div>

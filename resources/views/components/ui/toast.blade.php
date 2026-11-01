<div x-data="{
         items: [],
         push(detail) {
             const id = Date.now() + Math.random();
             this.items.push({ id, message: detail.message, type: detail.type ?? 'success' });
             setTimeout(() => this.remove(id), 4000);
         },
         remove(id) {
             this.items = this.items.filter(item => item.id !== id);
         }
     }"
     @notify.window="push($event.detail)"
     class="pointer-events-none fixed bottom-5 left-5 z-60 flex w-88 max-w-[calc(100vw-2.5rem)] flex-col gap-2.5">
    <template x-for="item in items" :key="item.id">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-2 scale-95"
             class="pointer-events-auto flex items-center gap-3 rounded-2xl border border-line-border bg-surface-card/95 p-3.5 shadow-2xl backdrop-blur-md">
            <div class="flex size-8 shrink-0 items-center justify-center rounded-xl"
                 :class="item.type === 'error' ? 'bg-primary/10 text-primary' : 'bg-emerald-500/10 text-emerald-500'">
                <x-ui.icon name="alert-triangle" class="size-4.5" x-show="item.type === 'error'"/>
                <x-ui.icon name="check" class="size-4.5" x-show="item.type !== 'error'"/>
            </div>

            <p class="min-w-0 flex-1 text-xs font-semibold leading-5 text-content-main wrap-break-word" x-text="item.message"></p>

            <button type="button"
                    @click="remove(item.id)"
                    aria-label="بستن"
                    class="flex size-6 shrink-0 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main cursor-pointer">
                <x-ui.icon name="x" class="size-3.5"/>
            </button>
        </div>
    </template>
</div>

@php
    $sessionError = session('auth_error') ?? session('error');
@endphp

<div x-data="{
         show: @js(! empty($sessionError)),
         message: @js($sessionError ?? ''),
         timer: null,
         trigger(msg) {
             this.message = msg;
             this.show = true;
             if (this.timer) clearTimeout(this.timer);
             this.timer = setTimeout(() => {
                 this.show = false;
             }, 7000);
         },
         init() {
             if (this.show) {
                 this.timer = setTimeout(() => {
                     this.show = false;
                 }, 7000);
             }
         }
     }"
     x-on:auth-error.window="trigger($event.detail.message)"
     x-show="show"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
     x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
     class="mb-4 flex items-start gap-2.5 rounded-xl border border-primary/25 bg-primary/10 p-3 text-start text-xs font-medium text-primary">

    <x-ui.icon name="alert-triangle" class="size-4.5 shrink-0"/>

    <p class="min-w-0 flex-1 leading-5" x-text="message"></p>

    <button type="button"
            @click="show = false"
            aria-label="بستن"
            class="flex size-5 shrink-0 items-center justify-center rounded-lg text-primary/70 transition hover:bg-primary/15 hover:text-primary cursor-pointer">
        <x-ui.icon name="x" class="size-3.5"/>
    </button>
</div>

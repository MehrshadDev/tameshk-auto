@php
    $user = auth()->user();
    $avatarUrl = $user?->avatarUrl();
@endphp

@if ($user)
    <x-ui.modal name="edit-profile-modal" title="ویرایش مشخصات کاربری" maxWidth="sm">
        <form action="{{ route('app.api.profile.update') }}"
              data-initial-name="{{ $user->name }}"
              x-data="{
                  name: '',
                  saving: false,
                  error: '',
                  init() {
                      this.name = $store.userProfile?.name || this.$el.dataset.initialName || '';
                  },
                  async submitProfile(event) {
                      const trimmed = this.name.trim();
                      this.error = '';

                      if (trimmed.length < 2 || trimmed.length > 50) {
                          this.error = 'نام کاربری باید بین ۲ تا ۵۰ کاراکتر باشد.';
                          return;
                      }

                      this.saving = true;

                      try {
                          const response = await fetch(event.currentTarget.action, {
                              method: 'PATCH',
                              headers: {
                                  'Content-Type': 'application/json',
                                  'Accept': 'application/json',
                                  'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? ''
                              },
                              body: JSON.stringify({ name: trimmed })
                          });

                          const data = await response.json().catch(() => ({}));

                          if (response.ok && data.name) {
                              if ($store.userProfile) {
                                  $store.userProfile.name = data.name;
                              }
                              this.name = data.name;
                              this.$dispatch('close-modal', 'edit-profile-modal');
                              window.dispatchEvent(new CustomEvent('notify', {
                                  detail: { message: 'نام کاربری با موفقیت بروزرسانی شد.', type: 'success' }
                              }));
                          } else {
                              this.error = data.message || 'نام واردشده معتبر نیست.';
                          }
                      } catch {
                          this.error = 'خطا در برقراری ارتباط با سرور.';
                      } finally {
                          this.saving = false;
                      }
                  }
              }"
              @open-modal.window="if (($event.detail?.name ?? $event.detail) === 'edit-profile-modal') { name = $store.userProfile?.name || $el.dataset.initialName || ''; error = ''; }"
              @submit.prevent="submitProfile($event)"
              class="space-y-4">

            <div class="flex items-center gap-3.5 rounded-2xl border border-line-border/80 bg-surface-card p-3.5">
                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="size-11 shrink-0 rounded-xl object-cover ring-1 ring-line-border">
                @else
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary text-sm font-bold text-white shadow-xs shadow-primary/25"
                          x-text="(name || 'U').slice(0, 1).toUpperCase()">
                        {{ mb_substr($user->name, 0, 1) }}
                    </span>
                @endif

                <div class="min-w-0 flex-1">
                    <p dir="auto" class="truncate text-start text-sm font-bold text-content-main" x-text="name || $el.dataset.initialName">{{ $user->name }}</p>
                    <p dir="ltr" class="mt-0.5 truncate text-left font-mono text-[11px] text-content-muted">{{ $user->email }}</p>
                </div>
            </div>

            <div>
                <label for="profile_name_input" class="mb-1.5 block text-xs font-bold text-content-main">
                    نام نمایشی
                </label>
                <input id="profile_name_input"
                       type="text"
                       dir="auto"
                       maxlength="50"
                       x-model="name"
                       :disabled="saving"
                       placeholder="نام خود را وارد کنید"
                       class="w-full rounded-xl border border-line-border bg-surface-base px-3.5 py-2.5 text-start text-sm font-medium text-content-main transition placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"/>
                <template x-if="error">
                    <p class="mt-1.5 text-xs font-medium text-primary" x-text="error"></p>
                </template>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-line-border/60 pt-4">
                <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'edit-profile-modal')" class="cursor-pointer">
                    انصراف
                </x-ui.button>
                <x-ui.button type="submit" x-bind:disabled="saving" class="cursor-pointer">
                    <span x-show="!saving">ذخیره تغییرات</span>
                    <span x-show="saving" x-cloak>در حال ذخیره...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
@endif

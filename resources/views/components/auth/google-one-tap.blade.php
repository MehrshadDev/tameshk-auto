@php
    $clientId = config('services.google.client_id');
@endphp

@if (! empty($clientId))
    <div x-data="{
             init() {
                 if (window.google?.accounts?.id) {
                     this.setup();
                     return;
                 }

                 const script = document.createElement('script');
                 script.src = 'https://accounts.google.com/gsi/client';
                 script.async = true;
                 script.defer = true;
                 script.onload = () => this.setup();
                 document.head.appendChild(script);
             },
             setup() {
                 window.google.accounts.id.initialize({
                     client_id: '{{ $clientId }}',
                     callback: (res) => this.handleCallback(res),
                     auto_select: false,
                     cancel_on_tap_outside: true,
                 });

                 window.google.accounts.id.prompt();
             },
             async handleCallback(response) {
                 if (! response.credential) return;

                 try {
                     const res = await fetch('{{ route('auth.google.one-tap') }}', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'Accept': 'application/json',
                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? ''
                         },
                         body: JSON.stringify({ credential: response.credential })
                     });

                     const data = await res.json().catch(() => ({}));

                     if (res.ok && data.redirect) {
                         window.location.href = data.redirect;
                         return;
                     }

                     window.dispatchEvent(new CustomEvent('auth-error', {
                         detail: { message: data.message || 'خطا در ورود با گوگل.' }
                     }));
                 } catch {
                     window.dispatchEvent(new CustomEvent('auth-error', {
                         detail: { message: 'خطا در ارتباط با سرور گوگل. لطفاً دوباره تلاش کنید.' }
                     }));
                 }
             }
         }"
         class="hidden"></div>
@endif

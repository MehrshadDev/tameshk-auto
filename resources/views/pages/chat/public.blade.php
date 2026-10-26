<x-layouts.chat :title="$title">
    <div class="flex h-full flex-col">
        <header class="shrink-0 border-b border-line-border bg-surface-raised">
            <div class="mx-auto flex h-14 w-full max-w-4xl items-center px-4 sm:h-16 sm:px-6">
                <h1 class="truncate text-sm font-bold sm:text-base">{{ $title }}</h1>
            </div>
        </header>

        <div id="messages" class="custom-scrollbar min-h-0 flex-1 overflow-y-auto">
            <div id="thread" class="mx-auto flex w-full max-w-4xl flex-col gap-3 px-4 py-5 sm:px-6 sm:py-8"></div>
        </div>

        <footer class="shrink-0 border-t border-line-border bg-surface-raised pb-[env(safe-area-inset-bottom)]">
            <form id="composer" class="mx-auto flex w-full max-w-4xl items-end gap-2 px-3 py-3 sm:gap-3 sm:px-6 sm:py-4">
                <textarea id="input"
                          rows="1"
                          maxlength="3000"
                          autocomplete="off"
                          placeholder="{{ $placeholder }}"
                          class="custom-scrollbar min-w-0 flex-1 resize-none overflow-hidden rounded-2xl border border-line-border bg-surface-card px-4 py-3 text-base leading-6 text-content-main transition placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:opacity-50"></textarea>

                <button id="send"
                        type="submit"
                        aria-label="ارسال"
                        class="flex size-12.5 shrink-0 items-center justify-center rounded-2xl bg-primary text-white transition hover:bg-primary/90 active:scale-95 disabled:opacity-50">
                    <span class="inline-block size-5 -scale-x-100 bg-current"
                          style="-webkit-mask: url('{{ asset('icons/tabler/send.svg') }}') center / contain no-repeat; mask: url('{{ asset('icons/tabler/send.svg') }}') center / contain no-repeat;"></span>
                </button>
            </form>
        </footer>
    </div>

    <script>
        (() => {
            const cfg = @json($config);
            const scroller = document.getElementById('messages');
            const thread = document.getElementById('thread');
            const form = document.getElementById('composer');
            const input = document.getElementById('input');
            const send = document.getElementById('send');
            const storageKey = 'chat_session_' + cfg.token;
            const MAX_HEIGHT = 160;
            let busy = false;

            const newId = () =>
                crypto.randomUUID ? crypto.randomUUID() : Date.now() + '-' + Math.random().toString(16).slice(2);

            const readSession = () => {
                try {
                    let id = localStorage.getItem(storageKey);

                    if (!id) {
                        id = newId();
                        localStorage.setItem(storageKey, id);
                    }

                    return id;
                } catch {
                    return newId();
                }
            };

            const sessionId = readSession();

            const styles = {
                user: 'ms-auto bg-primary text-white',
                bot: 'me-auto border border-line-border bg-surface-raised text-content-main',
                error: 'me-auto border border-primary/20 bg-primary/10 text-primary',
                pending: 'me-auto border border-line-border bg-surface-raised text-content-muted',
            };

            const addBubble = (role, text) => {
                const el = document.createElement('div');

                el.dir = 'auto';
                el.className = 'w-fit max-w-[88%] whitespace-pre-wrap break-words rounded-2xl px-4 py-2.5 text-sm leading-7 sm:max-w-[75%] sm:text-base lg:max-w-[65%] ' + styles[role];
                el.textContent = text;
                thread.appendChild(el);
                scroller.scrollTop = scroller.scrollHeight;

                return el;
            };

            const setBusy = (value) => {
                busy = value;
                input.disabled = value;
                send.disabled = value;

                if (!value) {
                    input.focus();
                }
            };

            const resize = () => {
                input.style.height = 'auto';

                const border = input.offsetHeight - input.clientHeight;
                const full = input.scrollHeight + border;

                input.style.height = Math.min(full, MAX_HEIGHT) + 'px';
                input.style.overflowY = full > MAX_HEIGHT ? 'auto' : 'hidden';
            };

            const submit = async () => {
                const text = input.value.trim();

                if (!text || busy) {
                    return;
                }

                input.value = '';
                resize();
                addBubble('user', text);
                setBusy(true);

                const pending = addBubble('pending', cfg.pendingText);

                try {
                    const response = await fetch(cfg.endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                        body: JSON.stringify({ chatInput: text, sessionId }),
                    });

                    const data = await response.json().catch(() => ({}));

                    pending.remove();

                    if (!response.ok) {
                        addBubble('error', response.status === 429 ? cfg.rateLimitText : cfg.errorText);
                        return;
                    }

                    addBubble('bot', data.reply || cfg.emptyText);
                } catch {
                    pending.remove();
                    addBubble('error', cfg.errorText);
                } finally {
                    setBusy(false);
                }
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                submit();
            });

            input.addEventListener('input', resize);

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                    event.preventDefault();
                    submit();
                }
            });

            if (cfg.initialMessage) {
                addBubble('bot', cfg.initialMessage);
            }

            resize();
            input.focus();
        })();
    </script>
</x-layouts.chat>

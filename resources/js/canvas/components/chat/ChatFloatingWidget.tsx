import {useEffect, useRef, useState} from 'react';
import {Icon} from '@canvas/components/ui/Icon';
import {MarkdownRenderer} from '@canvas/components/ui/MarkdownRenderer';
import {useWorkflowStore} from '@canvas/store/useWorkflowStore';
import {asString} from '@canvas/lib/fields';
import {Tooltip} from "@canvas/components/ui/Tooltip";

type Role = 'user' | 'assistant' | 'error';

const MAX_TEXTAREA_HEIGHT = 112;
const PEEK_VISIBLE_MS = 6000;
const PEEK_FADE_MS = 600;
const PEEK_MAX_CHARS = 180;

const truncate = (text: string) => {
    const clean = text.trim();

    return clean.length > PEEK_MAX_CHARS ? `${clean.slice(0, PEEK_MAX_CHARS).trimEnd()}...` : clean;
};

const bubbleClass = (role: Role) => {
    if (role === 'user') {
        return 'ms-auto bg-primary text-white';
    }

    if (role === 'error') {
        return 'me-auto border border-primary/20 bg-primary/10 text-primary';
    }

    return 'me-auto border border-line-border bg-surface-card text-content-main';
};

const peekBubbleClass = (role: Role) => {
    if (role === 'error') {
        return 'border border-primary/30 bg-primary/15 text-primary';
    }

    return 'border border-line-border bg-surface-raised/90 text-content-main';
};

const resizeTextarea = (element: HTMLTextAreaElement) => {
    element.style.height = 'auto';
    const border = element.offsetHeight - element.clientHeight;
    const full = element.scrollHeight + border;
    element.style.height = `${Math.min(full, MAX_TEXTAREA_HEIGHT)}px`;
    element.style.overflowY = full > MAX_TEXTAREA_HEIGHT ? 'auto' : 'hidden';
};

export function ChatFloatingWidget() {
    const status = useWorkflowStore((state) => state.status);
    const nodes = useWorkflowStore((state) => state.nodes);
    const isChatOpen = useWorkflowStore((state) => state.isChatOpen);
    const toggleChat = useWorkflowStore((state) => state.toggleChat);
    const setChatOpen = useWorkflowStore((state) => state.setChatOpen);
    const chatMessages = useWorkflowStore((state) => state.chatMessages);
    const sendChatMessage = useWorkflowStore((state) => state.sendChatMessage);
    const clearChat = useWorkflowStore((state) => state.clearChat);
    const isRunning = useWorkflowStore((state) => state.isRunning);
    const cancelWorkflow = useWorkflowStore((state) => state.cancelWorkflow);

    const [draft, setDraft] = useState('');
    const [peekId, setPeekId] = useState<string | null>(null);
    const [peekLeaving, setPeekLeaving] = useState(false);
    const [hasUnread, setHasUnread] = useState(false);

    const scrollRef = useRef<HTMLDivElement>(null);
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const seenIdRef = useRef<string | null | undefined>(undefined);
    const hideTimerRef = useRef<number | null>(null);
    const removeTimerRef = useRef<number | null>(null);

    const chatTriggerNode = nodes.find((n) => n.data.nodeType === 'trigger.chat');
    const lastMessage = chatMessages[chatMessages.length - 1];

    const clearTimers = () => {
        if (hideTimerRef.current) window.clearTimeout(hideTimerRef.current);
        if (removeTimerRef.current) window.clearTimeout(removeTimerRef.current);
        hideTimerRef.current = null;
        removeTimerRef.current = null;
    };

    const scheduleHide = () => {
        clearTimers();
        hideTimerRef.current = window.setTimeout(() => {
            setPeekLeaving(true);
            removeTimerRef.current = window.setTimeout(() => setPeekId(null), PEEK_FADE_MS);
        }, PEEK_VISIBLE_MS);
    };

    const pausePeek = () => {
        clearTimers();
        setPeekLeaving(false);
    };

    useEffect(() => clearTimers, []);

    useEffect(() => {
        if (status !== 'ready') {
            return;
        }

        if (seenIdRef.current === undefined) {
            seenIdRef.current = lastMessage?.id ?? null;
            return;
        }

        if (isChatOpen) {
            seenIdRef.current = lastMessage?.id ?? null;
            clearTimers();
            setPeekId(null);
            setPeekLeaving(false);
            setHasUnread(false);
            return;
        }

        if (!lastMessage || seenIdRef.current === lastMessage.id) {
            return;
        }

        seenIdRef.current = lastMessage.id;

        if (lastMessage.role === 'user') {
            return;
        }

        setHasUnread(true);
        setPeekId(lastMessage.id);
        setPeekLeaving(false);
        scheduleHide();
    }, [status, isChatOpen, lastMessage?.id]);

    useEffect(() => {
        if (!isChatOpen) {
            return;
        }

        const scroll = () => {
            if (scrollRef.current) {
                scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
            }
        };

        scroll();
        const timer = window.setTimeout(scroll, 60);

        return () => window.clearTimeout(timer);
    }, [isChatOpen, chatMessages.length, isRunning]);

    if (!chatTriggerNode) {
        return null;
    }

    const config = chatTriggerNode.data.config ?? {};
    const title = asString(config.title) || 'گفتگوی تست';
    const placeholder = asString(config.input_placeholder) || 'پیام خود را بنویسید...';

    const peekMessage = peekId ? chatMessages.find((m) => m.id === peekId) : undefined;

    const handleSend = async () => {
        const text = draft.trim();

        if (!text || isRunning) {
            return;
        }

        setDraft('');

        if (textareaRef.current) {
            textareaRef.current.style.height = 'auto';
            textareaRef.current.style.overflowY = 'hidden';
        }

        await sendChatMessage(text);
    };

    const handleTextareaChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
        setDraft(e.target.value);
        resizeTextarea(e.target);
    };

    return (
        <div className="fixed bottom-5 left-34 z-20" dir="ltr">
            {!isChatOpen && peekMessage && (
                <div
                    dir="rtl"
                    onMouseEnter={pausePeek}
                    onMouseLeave={scheduleHide}
                    style={{transitionDuration: `${PEEK_FADE_MS}ms`}}
                    className={`pointer-events-none absolute bottom-14 left-0 w-80 pb-2 transition-opacity ${
                        peekLeaving ? 'opacity-0' : 'opacity-100'
                    }`}
                >
                    <div
                        key={peekMessage.id}
                        dir="auto"
                        onClick={() => setChatOpen(true)}
                        className={`pointer-events-auto cursor-pointer whitespace-pre-wrap wrap-break-word rounded-2xl px-4 py-3 text-xs leading-6 shadow-xl backdrop-blur-md animate-in fade-in slide-in-from-bottom-2 duration-200 ${peekBubbleClass(
                            peekMessage.role,
                        )}`}
                    >
                        {truncate(peekMessage.text)}
                    </div>
                </div>
            )}

            {isChatOpen && (
                <div
                    dir="rtl"
                    className="absolute bottom-14 left-0 flex h-130 w-96 max-w-[calc(100vw-6rem)] flex-col overflow-hidden rounded-3xl border border-line-border bg-surface-raised/95 shadow-2xl backdrop-blur-xl animate-in fade-in zoom-in-95 duration-150"
                >
                    <div
                        className="flex h-16 shrink-0 items-center justify-between border-b border-line-border/70 px-5">
                        <div className="flex min-w-0 items-center gap-3">
                            <div
                                className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
                                <Icon name="message-circle" className="size-5"/>
                            </div>
                            <div className="min-w-0 space-y-0.5">
                                <span className="block truncate text-xs font-bold leading-tight text-content-main">
                                    {title}
                                </span>
                                <span className="block text-[11px] leading-tight text-content-muted">
                                    محیط تست تعاملی
                                </span>
                            </div>
                        </div>

                        <div className="flex items-center gap-1.5">
                            <button
                                type="button"
                                onClick={() => void clearChat()}
                                disabled={isRunning}
                                title="پاک‌کردن تاریخچه"
                                className="flex size-8 items-center justify-center rounded-xl text-content-muted transition hover:bg-line-border/60 hover:text-content-main disabled:opacity-50"
                            >
                                <Icon name="trash" className="size-4"/>
                            </button>

                            <button
                                type="button"
                                onClick={() => setChatOpen(false)}
                                title="بستن"
                                className="flex size-8 items-center justify-center rounded-xl text-content-muted transition hover:bg-line-border/60 hover:text-content-main"
                            >
                                <Icon name="x" className="size-4"/>
                            </button>
                        </div>
                    </div>

                    <div ref={scrollRef} className="custom-scrollbar flex-1 select-text space-y-3 overflow-y-auto p-4">
                        {chatMessages.map((msg) => (
                            <div
                                key={msg.id}
                                className={`w-fit max-w-[88%] rounded-2xl px-4 py-2.5 text-xs shadow-xs ${bubbleClass(
                                    msg.role,
                                )}`}
                            >
                                {msg.role === 'assistant' ? (
                                    <MarkdownRenderer content={msg.text}/>
                                ) : (
                                    <p dir="auto"
                                       className="whitespace-pre-wrap wrap-break-word leading-6">{msg.text}</p>
                                )}
                            </div>
                        ))}

                        {isRunning && (
                            <div
                                className="me-auto flex w-fit items-center gap-2 rounded-2xl border border-line-border bg-surface-card px-3.5 py-2 text-xs text-content-muted shadow-xs">
                                <Icon name="loader" className="size-3.5 animate-spin text-primary"/>
                                <span>در حال پردازش...</span>
                            </div>
                        )}
                    </div>

                    <div className="flex items-end gap-2 border-t border-line-border/70 bg-surface-card/40 p-3">
                        <textarea
                            ref={textareaRef}
                            rows={1}
                            value={draft}
                            maxLength={3000}
                            autoComplete="off"
                            disabled={isRunning}
                            placeholder={placeholder}
                            onChange={handleTextareaChange}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' && !e.shiftKey && !e.nativeEvent.isComposing) {
                                    e.preventDefault();
                                    void handleSend();
                                }
                            }}
                            className="custom-scrollbar min-w-0 flex-1 resize-none overflow-hidden rounded-xl border border-line-border bg-surface-card px-3.5 py-2.5 text-xs leading-5 text-content-main transition placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:opacity-50"
                        />

                        {isRunning ? (
                            <Tooltip label="توقف اجرا">
                                <button
                                    type="button"
                                    onClick={() => void cancelWorkflow()}
                                    aria-label="توقف اجرا"
                                    className="mb-0.5 flex size-9.5 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-xs transition hover:bg-amber-600"
                                >
                                    <Icon name="player-stop" className="size-4"/>
                                </button>
                            </Tooltip>
                        ) : (
                            <Tooltip label="ارسال پیام">
                                <button
                                    type="button"
                                    onClick={() => void handleSend()}
                                    disabled={draft.trim() === ''}
                                    aria-label="ارسال پیام"
                                    className="mb-0.5 flex size-9.5 shrink-0 items-center justify-center rounded-xl bg-primary text-white shadow-xs transition hover:bg-primary/90 disabled:opacity-50"
                                >
                                    <Icon name="send" className="size-4 -scale-x-100"/>
                                </button>
                            </Tooltip>
                        )}
                    </div>
                </div>
            )}

            <Tooltip label={isChatOpen ? 'بستن تست چت' : 'تست گفتگوی ورکفلو'}>
                <button
                    type="button"
                    onClick={toggleChat}
                    className={`relative flex size-11 items-center justify-center rounded-2xl border shadow-2xl backdrop-blur-md transition-all active:scale-95 ${
                        isChatOpen
                            ? 'border-line-border bg-surface-raised text-content-main hover:bg-surface-card'
                            : 'border-emerald-500/40 bg-emerald-500 text-white shadow-emerald-500/20 hover:bg-emerald-600'
                    }`}
                >
                    <Icon name={isChatOpen ? 'x' : 'message-circle'} className="size-5"/>
                    {hasUnread && !isChatOpen && (
                        <span
                            className="absolute -right-1 -top-1 size-2.5 rounded-full bg-primary ring-2 ring-surface-raised"/>
                    )}
                </button>
            </Tooltip>
        </div>
    );
}

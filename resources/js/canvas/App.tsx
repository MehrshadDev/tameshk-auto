import { Canvas } from '@canvas/components/Canvas';
import { TopBar } from '@canvas/components/TopBar';
import { ToastContainer } from '@canvas/components/ui/ToastContainer';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';
import { useSaveGuard } from '@canvas/lib/useSaveGuard';
import { lazy, Suspense, useEffect } from 'react';

const ChatFloatingWidget = lazy(() =>
    import('@canvas/components/chat/ChatFloatingWidget').then((m) => ({ default: m.ChatFloatingWidget })),
);
const LogsFloatingWidget = lazy(() =>
    import('@canvas/components/logs/LogsFloatingWidget').then((m) => ({ default: m.LogsFloatingWidget })),
);
const NodeSettingsPanel = lazy(() =>
    import('@canvas/components/settings/NodeSettings').then((m) => ({ default: m.NodeSettingsPanel })),
);
const UnsavedChangesModal = lazy(() =>
    import('@canvas/components/ui/UnsavedChangesModal').then((m) => ({ default: m.UnsavedChangesModal })),
);

type AppProps = {
    workflowId: number;
    backUrl: string;
};

const SESSION_PING_INTERVAL = 5 * 60 * 1000;

export function App({ workflowId, backUrl }: AppProps) {
    useSaveGuard();

    const status = useWorkflowStore((state) => state.status);
    const load = useWorkflowStore((state) => state.load);
    const nodePanelOpen = useWorkflowStore((state) => state.panel?.type === 'node');
    const exitPromptOpen = useWorkflowStore((state) => state.exitPromptOpen);

    useEffect(() => {
        void load(workflowId);
    }, [load, workflowId]);

    useEffect(() => {
        let cancelled = false;

        const ping = async () => {
            if (document.hidden || cancelled) {
                return;
            }

            try {
                const response = await fetch('/app/api/ping', {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (response.status === 401 || response.status === 419) {
                    useWorkflowStore
                        .getState()
                        .addToast('نشست شما منقضی شده. لطفاً دوباره وارد شوید.', 'error', 10000);
                }
            } catch {
            }
        };

        const interval = window.setInterval(ping, SESSION_PING_INTERVAL);

        return () => {
            cancelled = true;
            window.clearInterval(interval);
        };
    }, []);

    return (
        <div className="flex h-full w-full flex-col">
            <TopBar backUrl={backUrl} />

            <div className="min-h-0 flex-1">
                {status === 'ready' ? (
                    <Canvas />
                ) : (
                    <div dir="rtl" className="flex h-full items-center justify-center text-sm text-content-muted">
                        {status === 'loading' ? 'در حال بارگذاری...' : 'بارگذاری ورکفلو با خطا مواجه شد.'}
                    </div>
                )}
            </div>

            <Suspense fallback={null}>
                {status === 'ready' && <ChatFloatingWidget />}
                {status === 'ready' && <LogsFloatingWidget />}
                {nodePanelOpen && <NodeSettingsPanel />}
                {exitPromptOpen && <UnsavedChangesModal backUrl={backUrl} />}
            </Suspense>

            <ToastContainer />
        </div>
    );
}

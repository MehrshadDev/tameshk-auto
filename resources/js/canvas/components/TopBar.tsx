import { Icon } from '@canvas/components/ui/Icon';
import { Tooltip } from '@canvas/components/ui/Tooltip';
import { isWebhookTrigger, isTelegramTrigger, isGmailTrigger, isSheetsTrigger, isTestableTrigger } from '@canvas/lib/triggers';
import { useColorMode } from '@canvas/lib/useColorMode';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';
import { SaveButton } from '@canvas/components/SaveButton';
import { PublishControl } from '@canvas/components/PublishControl';
import { WorkflowTitle } from '@canvas/components/WorkflowTitle';

type TopBarProps = {
    backUrl: string;
};

const buttonClass =
    'flex size-9 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main';

export function TopBar({ backUrl }: TopBarProps) {
    const nodes = useWorkflowStore((state) => state.nodes);
    const saveStatus = useWorkflowStore((state) => state.saveStatus);
    const openExitPrompt = useWorkflowStore((state) => state.openExitPrompt);
    const executeWorkflow = useWorkflowStore((state) => state.executeWorkflow);
    const cancelWorkflow = useWorkflowStore((state) => state.cancelWorkflow);
    const isRunning = useWorkflowStore((state) => state.isRunning);
    const runningNodeId = useWorkflowStore((state) => state.runningNodeId);
    const colorMode = useColorMode();

    const canTest = nodes.some((n) => isTestableTrigger(n.data.nodeType));
    const isBusy = isRunning || runningNodeId !== null;
    const runningTrigger = nodes.find((n) => n.id === runningNodeId);
    const listeningText = runningTrigger
        ? isWebhookTrigger(runningTrigger.data.nodeType)
            ? 'در انتظار دریافت وبهوک...'
            : isTelegramTrigger(runningTrigger.data.nodeType)
                ? 'در انتظار پیام تلگرام...'
                : isGmailTrigger(runningTrigger.data.nodeType)
                    ? 'در انتظار ایمیل جدید...'
                    : isSheetsTrigger(runningTrigger.data.nodeType)
                        ? 'در انتظار تغییر در شیت...'
                        : null
        : null;

    const handleBack = () => {
        if (saveStatus === 'saved') {
            window.location.href = backUrl;
            return;
        }

        openExitPrompt();
    };

    const toggleTheme = () => {
        const dark = !document.documentElement.classList.contains('dark');

        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem('theme', dark ? 'dark' : 'light');
    };

    return (
        <header
            dir="rtl"
            className="flex h-14 shrink-0 items-center gap-3 border-b border-line-border bg-surface-raised px-4"
        >
            <Tooltip label="بازگشت به ورکفلوها" side="bottom">
                <button type="button" onClick={handleBack} aria-label="بازگشت به ورکفلوها" className={buttonClass}>
                    <Icon name="arrow-right" />
                </button>
            </Tooltip>

            <div className="min-w-0 flex-1">
                <WorkflowTitle />
            </div>

            <div className="flex items-center gap-3">
                {isBusy ? (
                    <>
                        {listeningText && (
                            <span className="flex items-center gap-1.5 text-xs text-content-muted">
                                <Icon name="loader" className="size-3.5 animate-spin text-primary" />
                                {listeningText}
                            </span>
                        )}

                        <button
                            type="button"
                            onClick={() => void cancelWorkflow()}
                            className="flex items-center gap-2 rounded-xl bg-amber-500/10 border border-amber-500/20 px-3.5 py-1.5 text-xs font-bold text-amber-500 shadow-sm transition hover:bg-amber-500/20"
                        >
                            <Icon name="player-stop" className="size-4" />
                            <span>توقف اجرا</span>
                        </button>

                        <span className="h-5 w-px bg-line-border/80" />
                    </>
                ) : (
                    canTest && (
                        <>
                            <button
                                type="button"
                                onClick={() => void executeWorkflow()}
                                className="flex items-center gap-2 rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-500"
                            >
                                <Icon name="player-play" className="size-4" />
                                <span>تست اجرا</span>
                            </button>

                            <span className="h-5 w-px bg-line-border/80" />
                        </>
                    )
                )}

                <SaveButton />

                <span className="h-5 w-px bg-line-border/80" />

                <PublishControl />
            </div>

            <Tooltip label={colorMode === 'dark' ? 'تم روشن' : 'تم تاریک'} side="bottom">
                <button type="button" onClick={toggleTheme} aria-label="تغییر تم" className={buttonClass}>
                    <Icon name={colorMode === 'dark' ? 'sun' : 'moon'} />
                </button>
            </Tooltip>
        </header>
    );
}

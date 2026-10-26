import { ApiError } from '@canvas/api/client';
import { publishWorkflow, unpublishWorkflow } from '@canvas/api/workflows';
import type { Slice } from './types';

export type PublishSlice = {
    isPublishing: boolean;
    publish: () => Promise<void>;
    unpublish: () => Promise<void>;
};

export const createPublishSlice: Slice<PublishSlice> = (set, get) => ({
    isPublishing: false,

    publish: async () => {
        const { workflow, isPublishing, isRunning, saveStatus, save, addToast, addLog } = get();

        if (!workflow || isPublishing || isRunning) return;

        if (saveStatus !== 'saved') {
            await save();
            if (get().saveStatus !== 'saved') return;
        }

        set({ isPublishing: true });

        try {
            const res = await publishWorkflow(workflow.id);

            set({ workflow: res.workflow });
            addToast(res.message, 'success', 4000);
        } catch (error) {
            if (error instanceof ApiError) {
                const messages = Object.values(error.errors).flat();

                messages.forEach((message) => addLog({ source: 'publish', nodeName: null, message }));

                const extra = messages.length > 1 ? ` (و ${messages.length - 1} خطای دیگر در لاگ‌ها)` : '';
                addToast(`انتشار انجام نشد: ${messages[0] ?? 'خطای ناشناخته.'}${extra}`, 'error', 10000);
            } else {
                addLog({ source: 'publish', nodeName: null, message: 'Network error while publishing.' });
                addToast('خطا در ارتباط با سرور. اتصال اینترنت را بررسی کنید.', 'error');
            }
        } finally {
            set({ isPublishing: false });
        }
    },

    unpublish: async () => {
        const { workflow, isPublishing, addToast } = get();

        if (!workflow || isPublishing) return;

        set({ isPublishing: true });

        try {
            const res = await unpublishWorkflow(workflow.id);

            set({ workflow: res.workflow });
            addToast(res.message, 'info', 3500);
        } catch {
            addToast('لغو انتشار انجام نشد. دوباره تلاش کنید.', 'error');
        } finally {
            set({ isPublishing: false });
        }
    },
});

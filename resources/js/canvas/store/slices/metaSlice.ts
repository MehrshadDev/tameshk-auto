import { ApiError } from '@canvas/api/client';
import { renameWorkflow as renameWorkflowRequest } from '@canvas/api/workflows';
import type { Slice } from './types';

export type MetaSlice = {
    isRenaming: boolean;
    rename: (name: string) => Promise<boolean>;
};

export const createMetaSlice: Slice<MetaSlice> = (set, get) => ({
    isRenaming: false,

    rename: async (name) => {
        const { workflow, isRenaming, addToast } = get();

        if (!workflow || isRenaming) return false;

        set({ isRenaming: true });

        try {
            const res = await renameWorkflowRequest(workflow.id, name);

            set((state) => ({
                workflow: state.workflow ? { ...state.workflow, name: res.workflow.name } : state.workflow,
            }));

            return true;
        } catch (error) {
            if (error instanceof ApiError && error.status === 422) {
                addToast(error.firstError() ?? 'نام واردشده معتبر نیست.', 'error', 4000);
            } else {
                addToast('تغییر نام انجام نشد. دوباره تلاش کنید.', 'error', 4000);
            }

            return false;
        } finally {
            set({ isRenaming: false });
        }
    },
});

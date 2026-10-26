import { useEffect } from 'react';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';

let isBypassed = false;

export const bypassSaveGuard = () => {
    isBypassed = true;
};

export function useSaveGuard() {
    const save = useWorkflowStore((state) => state.save);

    useEffect(() => {
        isBypassed = false;
    }, []);

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            const isKeyS = event.code === 'KeyS' || event.key.toLowerCase() === 's' || event.key === 'س';

            if ((event.ctrlKey || event.metaKey) && isKeyS) {
                event.preventDefault();
                const { saveStatus, revision, savedRevision } = useWorkflowStore.getState();

                if (saveStatus === 'saving' || (saveStatus === 'saved' && revision === savedRevision)) {
                    return;
                }

                void save();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [save]);

    useEffect(() => {
        const onBeforeUnload = (event: BeforeUnloadEvent) => {
            const { saveStatus } = useWorkflowStore.getState();

            if (saveStatus === 'saved' || isBypassed) {
                return;
            }

            event.preventDefault();
        };

        window.addEventListener('beforeunload', onBeforeUnload);

        return () => window.removeEventListener('beforeunload', onBeforeUnload);
    }, []);
}

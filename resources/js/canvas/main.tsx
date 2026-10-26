import '@xyflow/react/dist/style.css';
import { createRoot } from 'react-dom/client';
import { App } from '@canvas/App';

const container = document.getElementById('canvas-root');

if (container) {
    createRoot(container).render(
        <App workflowId={Number(container.dataset.workflowId)} backUrl={container.dataset.backUrl ?? '/'} />,
    );
}

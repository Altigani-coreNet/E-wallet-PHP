import { createRoot } from 'react-dom/client';
import TerminalGroupForm from './TerminalGroupForm';

const TerminalGroupFormWrapper = () => {
    return <TerminalGroupForm />;
};

// Function to mount the component
export const mountTerminalGroupForm = (elementId = 'terminal-group-form-root') => {
    const element = document.getElementById(elementId);
    if (element) {
        const root = createRoot(element);
        root.render(<TerminalGroupFormWrapper   />);
    }
};

// Auto-mount if the element exists
if (typeof window !== 'undefined' && document.getElementById('terminal-group-form-root')) {
    mountTerminalGroupForm();
}

export default TerminalGroupFormWrapper; 
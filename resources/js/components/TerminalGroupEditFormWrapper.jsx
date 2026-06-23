import { useEffect, useState } from 'react';
import TerminalGroupEditForm from './TerminalGroupEditForm';

const TerminalGroupEditFormWrapper = () => {
    const [terminalGroupData, setTerminalGroupData] = useState(null);

    useEffect(() => {
        // Get the root element that contains the terminal group data
        const rootElement = document.getElementById('terminal-group-edit-form-root');
        
        if (rootElement) {
            // Extract terminal group data from data attribute
            const terminalGroupJson = rootElement.getAttribute('data-terminal-group');
            
            if (terminalGroupJson) {
                try {
                    const terminalGroup = JSON.parse(terminalGroupJson);
                    console.log('🔍 Found terminal group data from attributes:', terminalGroup);
                    setTerminalGroupData(terminalGroup);
                } catch (error) {
                    console.error('❌ Error parsing terminal group data:', error);
                }
            } else {
                console.error('❌ No terminal group data found in data attributes');
            }
        } else {
            console.error('❌ Root element not found');
        }
    }, []);

    if (!terminalGroupData) {
        return (
            <div className="post d-flex flex-column-fluid" id="kt_post">
                <div id="kt_content_container" className="container-xxl">
                    <div className="text-center py-5">
                        <div className="spinner-border text-primary" role="status">
                            <span className="visually-hidden">Loading...</span>
                        </div>
                        <p className="mt-3 text-muted">Loading terminal group data...</p>
                    </div>
                </div>
            </div>
        );
    }

    return <TerminalGroupEditForm initialData={terminalGroupData} />;
};

export default TerminalGroupEditFormWrapper; 
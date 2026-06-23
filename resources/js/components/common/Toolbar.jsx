import React, { useMemo } from 'react';
import Breadcrumbs from './Breadcrumbs';

const toolbarDir = () => {
    if (typeof document === 'undefined') return 'ltr';
    return document.documentElement.getAttribute('dir') === 'rtl' ? 'rtl' : 'ltr';
};

const Toolbar = ({ 
    pageTitle = 'Dashboard', 
    breadcrumbs = [], 
    actions = null,
    children 
}) => {
    const dir = useMemo(() => toolbarDir(), []);

    return (
        <div id="kt_app_toolbar" className="app-toolbar py-3 py-lg-6" dir={dir}>
            {/* Toolbar container */}
            <div id="kt_app_toolbar_container" className="app-container container-xxl d-flex flex-stack" dir={dir}>
                {/* Page title */}
                <div className="page-title d-flex flex-column justify-content-center flex-wrap me-3 align-items-start text-start">
                    {/* Title */}
                    <h1 className="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0 text-uppercase text-start">
                        {pageTitle}
                    </h1>
                    
                    {/* Breadcrumb */}
                    {breadcrumbs && breadcrumbs.length > 0 && (
                        <Breadcrumbs items={breadcrumbs} />
                    )}
                </div>
                
                {/* Actions */}
                {(actions || children) && (
                    <div className="d-flex align-items-center gap-2 gap-lg-3">
                        {actions}
                        {children}
                    </div>
                )}
            </div>
        </div>
    );
};

export default Toolbar;




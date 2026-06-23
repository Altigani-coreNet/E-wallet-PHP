import React, { useState, useEffect } from 'react';
import RolesIndex from './RolesIndex';
import RoleCreate from './RoleCreate';
import RoleEdit from './RoleEdit';

const RolesMain = () => {
    // Get page mode and role ID from data attributes
    const container = document.getElementById('roles-app');
    const mode = container?.dataset?.mode || 'list'; // list, create, edit
    const roleId = container?.dataset?.roleId || null;
    const typeParam = container?.dataset?.type || null;
    const parentParam = container?.dataset?.parent || null;

    // Render based on mode
    const renderPage = () => {
        switch (mode) {
            case 'create':
                return <RoleCreate typeParam={typeParam} />;
            
            case 'edit':
                if (!roleId) {
                    console.error('Role ID is required for edit mode');
                    return <RolesIndex />;
                }
                return <RoleEdit roleId={roleId} typeParam={typeParam} />;
            
            case 'list':
            default:
                return <RolesIndex />;
        }
    };

    return renderPage();
};

export default RolesMain;





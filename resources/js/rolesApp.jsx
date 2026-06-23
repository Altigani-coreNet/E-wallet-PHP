import './bootstrap';
import React from 'react';
import ReactDOM from 'react-dom/client';
import RolesMain from './components/roles/RolesMain';

console.log('Roles React Application Loading...');

// Check if the roles-app element exists before trying to render React
const rolesAppElement = document.getElementById('roles-app');
if (rolesAppElement) {
    console.log('Mounting Roles React Component...');
    const root = ReactDOM.createRoot(rolesAppElement);
    root.render(<RolesMain />);
}


import './bootstrap';
import React from 'react';
import ReactDOM from 'react-dom/client';
import UsersMain from './components/users/UsersMain';

console.log('Users React Application Loading...');

// Check if any users app element exists before trying to render React
const usersAppElement = document.getElementById('users-app');
const userCreateAppElement = document.getElementById('user-create-app');
const userEditAppElement = document.getElementById('user-edit-app');

if (usersAppElement || userCreateAppElement || userEditAppElement) {
    console.log('Mounting Users React Component...');
    const element = usersAppElement || userCreateAppElement || userEditAppElement;
    const root = ReactDOM.createRoot(element);
    root.render(<UsersMain />);
}





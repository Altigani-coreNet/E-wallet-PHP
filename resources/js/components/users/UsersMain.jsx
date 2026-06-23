import React from 'react';
import { useEffect } from 'react';
import UsersIndex from './UsersIndex';
import UserCreate from './UserCreate';
import UserEdit from './UserEdit';

const UsersMain = () => {
    // Get the app root element
    const usersAppElement = document.getElementById('users-app');
    const userCreateAppElement = document.getElementById('user-create-app');
    const userEditAppElement = document.getElementById('user-edit-app');

    useEffect(() => {
        console.log('🚀 Users React Component Mounted');
    }, []);

    // Determine which component to render based on which element exists
    if (usersAppElement) {
        return <UsersIndex />;
    }
    
    if (userCreateAppElement) {
        return <UserCreate />;
    }
    
    if (userEditAppElement) {
        const userId = userEditAppElement.dataset.userId;
        return <UserEdit userId={userId} />;
    }

    return null;
};

export default UsersMain;





import React from 'react';
import { useParams } from 'react-router-dom';
import UserEdit from './UserEdit';

const UserEditWrapper = () => {
    const { id } = useParams();
    return <UserEdit userId={id} />;
};

export default UserEditWrapper;


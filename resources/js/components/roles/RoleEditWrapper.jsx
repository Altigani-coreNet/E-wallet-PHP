import React from 'react';
import { useParams } from 'react-router-dom';
import RoleEdit from './RoleEdit';

const RoleEditWrapper = () => {
    const { id } = useParams();
    return <RoleEdit roleId={id} />;
};

export default RoleEditWrapper;


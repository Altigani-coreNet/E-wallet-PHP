import './bootstrap';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Dashboard from './components/Sales/Dashboard';
import Reports from './components/Sales/Reports';
import Orders from './components/Sales/Orders';
import Products from './components/Sales/Products';
import ProductCreate from './components/Sales/ProductCreate';
import ProductEdit from './components/Sales/ProductEdit';
import Customers from './components/Sales/Customers';
import CustomerCreate from './components/Sales/CustomerCreate';
import CustomerEdit from './components/Sales/CustomerEdit';
import CustomerView from './components/Sales/CustomerView';
import Tags from './components/Sales/Tags';
import Taxes from './components/Sales/Taxes';
import Categories from './components/Sales/Categories';
import Warehouse from './components/Sales/Warehouse';
import PosIndex from './components/POS/PosIndex';
import InvoicePrint from './components/Sales/InvoicePrint';

// User Management Components
import UsersIndex from './components/users/UsersIndex';
import UserCreate from './components/users/UserCreate';
import UserEditWrapper from './components/users/UserEditWrapper';

// Role Management Components
import RolesIndex from './components/roles/RolesIndex';
import RoleCreate from './components/roles/RoleCreate';
import RoleEditWrapper from './components/roles/RoleEditWrapper';

// Branch Management Components
import BranchesIndex from './components/branches/BranchesIndex';
import BranchCreate from './components/branches/BranchCreate';
import BranchEdit from './components/branches/BranchEdit';
import BranchView from './components/branches/BranchView';

import { setToken } from './utils/api';

// Main Sales App Component with Routing
function SalesApp() {

    return (
        <Routes>
            <Route path="/merchant/sales/dashboard" element={<Dashboard />} />
            <Route path="/merchant/sales/sale" element={<PosIndex />} />
            <Route path="/merchant/sales/invoice/:id" element={<InvoicePrint />} />
            <Route path="/merchant/sales/purchase" element={<div>Purchase Component</div>} />
            <Route path="/merchant/sales/reports" element={<Reports />} />
            <Route path="/merchant/sales/reports/sales" element={<Reports />} />
            <Route path="/merchant/sales/reports/purchases" element={<Reports />} />
            <Route path="/merchant/sales/reports/products" element={<Reports />} />
            <Route path="/merchant/sales/reports/expenses" element={<Reports />} />
            <Route path="/merchant/sales/orders" element={<Orders />} />
            
            {/* Product Management Routes */}
            <Route path="/merchant/sales/products" element={<Products />} />
            <Route path="/merchant/sales/products/create" element={<ProductCreate />} />
            <Route path="/merchant/sales/products/:id/edit" element={<ProductEdit />} />
            
            {/* Customer Management Routes */}
            <Route path="/merchant/sales/customers" element={<Customers />} />
            <Route path="/merchant/sales/customers/create" element={<CustomerCreate />} />
            <Route path="/merchant/sales/customers/:id" element={<CustomerView />} />
            <Route path="/merchant/sales/customers/:id/edit" element={<CustomerEdit />} />
            
            {/* Inventory Management Routes */}
            <Route path="/merchant/sales/tags" element={<Tags />} />
            <Route path="/merchant/sales/taxes" element={<Taxes />} />
            <Route path="/merchant/sales/categories" element={<Categories />} />
            <Route path="/merchant/sales/warehouse" element={<Warehouse />} />
            
            {/* User Management Routes */}
            <Route path="/merchant/sales/users" element={<UsersIndex />} />
            <Route path="/merchant/sales/users/create" element={<UserCreate />} />
            <Route path="/merchant/sales/users/:id/edit" element={<UserEditWrapper />} />
            <Route path="/merchant/sales/users/:id" element={<div>User View Component (Coming Soon)</div>} />
            
            {/* Role Management Routes */}
            <Route path="/merchant/sales/roles" element={<RolesIndex />} />
            <Route path="/merchant/sales/roles/create" element={<RoleCreate />} />
            <Route path="/merchant/sales/roles/:id/edit" element={<RoleEditWrapper />} />
            <Route path="/merchant/sales/roles/:id" element={<div>Role View Component (Coming Soon)</div>} />
            
            {/* Branch Management Routes */}
            <Route path="/merchant/sales/branches" element={<BranchesIndex />} />
            <Route path="/merchant/sales/branches/create" element={<BranchCreate />} />
            <Route path="/merchant/sales/branches/:id" element={<BranchView />} />
            <Route path="/merchant/sales/branches/:id/edit" element={<BranchEdit />} />
            
            {/* Default redirect to dashboard */}
            <Route path="/merchant/sales" element={<Navigate to="/merchant/sales/dashboard" replace />} />
            <Route path="/merchant/sales/" element={<Navigate to="/merchant/sales/dashboard" replace />} />
            
            {/* Catch-all route - redirect to dashboard */}
            <Route path="/merchant/sales/*" element={<Navigate to="/merchant/sales/dashboard" replace />} />
        </Routes>
    );
}

// Mount the app when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const salesAppRoot = document.getElementById('sales-app-root');
    
    if (salesAppRoot) {
        // Get API token from data attribute
        const apiToken = salesAppRoot.getAttribute('data-api-token');
        
        // Store token in localStorage
        if (apiToken) {
            setToken(apiToken);
        }
        
        try {
            const root = createRoot(salesAppRoot);
            root.render(
                <BrowserRouter>
                    <SalesApp />
                </BrowserRouter>
            );
        } catch (error) {
            console.error('Error mounting Sales SPA component:', error);
        }
    }
});


import './bootstrap';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

// Branch Management Components
import BranchesIndex from './components/branches/BranchesIndex';
import BranchCreate from './components/branches/BranchCreate';
import BranchEdit from './components/branches/BranchEdit';
import BranchView from './components/branches/BranchView';

// Terminal Management Components
import TerminalsIndex from './components/terminals/TerminalsIndex';
import TerminalCreate from './components/terminals/TerminalCreate';
import TerminalEdit from './components/terminals/TerminalEdit';
import TerminalView from './components/terminals/TerminalView';

// Payment Links Management Components
import PaymentLinksIndex from './components/payment-links/PaymentLinksIndex';
import PaymentLinkCreate from './components/payment-links/PaymentLinkCreate';
import PaymentLinkEdit from './components/payment-links/PaymentLinkEdit';

// Transaction Management Components
import MerchantTransactions from './components/merchant/MerchantTransactions';
import TransactionDetail from './components/merchant/TransactionDetail';

// Settlement Management Components
import MerchantSettlements from './components/merchant/MerchantSettlements';
import SettlementDetail from './components/merchant/SettlementDetail';

// Batch Management Components
import MerchantBatches from './components/merchant/MerchantBatches';
import BatchDetail from './components/merchant/BatchDetail';

// Contract Component
import ContractView from './components/contracts/ContractView';

// Service Fees Components
import ServiceFeesIndex from './components/service-fees/ServiceFeesIndex';
import ServiceFeeView from './components/service-fees/ServiceFeeView';

// Profile Component
import Profile from './components/Profile/Profile';

// Dashboard Component
import MerchantDashboard from './components/merchant/MerchantDashboard';

import { setToken } from './utils/api';

// Main Merchant App Component with Routing
function MerchantApp() {
    return (
        <>
            <Routes>
                {/* Dashboard Route */}
                <Route path="/merchant/dashboard" element={<MerchantDashboard />} />
                {/* <Route path="/merchant/dashboard-react" element={<MerchantDashboard />} /> */}
                
                {/* Branch Management Routes */}
                <Route path="/merchant/branches" element={<BranchesIndex />} />
                <Route path="/merchant/branches/create" element={<BranchCreate />} />
                <Route path="/merchant/branches/:id" element={<BranchView />} />
                <Route path="/merchant/branches/:id/edit" element={<BranchEdit />} />
                
                {/* Terminal Management Routes */}
                <Route path="/merchant/terminals" element={<TerminalsIndex />} />
                <Route path="/merchant/terminals/create" element={<TerminalCreate />} />
                <Route path="/merchant/terminals/:id" element={<TerminalView />} />
                <Route path="/merchant/terminals/:id/edit" element={<TerminalEdit />} />
                
                {/* Payment Links Management Routes */}
                <Route path="/merchant/payment-links" element={<PaymentLinksIndex />} />
                <Route path="/merchant/payment-links/create" element={<PaymentLinkCreate />} />
                <Route path="/merchant/payment-links/:id/edit" element={<PaymentLinkEdit />} />
                
                {/* Transaction Management Routes */}
                <Route path="/merchant/transactions" element={<MerchantTransactions />} />
                <Route path="/merchant/transactions/:id" element={<TransactionDetail />} />
                
                {/* Settlement Management Routes */}
                <Route path="/merchant/settlements" element={<MerchantSettlements />} />
                <Route path="/merchant/settlements/:id" element={<SettlementDetail />} />
                
                {/* Batch Management Routes */}
                <Route path="/merchant/batches" element={<MerchantBatches />} />
                <Route path="/merchant/batches/:id" element={<BatchDetail />} />
                
                {/* Contract Routes */}
                <Route path="/merchant/contracts" element={<ContractView />} />
                <Route path="/merchant/contracts/index" element={<ContractView />} />
                
                {/* Service Fees Routes */}
                <Route path="/merchant/service-fees" element={<ServiceFeesIndex />} />
                <Route path="/merchant/service-fees/:id" element={<ServiceFeeView />} />
                
                {/* Profile Routes */}
                <Route path="/merchant/profile" element={<Profile />} />
                <Route path="/merchant/profile/*" element={<Profile />} />
                
                {/* Default redirect to dashboard */}
                <Route path="/merchant" element={<Navigate to="/merchant/dashboard" replace />} />
                <Route path="/merchant/" element={<Navigate to="/merchant/dashboard" replace />} />
                
                {/* Catch-all route - redirect to branches */}
                <Route path="*" element={<Navigate to="/merchant/branches" replace />} />
            </Routes>
            
            {/* Toast Container for notifications */}
            <ToastContainer
                position="top-right"
                autoClose={3000}
                hideProgressBar={false}
                newestOnTop={true}
                closeOnClick
                rtl={false}
                pauseOnFocusLoss
                draggable
                pauseOnHover
                theme="light"
            />
        </>
    );
}

// Mount the app when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const merchantAppRoot = document.getElementById('merchant-app-root');
    
    if (merchantAppRoot) {
        // Get API token from data attribute
        const apiToken = merchantAppRoot.getAttribute('data-api-token');
        
        // Store token in localStorage
        if (apiToken) {
            setToken(apiToken);
        }
        
        try {
            const root = createRoot(merchantAppRoot);
            root.render(
                <BrowserRouter>
                    <MerchantApp />
                </BrowserRouter>
            );
        } catch (error) {
            console.error('Error mounting Merchant App component:', error);
        }
    }
});


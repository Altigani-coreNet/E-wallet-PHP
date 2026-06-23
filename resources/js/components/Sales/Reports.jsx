import React, { useState, useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import PurchaseReport from './PurchaseReport';
import SalesReport from './SalesReport';
import ProductsReport from './ProductsReport';
import ExpensesReport from './ExpensesReport';

export default function Reports() {
    const location = useLocation();
    const navigate = useNavigate();
    
    // Get initial tab from URL path or default to 'sales'
    const getInitialTab = () => {
        const path = location.pathname;
        const validTabs = ['purchases', 'sales', 'products', 'expenses'];
        
        // Extract the last part of the path (e.g., /merchant/sales/reports/sales -> sales)
        const pathParts = path.split('/');
        const lastPart = pathParts[pathParts.length - 1];
        
        return validTabs.includes(lastPart) ? lastPart : 'sales';
    };
    
    const [activeTab, setActiveTab] = useState(getInitialTab());

    // Update tab when URL path changes
    useEffect(() => {
        const tab = getInitialTab();
        setActiveTab(tab);
    }, [location.pathname]);

    // Navigate to new route when tab changes
    const handleTabChange = (tabId) => {
        navigate(`/merchant/sales/reports/${tabId}`);
    };

    const tabs = [
        { id: 'purchases', label: 'Purchase Reports', icon: 'bx-cart' },
        { id: 'sales', label: 'Sales Reports', icon: 'bx-shopping-bag' },
        { id: 'products', label: 'Product Reports', icon: 'bx-package' },
        { id: 'expenses', label: 'Expense Reports', icon: 'bx-dollar-circle' },
    ];

    // Keep all reports mounted for caching
    const renderReports = () => (
        <>
            <div style={{ display: activeTab === 'purchases' ? 'block' : 'none' }}>
                <PurchaseReport />
            </div>
            <div style={{ display: activeTab === 'sales' ? 'block' : 'none' }}>
                <SalesReport />
            </div>
            <div style={{ display: activeTab === 'products' ? 'block' : 'none' }}>
                <ProductsReport />
            </div>
            <div style={{ display: activeTab === 'expenses' ? 'block' : 'none' }}>
                <ExpensesReport />
            </div>
        </>
    );

    return (
        <div className="container-fluid">
            <div className="row">
                <div className="col-12">
                    {/* Page Header */}
                    <div className="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 className="mb-1">Reports</h4>
                            <nav aria-label="breadcrumb">
                                <ol className="breadcrumb mb-0">
                                    <li className="breadcrumb-item"><a href="/merchant/sales/dashboard">Dashboard</a></li>
                                    <li className="breadcrumb-item active">Reports</li>
                                </ol>
                            </nav>
                        </div>
                    </div>

                    {/* Tab Navigation */}
                    <div className="card mb-4">
                        <div className="card-body p-0">
                            <ul className="nav nav-tabs nav-tabs-custom" role="tablist">
                                {tabs.map(tab => (
                                    <li className="nav-item" key={tab.id}>
                                        <button
                                            className={`nav-link ${activeTab === tab.id ? 'active' : ''}`}
                                            onClick={() => handleTabChange(tab.id)}
                                            type="button"
                                        >
                                            <i className={`bx ${tab.icon} me-2`}></i>
                                            {tab.label}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>

                    {/* Report Content */}
                    <div className="report-content">
                        {renderReports()}
                    </div>
                </div>
            </div>

            <style jsx>{`
                .nav-tabs-custom {
                    border-bottom: 2px solid #e9ecef;
                    margin-bottom: 0;
                }
                
                .nav-tabs-custom .nav-link {
                    border: none;
                    color: #6c757d;
                    padding: 1rem 1.5rem;
                    font-weight: 500;
                    transition: all 0.3s ease;
                }
                
                .nav-tabs-custom .nav-link:hover {
                    color: #495057;
                    background-color: #f8f9fa;
                }
                
                .nav-tabs-custom .nav-link.active {
                    color: #0d6efd;
                    border-bottom: 2px solid #0d6efd;
                    background-color: transparent;
                }
                
                .report-content {
                    animation: fadeIn 0.3s ease-in;
                }
                
                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            `}</style>
        </div>
    );
}


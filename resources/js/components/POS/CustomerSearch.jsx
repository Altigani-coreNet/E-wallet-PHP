import React, { useState, useCallback, useRef } from 'react';
import usePosStore from '../../store/usePosStore';
import CustomerCreateModal from './CustomerCreateModal';

const CustomerSearch = () => {
    const { 
        customers,
        selectedCustomer,
        customersLoading,
        fetchCustomers,
        selectCustomer
    } = usePosStore();

    const [customerSearchTerm, setCustomerSearchTerm] = useState('');
    const [showCustomerList, setShowCustomerList] = useState(false);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const customerSearchRef = useRef(null);

    // Debounce function for customer search
    const debounce = (func, delay) => {
        let timeoutId;
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(null, args), delay);
        };
    };

    // Debounced customer search function
    const debouncedCustomerSearch = useCallback(
        debounce(async (searchTerm) => {
            if (searchTerm.length >= 2) {
                await fetchCustomers(searchTerm);
            } else if (searchTerm.length === 0) {
                await fetchCustomers();
            }
        }, 500), // 500ms delay
        [fetchCustomers]
    );

    const handleCustomerSearch = (searchTerm) => {
        debouncedCustomerSearch(searchTerm);
        setShowCustomerList(true); // Show customer list when searching
    };

    const handleCustomerSelect = (customer) => {
        selectCustomer(customer);
        setCustomerSearchTerm(customer.name); // Clear search term when customer is selected
        setShowCustomerList(false); 
        // console.log(customer.name);

        // Hide customer list when customer is selected
        // Focus the search field after selection
        // setTimeout(() => {
        //     if (customerSearchRef.current) {
        //         customerSearchRef.current.focus();
        //     }
        // }, 100);
    };

    const handleAddNewCustomer = () => {
        setShowCreateModal(true);
    };

    const handleCustomerCreated = (newCustomer) => {
        selectCustomer(newCustomer);
        // Focus the search field after creating new customer
        setTimeout(() => {
            if (customerSearchRef.current) {
                customerSearchRef.current.focus();
            }
        }, 100);
    };

    const handleRemoveCustomer = () => {
        selectCustomer(null);
        setCustomerSearchTerm(''); // Clear search term when customer is removed
        setShowCustomerList(false); // Hide customer list when customer is removed
        // Focus the search field after removal
        setTimeout(() => {
            if (customerSearchRef.current) {
                customerSearchRef.current.focus();
            }
        }, 100);
    };

    return (
        <div className="customer-section mb-8">
            {/*begin::Customer Selection*/}
            <div className="d-flex flex-column">
                <h4 className="fw-bold text-gray-800 mb-3">Customer Information</h4>
                
                {/*begin::Customer Search and Select*/}
                <div className="d-flex gap-2 mb-3">
                    <div className="flex-grow-1 position-relative">
                        <input 
                            ref={customerSearchRef}
                            type="text" 
                            className="form-control h-50px" 
                            placeholder="Search customers..."
                            value={customerSearchTerm}
                            onChange={(e) => {
                                const value = e.target.value;
                                setCustomerSearchTerm(value);
                                handleCustomerSearch(value);
                            }}
                            onFocus={() => {
                                if (customers.length > 0) {
                                    setShowCustomerList(true);
                                }
                            }}
                            onBlur={() => {
                                // Hide customer list when input loses focus
                                setTimeout(() => {
                                    setShowCustomerList(false);
                                }, 200); // Small delay to allow clicking on customer items
                            }}
                            disabled={customersLoading}
                        />
                        {customersLoading && (
                            <div className="position-absolute top-50 end-0 translate-middle-y me-3">
                                <div className="spinner-border spinner-border-sm" role="status">
                                    <span className="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        )}
                        
                        {/*begin::Customer Dropdown*/}
                        {showCustomerList && customers.length > 0 && (
                            <div className="position-absolute top-100 start-0 w-100 bg-white border rounded-3 shadow-sm mt-1" style={{ zIndex: 1000 }}>
                                {customers.map((customer) => (
                                    <div 
                                        key={customer.id}
                                        className="p-3 border-bottom cursor-pointer hover-bg-light"
                                        onClick={() => handleCustomerSelect(customer)}
                                        style={{ cursor: 'pointer' }}
                                    >
                                        <div className="fw-bold text-gray-800">{customer.name}</div>
                                        <div className="text-muted fs-7">{customer.email}</div>
                                        {customer.phone && (
                                            <div className="text-muted fs-7">{customer.phone}</div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                    
                    <button 
                        type="button"
                        className="btn btn-primary h-50px px-4"
                        onClick={handleAddNewCustomer}
                    >
                        <i className="ki-duotone ki-user-edit fs-2">
                            <span className="path1"></span>
                            <span className="path2"></span>
                            <span className="path3"></span>
                            </i>
                    </button>
                </div>
                {/*end::Customer Search and Select*/}
                
                {/*begin::Selected Customer Display*/}
                {selectedCustomer && (
                    <div className="card card-flush bg-light-primary border border-primary">
                        <div className="card-body p-4">
                            <div className="d-flex justify-content-between align-items-start">
                                <div className="flex-grow-1">
                                    <h6 className="fw-bold text-gray-800 mb-1">{selectedCustomer.name}</h6>
                                    <div className="text-muted fs-7 mb-1">{selectedCustomer.email}</div>
                                    {selectedCustomer.phone && (
                                        <div className="text-muted fs-7 mb-1">{selectedCustomer.phone}</div>
                                    )}
                                    {selectedCustomer.address && (
                                        <div className="text-muted fs-7">{selectedCustomer.address}</div>
                                    )}
                                </div>
                                <button 
                                    type="button"
                                    className="btn btn-icon btn-sm btn-light-danger"
                                    onClick={handleRemoveCustomer}
                                >
                                    <i className="ki-duotone ki-cross fs-2">
                                        <span className="path1"></span>
                                        <span className="path2"></span>
                                    </i>
                                </button>
                            </div>
                        </div>
                    </div>
                )}
                {/*end::Selected Customer Display*/}
            </div>
            {/*end::Customer Selection*/}
            
            {/* Customer Create Modal */}
            <CustomerCreateModal 
                isOpen={showCreateModal}
                onClose={() => setShowCreateModal(false)}
                onCustomerCreated={handleCustomerCreated}
            />
        </div>
    );
};

export default CustomerSearch;


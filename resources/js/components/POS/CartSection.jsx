import React, { useState } from 'react';
import usePosStore from '../../store/usePosStore';
import { apiPost } from '../../utils/apiUtils';
import { API_V_1, POS_API_BASE } from '../../utils/constants';
import CustomerSearch from './CustomerSearch';

const CartSection = () => {
    const { 
        cart, 
        clearCart, 
        updateQuantity, 
        removeFromCart,
        getCartSubtotal,
        discount,
        tax,
        cartTotal,
        paymentMethod,
        setPaymentMethod,
        fetchProducts,
        selectedCustomer
    } = usePosStore();

    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState(paymentMethod || '1');



    const handlePaymentMethodChange = (method) => {
        setPaymentMethod(method);
    };

    const handleCompleteSale = async () => {
        if (cart.length === 0) {
            alert('Cart is empty');
            return;
        }

        try {
            // Prepare sale data according to SaleRequest structure
            const saleData = {
                date: new Date().toISOString().split('T')[0], // Current date
                product_ids: cart.map(item => item.id),
                product_variant_ids: cart.map(item => item.variant_id || null),
                product_serial_numbers: cart.map(item => item.serial_imei_number || null),
                qty: cart.map(item => item.quantity),
                price: cart.map(item => item.price),
                discount: discount,
                discount_type: discount > 0 ? 'Fixed' : null,
                shipping_cost: 0, // Set to 0 as default
                tax_id: null, // Set to null as default
                coupon_id: null, // Set to null as default
                customer_id: selectedCustomer?.id || null, // Add selected customer
                paid_amount: cartTotal,
                sale_note: null, // Set to null as default
                staff_note: null, // Set to null as default
                document: null, // Set to null as default
                payment_method: getPaymentMethodLabel(paymentMethod),
                type: 'Sale' // Set to 'Sale' for completed sale
            };

            const response = await apiPost(`${POS_API_BASE}/api/v1/pos/store`, saleData);

            if (response.success) {
                // Clear cart after successful sale
                clearCart();
                
                // Reload products to update stock quantities
                fetchProducts();
                
                // Show success SweetAlert with options
                Swal.fire({
                    title: 'Sale Completed Successfully!',
                    text: 'Your sale has been processed successfully.',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Done',
                    cancelButtonText: 'Print Invoice',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#28a745',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // User clicked "Done" - just close the alert
                        console.log('Sale completed, user chose Done');
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // User clicked "Print Invoice" - redirect to invoice page
                        const saleId = response.data?.data?.id;
                        if (saleId) {
                            // Redirect to the invoice page
                            window.location.href = `/merchant/sales/invoice/${saleId}`;
                        } else {
                            // Fallback if no sale ID
                            Swal.fire({
                                title: 'Error',
                                text: 'Invoice not available',
                                icon: 'error'
                            });
                        }
                    }
                });
                
                // You can add additional success handling here
                // For example, redirect to invoice or show receipt
                if (response.data?.data?.invoice_pdf_url) {
                    // Open PDF in new tab as backup
                    window.open(response.data.data.invoice_pdf_url, '_blank');
                }
            } else {
                // Show error SweetAlert
                Swal.fire({
                    title: 'Sale Failed',
                    text: response.error?.message || 'Unknown error occurred',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        } catch (error) {
            console.error('Sale creation error:', error);
            Swal.fire({
                title: 'Error',
                text: 'Failed to create sale. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    };

    const handleDraftSale = async () => {
        if (cart.length === 0) {
            Swal.fire({
                title: 'Cart Empty',
                text: 'Please add products to cart before creating a draft',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        try {
            // Prepare draft data according to SaleRequest structure
            const draftData = {
                date: new Date().toISOString().split('T')[0], // Current date
                product_ids: cart.map(item => item.id),
                product_variant_ids: cart.map(item => item.variant_id || null),
                product_serial_numbers: cart.map(item => item.serial_imei_number || null),
                qty: cart.map(item => item.quantity),
                price: cart.map(item => item.price),
                discount: discount,
                discount_type: discount > 0 ? 'Fixed' : null,
                shipping_cost: 0, // Set to 0 as default
                tax_id: null, // Set to null as default
                coupon_id: null, // Set to null as default
                customer_id: selectedCustomer?.id || null, // Add selected customer
                paid_amount: cartTotal,
                sale_note: null, // Set to null as default
                staff_note: null, // Set to null as default
                document: null, // Set to null as default
                payment_method: getPaymentMethodLabel(paymentMethod),
                type: 'Draft' // Set to 'Draft' for draft sale
            };

            const response = await apiPost(`${API_V_1}/pos/store`, draftData);

            if (response.success) {
                // Clear cart after successful draft creation
                clearCart();
                
                // Reload products to update stock quantities
                fetchProducts();
                
                // Show success SweetAlert for draft
                Swal.fire({
                    title: 'Draft Created Successfully!',
                    text: 'Your draft has been saved successfully.',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Done',
                    cancelButtonText: 'View Draft',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#28a745',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // User clicked "Done" - just close the alert
                        console.log('Draft created, user chose Done');
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // User clicked "View Draft" - redirect to drafts page
                        const draftId = response.data?.data?.draft_id;
                        if (draftId) {
                            // Redirect to the drafts page
                            window.location.href = `/drafts`;
                        } else {
                            // Fallback if no draft ID
                            Swal.fire({
                                title: 'Error',
                                text: 'Draft not available',
                                icon: 'error'
                            });
                        }
                    }
                });
            } else {
                // Show error SweetAlert
                Swal.fire({
                    title: 'Draft Creation Failed',
                    text: response.error?.message || 'Unknown error occurred',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        } catch (error) {
            console.error('Draft creation error:', error);
            Swal.fire({
                title: 'Error',
                text: 'Failed to create draft. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    };



    const handleQuantityChange = (itemId, newQuantity) => {
        if (newQuantity <= 0) {
            removeFromCart(itemId);
        } else {
            updateQuantity(itemId, newQuantity);
        }
    };

    const getPaymentMethodLabel = (method) => {
        switch (method) {
            case '0': return 'Cash';
            case '1': return 'Card';
            default: return 'Card';
        }
    };

    const getPaymentMethodIcon = (method) => {
        switch (method) {
            case '0':
                return (
                    <i className="ki-duotone ki-dollar fs-2hx mb-2 pe-0">
                        <span className="path1"></span>
                        <span className="path2"></span>
                        <span className="path3"></span>
                    </i>
                );
            case '1':
                return (
                    <i className="ki-duotone ki-credit-cart fs-2hx mb-2 pe-0">
                        <span className="path1"></span>
                        <span className="path2"></span>
                    </i>
                );
            case '2':
                return (
                    <i className="ki-duotone ki-paypal fs-2hx mb-2 pe-0">
                        <span className="path1"></span>
                        <span className="path2"></span>
                    </i>
                );
            default:
                return (
                    <i className="ki-duotone ki-credit-cart fs-2hx mb-2 pe-0">
                        <span className="path1"></span>
                        <span className="path2"></span>
                    </i>
                );
        }
    };

    return (
        <div className="flex-row-auto w-xl-450px">
            {/*begin::Pos order*/}
            <div className="card card-flush bg-body" id="kt_pos_form">
                {/*begin::Header*/}
                <div className="card-header pt-5">
                    <h3 className="card-title fw-bold text-gray-800 fs-2x">Current Order</h3>
                    {/*begin::Toolbar*/}
                    <div className="card-toolbar">
                        <button 
                            onClick={clearCart}
                            className="btn btn-light-primary btn-sm fs-5 fw-bold py-2"
                            disabled={cart.length === 0}
                        >
                            Clear All
                        </button>

                        
                    </div>
                    {/*end::Toolbar*/}
                </div>
                {/*end::Header*/}
                
                {/*begin::Body*/}
                <div className="card-body pt-0">
                    <CustomerSearch />

                    {/*begin::Table container*/}
                    <div className="table-responsive mb-8">
                        {/*begin::Table*/}
                        <table className="table align-middle gs-0 gy-4 my-0">
                            {/*begin::Table head*/}
                            <thead>
                                <tr>
                                    <th className="min-w-175px">Product</th>
                                    <th className="w-125px">Quantity</th>
                                    <th className="w-60px">Price</th>
                                    <th className="w-40px"></th>
                                </tr>
                            </thead>
                            {/*end::Table head*/}
                            
                            {/*begin::Table body*/}
                            <tbody>
                                {cart.length === 0 ? (
                                    <tr>
                                        <td colSpan="4" className="text-center text-muted py-8">
                                            <i className="ki-duotone ki-basket fs-2hx text-muted mb-4">
                                                <span className="path1"></span>
                                                <span className="path2"></span>
                                                <span className="path3"></span>
                                                <span className="path4"></span>
                                            </i>
                                            <div>Your cart is empty</div>
                                            <div className="fs-7">Add some products to get started</div>
                                        </td>
                                    </tr>
                                ) : (
                                    cart.map((item) => (
                                        <tr key={item.id} data-kt-pos-element="item" data-kt-pos-item-price={item.price}>
                                            <td className="pe-0">
                                                <div className="d-flex align-items-center">
                                                    <img 
                                                        src={item.thumbnail || item.image || "assets/media/stock/food/img-2.jpg"} 
                                                        className="w-50px h-50px rounded-3 me-3" 
                                                        alt={item.name}
                                                        onError={(e) => {
                                                            e.target.src = "assets/media/stock/food/img-2.jpg";
                                                        }}
                                                    />
                                                    <div className="d-flex flex-column">
                                                        <span className="fw-bold text-gray-800 cursor-pointer text-hover-primary fs-6 me-1">
                                                            {item.name}
                                                        </span>
                                                        {item.code && (
                                                            <small className="text-muted fs-8">SKU: {item.code}</small>
                                                        )}
                                                        {item.batch && (
                                                            <small className="text-info fs-8">Batch Tracked</small>
                                                        )}
                                                        {item.serial_imei_number && (
                                                            <small className="text-warning fs-8">Serial Tracked</small>
                                                        )}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="pe-0">
                                                {/*begin::Dialer*/}
                                                <div className="position-relative d-flex align-items-center" data-kt-dialer="true" data-kt-dialer-min="1" data-kt-dialer-max="10" data-kt-dialer-step="1" data-kt-dialer-decimals="0">
                                                    {/*begin::Decrease control*/}
                                                    <button 
                                                        type="button" 
                                                        className="btn btn-icon btn-sm btn-light btn-icon-gray-500" 
                                                        onClick={() => handleQuantityChange(item.id, item.quantity - 1)}
                                                    >
                                                        <i className="ki-duotone ki-minus fs-3x"></i>
                                                    </button>
                                                    {/*end::Decrease control*/}
                                                    
                                                    {/*begin::Input control*/}
                                                    <input 
                                                        type="text" 
                                                        className="form-control border-0 text-center px-0 fs-3 fw-bold text-gray-800 w-30px" 
                                                        value={item.quantity}
                                                        readOnly
                                                    />
                                                    {/*end::Input control*/}
                                                    
                                                    {/*begin::Increase control*/}
                                                    <button 
                                                        type="button" 
                                                        className="btn btn-icon btn-sm btn-light btn-icon-gray-500" 
                                                        onClick={() => handleQuantityChange(item.id, item.quantity + 1)}
                                                    >
                                                        <i className="ki-duotone ki-plus fs-3x"></i>
                                                    </button>
                                                    {/*end::Increase control*/}
                                                </div>
                                                {/*end::Dialer*/}
                                            </td>
                                            <td className="text-end">
                                                <div className="d-flex flex-column">
                                                    <span className="fw-bold text-primary fs-2" data-kt-pos-element="item-total">
                                                        ${(item.price * item.quantity).toFixed(2)}
                                                    </span>
                                                    {item.discount > 0 && (
                                                        <small className="text-danger fs-8">
                                                            -${(item.discount * item.quantity).toFixed(2)} off
                                                        </small>
                                                    )}
                                                    {item.tax > 0 && (
                                                        <small className="text-muted fs-8">
                                                            +${(item.tax * item.quantity).toFixed(2)} tax
                                                        </small>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="text-end">
                                                <button 
                                                    type="button" 
                                                    className="btn btn-icon btn-sm btn-light-danger" 
                                                    onClick={() => removeFromCart(item.id)}
                                                >
                                                    <i className="ki-duotone ki-trash fs-2">
                                                        <span className="path1"></span>
                                                        <span className="path2"></span>
                                                        <span className="path3"></span>
                                                        <span className="path4"></span>
                                                        <span className="path5"></span>
                                                    </i>
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                            {/*end::Table body*/}
                        </table>
                        {/*end::Table*/}
                    </div>
                    {/*end::Table container*/}
                    
                    {/*begin::Summary*/}
                    <div className="d-flex flex-stack bg-success rounded-3 p-6 mb-11">
                        {/*begin::Content*/}
                        <div className="fs-6 fw-bold text-white">
                            <span className="d-block lh-1 mb-2">Subtotal</span>
                            <span className="d-block mb-2">Discounts</span>
                            <span className="d-block mb-9">Tax</span>
                            <span className="d-block fs-2qx lh-1">Total</span>
                        </div>
                        {/*end::Content*/}
                        
                        {/*begin::Content*/}
                        <div className="fs-6 fw-bold text-white text-end">
                            <span className="d-block lh-1 mb-2" data-kt-pos-element="total">
                                ${getCartSubtotal().toFixed(2)}
                            </span>
                            <span className="d-block mb-2" data-kt-pos-element="discount">
                                -${discount.toFixed(2)}
                            </span>
                            <span className="d-block mb-9" data-kt-pos-element="tax">
                                ${tax?.toFixed(2)}
                            </span>
                            <span className="d-block fs-2qx lh-1" data-kt-pos-element="grant-total">
                                ${cartTotal?.toFixed(2)}
                            </span>
                        </div>
                        {/*end::Content*/}
                    </div>
                    {/*end::Summary*/}
                    
                    {/*begin::Payment Method*/}
                    <div className="m-0">
                        {/*begin::Title*/}
                        <h1 className="fw-bold text-gray-800 mb-5">Payment Method</h1>
                        {/*end::Title*/}
                        
                        {/*begin::Radio group*/}
                        <div className="d-flex flex-equal gap-5 gap-xxl-9 px-0 mb-12" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button]" data-kt-initialized="1">
                            {/*begin::Radio - Cash*/}
                            <label className={`btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 px-4 ${paymentMethod === '0' ? 'active' : ''}`} data-kt-button="true">
                                {/*begin::Input*/}
                                <input 
                                    className="btn-check" 
                                    type="radio" 
                                    name="method" 
                                    value="0" 
                                    checked={paymentMethod === '0'}
                                    onChange={() => handlePaymentMethodChange('0')}
                                />
                                {/*end::Input*/}
                                {/*begin::Icon*/}
                                <i className="ki-duotone ki-dollar fs-2hx mb-2 pe-0">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                    <span className="path3"></span>
                                </i>
                                {/*end::Icon*/}
                                {/*begin::Title*/}
                                <span className="fs-7 fw-bold d-block">Cash</span>
                                {/*end::Title*/}
                            </label>
                            {/*end::Radio*/}
                            
                            {/*begin::Radio - Card*/}
                            <label className={`btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 px-4 ${paymentMethod === '1' ? 'active' : ''}`} data-kt-button="true">
                                {/*begin::Input*/}
                                <input 
                                    className="btn-check" 
                                    type="radio" 
                                    name="method" 
                                    value="1" 
                                    checked={paymentMethod === '1'}
                                    onChange={() => handlePaymentMethodChange('1')}
                                />
                                {/*end::Input*/}
                                {/*begin::Icon*/}
                                <i className="ki-duotone ki-credit-cart fs-2hx mb-2 pe-0">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                </i>
                                {/*end::Icon*/}
                                {/*begin::Title*/}
                                <span className="fs-7 fw-bold d-block">Card</span>
                                {/*end::Title*/}
                            </label>
                            {/*end::Radio*/}
                            
                            {/*end::Radio*/}
                        </div>
                        {/*end::Radio group*/}
                        
                        {/*begin::Actions*/}
                        <div className="d-flex gap-3">
                            <button 
                                className="btn btn-primary fs-1 flex-grow-1 py-4"
                                disabled={cart.length === 0}
                                onClick={handleCompleteSale}
                            >
                                Complete Sale
                            </button>

                           
                            {/* <button 
                                className="btn btn-light-primary fs-1 py-4"
                                disabled={cart.length === 0}
                            >
                                Print Bills
                            </button> */}
                        </div>

                        <div className='d-flex gap-3 mt-3'>
                        <button 
                                className="btn btn-light-primary fs-1 flex-grow-1 py-4"
                                disabled={cart.length === 0}
                                onClick={handleDraftSale}
                            >
                                Draft Sale 
                            </button>
                            </div>

                        {/*end::Actions*/}
                    </div>
                    {/*end::Payment Method*/}
                </div>
                {/*end: Card Body*/}
            </div>
            {/*end::Pos order*/}
        </div>
    );
};

export default CartSection;


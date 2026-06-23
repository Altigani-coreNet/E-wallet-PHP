import React, { useState } from 'react';
import usePosStore from '../../store/usePosStore';
import { API_V_2 } from '../../utils/constants';

const ProductCard = ({ product }) => {
    const { addToCart, updateQuantity, cart } = usePosStore();
    const [isHovered, setIsHovered] = useState(false);

    // Get current quantity in cart for this product
    const cartItem = cart.find(item => item.id === product.id);
    const quantityInCart = cartItem ? cartItem.quantity : 0;

    const handleAddToCart = async () => {
        addToCart(product);
    };

    const handleIncrease = async () => {
        if (quantityInCart === 0) {
            addToCart(product);
        } else {
            updateQuantity(product.id, quantityInCart + 1);
        }
    };

    const handleDecrease = () => {
        if (quantityInCart > 0) {
            updateQuantity(product.id, quantityInCart - 1);
        }
    };

    // Get stock status color
    const getStockStatusColor = () => {
        if (product.qty <= 0) return 'text-danger';
        if (product.qty <= 10) return 'text-warning';
        return 'text-success';
    };

    // Check if product is out of stock
    const isOutOfStock = (product.qty || 0) <= 0;

    return (
        <div 
            className="card card-flush flex-row-fluid p-6 pb-5 mw-100 col-md-4 product-card fade-in position-relative"
            onMouseEnter={() => setIsHovered(true)}  
            onMouseLeave={() => setIsHovered(false)}
        >
            {/*begin::Body*/}
            <div className="card-body text-center">
                {/*begin::Product img*/}
                <img 
                    src={product.thumbnail || "assets/media/stock/food/img-2.jpg"} 
                    className="rounded-3 mb-4 w-150px h-150px w-xxl-200px h-xxl-200px" 
                    alt={product.name}
                    onError={(e) => {
                        e.target.src = "assets/media/stock/food/img-2.jpg";
                    }}
                />
                {/*end::Product img*/}
                
                {/*begin::Info*/}
                <div className="mb-2">
                    {/*begin::Title*/}
                    <div className="text-center">
                        <span 
                            className={`fw-bold text-gray-800 fs-3 fs-xl-1 ${!isOutOfStock ? 'cursor-pointer text-hover-primary' : ''}`}
                            onClick={!isOutOfStock ? handleAddToCart : undefined}
                            style={{ cursor: !isOutOfStock ? 'pointer' : 'default' }}
                        >
                            {product.name}
                        </span>
                        
                        {/* Stock Information */}
                        {/* {isOutOfStock ? (
                            <span className="badge badge-light-danger fw-semibold d-block fs-6 mt-n1">
                                Out of Stock
                            </span>
                        ) : (
                            <span className={`fw-semibold d-block fs-6 mt-n1 ${getStockStatusColor()}`}>
                                Stock: {product.qty || 0} {product.unit || 'units'}
                            </span>
                        )} */}
                        
                        {/* Product Code */}
                        
                        
                        {/* Brand and Category Information */}
                        {/* <div className="d-flex justify-content-center gap-2 mt-2">
                            {product.brand && (
                                <span className="badge badge-info fs-8">{product.brand}</span>
                            )}
                            {product.category && (
                                <span className="badge badge-primary fs-8">{product.category}</span>
                            )}
                        </div> */}
                    </div>
                    {/*end::Title*/}
                </div>
                {/*end::Info*/}
                
                {/*begin::Pricing*/}
                <div className="mb-3">
                    {/* Main Price */}
                    <span className="text-success text-end fw-bold fs-1">
                        ${product.price ? parseFloat(product.price).toFixed(2) : '0.00'}
                    </span>
                </div>
                {/*end::Pricing*/}
            </div>
            {/*end::Body*/}

            {/* Hover Overlay */}
            {isHovered && (
                <div className="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center rounded-3">
                    <div className="text-center">
                        {isOutOfStock ? (
                            /* Out of Stock Message */
                            <div className="text-center">
                                <div className="mb-3">
                                    <span className="badge badge-light-danger fs-6 px-3 py-2">
                                        Out of Stock
                                    </span>
                                </div>
                                <div className="text-white fs-6">
                                    This product is currently unavailable
                                </div>
                            </div>
                        ) : (
                            <>
                                {/* Cart Count Display */}
                                {/* {quantityInCart > 0 && (
                                    <div className="mb-3">
                                        <span className="badge badge-primary fs-6 px-3 py-2">
                                            In Cart: {quantityInCart} units
                                        </span>
                                    </div>
                                )} */}
                                
                                {/* Increase/Decrease Buttons */}
                                <div className="d-flex align-items-center justify-content-center gap-2">
                                    <button
                                        className="btn btn-sm btn-light btn-icon"
                                        onClick={handleDecrease}
                                        disabled={quantityInCart === 0}
                                        style={{ 
                                            width: '40px', 
                                            height: '40px',
                                            borderRadius: '50%',
                                            border: 'none',
                                            backgroundColor: quantityInCart === 0 ? '#e9ecef' : '#ffffff',
                                            color: quantityInCart === 0 ? '#6c757d' : '#000000'
                                        }}
                                    >
                                        <i className="fas fa-minus"></i>
                                    </button>
                                    
                                    <span className="text-white fw-bold fs-4 mx-3">
                                        {quantityInCart}
                                    </span>
                                    
                                    <button
                                        className="btn btn-sm btn-primary btn-icon"
                                        onClick={handleIncrease}
                                        style={{ 
                                            width: '40px', 
                                            height: '40px',
                                            borderRadius: '50%',
                                            border: 'none',
                                            backgroundColor: '#0d6efd',
                                            color: '#ffffff'
                                        }}
                                    >
                                        <i className="fas fa-plus"></i>
                                    </button>
                                </div>
                                
                                {/* Add to Cart Button (if not in cart) */}
                                {quantityInCart === 0 && (
                                    <div className="mt-3">
                                        <button
                                            className="btn btn-primary"
                                            onClick={handleAddToCart}
                                        >
                                            Add to Cart
                                        </button>
                                    </div>
                                )}
                                
                                {/* Product Details */}
                                <div className="mt-3 text-white fs-8">
                                    <div>Price: ${product.price?.toFixed(2) || '0.00'}</div>
                                    <div>Available: {product.qty || 0} {product.unit || 'units'}</div>
                                    {/* {product.brand && <div>Brand: {product.brand}</div>}
                                    {product.category && <div>Category: {product.category}</div>} */}
                                </div>
                            </>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};

export default ProductCard;


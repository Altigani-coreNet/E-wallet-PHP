import React, { useState, useEffect } from 'react';
import { get } from '../../../utils/api';
import { API_ENDPOINTS } from '../../../utils/constants';

export default function CompositeProductRepeater({ items = [], onChange, onTotalChange }) {
    const [compositeItems, setCompositeItems] = useState(items.length > 0 ? items : [{ product_id: '', qty: 1, netUnitCost: 0 }]);
    const [availableProducts, setAvailableProducts] = useState([]);

    useEffect(() => {
        fetchProducts();
    }, []);

    useEffect(() => {
        // Calculate total cost
        const total = compositeItems.reduce((sum, item) => {
            return sum + (parseFloat(item.qty) || 0) * (parseFloat(item.netUnitCost) || 0);
        }, 0);
        
        onTotalChange && onTotalChange(total);
    }, [compositeItems]);

    const fetchProducts = async () => {
        try {
            const response = await get(API_ENDPOINTS.PRODUCTS.LIST, {
                params: { per_page: 1000 }
            });
            
            if (response.data.success !== false) {
                const products = response.data.data?.products || [];
                setAvailableProducts(products);
            }
        } catch (error) {
            console.error('Error fetching products:', error);
        }
    };

    const addItem = () => {
        const newItems = [...compositeItems, { product_id: '', qty: 1, netUnitCost: 0 }];
        setCompositeItems(newItems);
        onChange && onChange(newItems);
    };

    const removeItem = (index) => {
        const newItems = compositeItems.filter((_, i) => i !== index);
        setCompositeItems(newItems);
        onChange && onChange(newItems);
    };

    const updateItem = (index, field, value) => {
        const newItems = [...compositeItems];
        newItems[index] = { ...newItems[index], [field]: value };
        
        // If product is selected, auto-fill the price
        if (field === 'product_id') {
            const selectedProduct = availableProducts.find(p => p.id === parseInt(value));
            if (selectedProduct) {
                newItems[index].netUnitCost = selectedProduct.base_price || selectedProduct.cost || 0;
            }
        }
        
        setCompositeItems(newItems);
        onChange && onChange(newItems);
    };

    const calculateSubtotal = (item) => {
        return (parseFloat(item.qty) || 0) * (parseFloat(item.netUnitCost) || 0);
    };

    return (
        <div className="composite-products-repeater">
            <div className="d-flex justify-content-between align-items-center mb-5">
                <h3 className="fs-5 fw-bold mb-0">Composite Products</h3>
                <button 
                    type="button" 
                    className="btn btn-sm btn-light-primary"
                    onClick={addItem}
                >
                    <i className="ki-duotone ki-plus fs-2"></i>
                    Add Item
                </button>
            </div>

            <div className="table-responsive">
                <table className="table table-row-bordered">
                    <thead>
                        <tr className="fw-bold fs-6 text-gray-800">
                            <th className="min-w-200px">Product</th>
                            <th className="min-w-100px">SKU</th>
                            <th className="min-w-100px">Quantity</th>
                            <th className="min-w-100px">Unit Cost</th>
                            <th className="min-w-100px">Subtotal</th>
                            <th className="w-50px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {compositeItems.map((item, index) => {
                            const selectedProduct = availableProducts.find(p => p.id === parseInt(item.product_id));
                            
                            return (
                                <tr key={index}>
                                    <td>
                                        <select 
                                            className="form-select form-select-sm"
                                            value={item.product_id}
                                            onChange={(e) => updateItem(index, 'product_id', e.target.value)}
                                            required
                                        >
                                            <option value="">Select Product</option>
                                            {availableProducts.map(product => (
                                                <option key={product.id} value={product.id}>
                                                    {product.product_name || product.name}
                                                </option>
                                            ))}
                                        </select>
                                    </td>
                                    <td>
                                        <input 
                                            type="text" 
                                            className="form-control form-control-sm"
                                            value={selectedProduct?.sku || ''}
                                            readOnly
                                        />
                                    </td>
                                    <td>
                                        <input 
                                            type="number" 
                                            className="form-control form-control-sm"
                                            value={item.qty}
                                            onChange={(e) => updateItem(index, 'qty', e.target.value)}
                                            min="1"
                                            required
                                        />
                                    </td>
                                    <td>
                                        <input 
                                            type="number" 
                                            className="form-control form-control-sm"
                                            value={item.netUnitCost}
                                            onChange={(e) => updateItem(index, 'netUnitCost', e.target.value)}
                                            step="0.01"
                                            min="0"
                                            readOnly
                                        />
                                    </td>
                                    <td>
                                        <input 
                                            type="number" 
                                            className="form-control form-control-sm"
                                            value={calculateSubtotal(item).toFixed(2)}
                                            readOnly
                                        />
                                    </td>
                                    <td>
                                        <button 
                                            type="button"
                                            className="btn btn-sm btn-icon btn-light-danger"
                                            onClick={() => removeItem(index)}
                                            disabled={compositeItems.length === 1}
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
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}


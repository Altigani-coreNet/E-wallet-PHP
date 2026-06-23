import React, { useState, useEffect } from 'react';
import { put, get, getToken } from '../../utils/api';
import { API_ENDPOINTS } from '../../utils/constants';
import { useParams } from 'react-router-dom';
import ImageUploader from './Products/ImageUploader';
import CompositeProductRepeater from './Products/CompositeProductRepeater';
import RichTextEditor from './Products/RichTextEditor';

export default function ProductEdit() {
    const { id } = useParams();
    const [loading, setLoading] = useState(false);
    const [loadingProduct, setLoadingProduct] = useState(true);
    const [selectOptions, setSelectOptions] = useState(null);
    const [formData, setFormData] = useState(null);

    useEffect(() => {
        fetchProduct();
        fetchSelectOptions();
    }, [id]);

    const fetchProduct = async () => {
        try {
            // Check cache first
            const cacheKey = `product_${id}`;
            const cached = sessionStorage.getItem(cacheKey);
            
            if (cached) {
                const cachedData = JSON.parse(cached);
                // Check if cache is less than 5 minutes old
                if (Date.now() - cachedData.timestamp < 5 * 60 * 1000) {
                    setFormData(cachedData.data);
                    setLoadingProduct(false);
                    return;
                }
            }

            // Fetch from API if no cache or expired
            const response = await get(API_ENDPOINTS.PRODUCTS.DETAILS(id));
            if (response.data.success !== false) {
                const product = response.data.data.product;
                
                const formattedData = {
                    product_name: product.product_name || '',
                    description: product.description || '',
                    sku: product.sku || '',
                    barcode: product.barcode || '',
                    product_type: product.product_type || 'Standard',
                    product_status: product.product_status || 'draft',
                    scheduled_date: product.scheduled_date || '',
                    base_price: product.base_price || '',
                    sale_price: product.sale_price || '',
                    discount_type: product.discount_type || '1',
                    tax_type: product.tax_type || 'inclusive',
                    tax_id: product.tax_id || '',
                    quantity: product.quantity || '',
                    warehouse_id: product.warehouses?.[0]?.id || '',
                    warehouse_quantity: product.warehouses?.[0]?.quantity || '',
                    brand_id: product.brand_id || '',
                    unit_id: product.unit_id || '',
                    categories: product.categories?.map(c => c.id.toString()) || [],
                    tags: product.tags?.map(t => t.id.toString()) || [],
                    is_featured: product.is_featured || false,
                    is_variant: product.is_variant || false,
                    is_batch: product.is_batch || false,
                    serial_imei_number: product.serial_imei_number || false,
                    product_image: product.product_image ? { url: product.thumbnail } : null,
                    product_images: product.images || [],
                    composite_items: product.composite_items || []
                };
                
                setFormData(formattedData);
                
                // Cache the data
                sessionStorage.setItem(cacheKey, JSON.stringify({
                    data: formattedData,
                    timestamp: Date.now()
                }));
                
                setLoadingProduct(false);
            }
        } catch (error) {
            console.error('Error fetching product:', error);
            alert('Failed to load product');
            setLoadingProduct(false);
        }
    };

    const fetchSelectOptions = async () => {
        try {
            // Check cache first
            const cached = sessionStorage.getItem('product_select_options');
            if (cached) {
                const cachedData = JSON.parse(cached);
                // Check if cache is less than 5 minutes old
                if (Date.now() - cachedData.timestamp < 5 * 60 * 1000) {
                    setSelectOptions(cachedData.data);
                    return;
                }
            }

            // Fetch from API if no cache or expired
            const response = await get(API_ENDPOINTS.PRODUCTS.SELECT_OPTIONS);
            if (response.data.success !== false) {
                const data = response.data.data;
                setSelectOptions(data);
                
                // Cache the data
                sessionStorage.setItem('product_select_options', JSON.stringify({
                    data: data,
                    timestamp: Date.now()
                }));
            }
        } catch (error) {
            console.error('Error fetching select options:', error);
        }
    };

    const handleChange = (field, value) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        const token = getToken();
        if (!token) {
            alert('Authentication required. Please login again.');
            return;
        }

        try {
            setLoading(true);

            // Prepare data for submission
            const submitData = {
                ...formData,
                categories: formData.categories,
                tags: formData.tags,
                is_featured: formData.is_featured ? 1 : 0,
                is_variant: formData.is_variant ? 1 : 0,
                is_batch: formData.is_batch ? 1 : 0,
                serial_imei_number: formData.serial_imei_number ? 1 : 0,
                avatar: formData.product_image?.url || null,
                product_images: formData.product_images.map(img => img.url || img),
            };

            // Add composite items if product type is Combo
            if (formData.product_type === 'Combo') {
                submitData.composite_items = formData.composite_items;
            }

            const response = await put(API_ENDPOINTS.PRODUCTS.UPDATE(id), submitData);

            if (response.data.success !== false) {
                // Clear cache for this product
                sessionStorage.removeItem(`product_${id}`);
                
                // Clear all products list caches so they refresh
                Object.keys(sessionStorage).forEach(key => {
                    if (key.startsWith('products_list_cache')) {
                        sessionStorage.removeItem(key);
                    }
                });
                
                alert('Product updated successfully!');
                // Navigate back to products list
                const event = new CustomEvent('spa-navigate', {
                    detail: { route: '/merchant/sales/products' }
                });
                window.dispatchEvent(event);
            } else {
                alert(response.data.message || 'Failed to update product');
            }

        } catch (error) {
            console.error('Error updating product:', error);
            alert('Failed to update product: ' + (error.response?.data?.message || error.message));
        } finally {
            setLoading(false);
        }
    };

    if (loadingProduct || !formData || !selectOptions) {
        return (
            <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
                <div className="spinner-border text-primary" role="status">
                    <span className="visually-hidden">Loading...</span>
                </div>
            </div>
        );
    }

    return (
        <div className="d-flex flex-column flex-column-fluid">
            {/* Toolbar */}
            <div id="kt_app_toolbar" className="app-toolbar py-3 py-lg-6">
                <div id="kt_app_toolbar_container" className="app-container container-xxl d-flex flex-stack">
                    <div className="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                        <h1 className="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                            Edit Product
                        </h1>
                        <ul className="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                            <li className="breadcrumb-item text-muted">
                                <a href="/merchant/sales/dashboard" className="text-muted text-hover-primary">Home</a>
                            </li>
                            <li className="breadcrumb-item"><span className="bullet bg-gray-500 w-5px h-2px"></span></li>
                            <li className="breadcrumb-item text-muted">
                                <a 
                                    href="/merchant/sales/products" 
                                    className="text-muted text-hover-primary"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        const event = new CustomEvent('spa-navigate', {
                                            detail: { route: '/merchant/sales/products' }
                                        });
                                        window.dispatchEvent(event);
                                    }}
                                >
                                    Products
                                </a>
                            </li>
                            <li className="breadcrumb-item"><span className="bullet bg-gray-500 w-5px h-2px"></span></li>
                            <li className="breadcrumb-item text-muted">Edit</li>
                        </ul>
                    </div>
                </div>
            </div>

            {/* Content */}
            <div id="kt_app_content" className="app-content flex-column-fluid">
                <div id="kt_app_content_container" className="app-container container-xxl">
                    <form onSubmit={handleSubmit} className="form d-flex flex-column flex-lg-row">
                        {/* Sidebar */}
                        <div className="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
                            {/* Thumbnail */}
                            <div className="card card-flush py-4">
                                <div className="card-header">
                                    <div className="card-title">
                                        <h2>Thumbnail</h2>
                                    </div>
                                </div>
                                <div className="card-body text-center pt-0">
                                    <ImageUploader
                                        type="thumbnail"
                                        existingImages={formData.product_image ? [formData.product_image] : []}
                                        onUploadSuccess={(image) => handleChange('product_image', image)}
                                        label=""
                                    />
                                </div>
                            </div>

                            {/* Product Type */}
                            <div className="card card-flush py-4">
                                <div className="card-header">
                                    <div className="card-title">
                                        <h2>Product Template</h2>
                                    </div>
                                </div>
                                <div className="card-body pt-0">
                                    <select 
                                        className="form-select" 
                                        value={formData.product_type}
                                        onChange={(e) => handleChange('product_type', e.target.value)}
                                    >
                                        <option value="Standard">Standard Product</option>
                                        <option value="Combo">Composite Product</option>
                                        <option value="Digital">Digital Product</option>
                                    </select>
                                </div>
                            </div>

                            {/* Status */}
                            <div className="card card-flush py-4">
                                <div className="card-header">
                                    <div className="card-title">
                                        <h2>Status</h2>
                                    </div>
                                </div>
                                <div className="card-body pt-0">
                                    <select 
                                        className="form-select"
                                        value={formData.product_status}
                                        onChange={(e) => handleChange('product_status', e.target.value)}
                                    >
                                        {selectOptions.productStatuses?.map(status => (
                                            <option key={status} value={status}>{status}</option>
                                        ))}
                                    </select>
                                    {formData.product_status === 'scheduled' && (
                                        <div className="mt-3">
                                            <label className="form-label">Scheduled Date</label>
                                            <input 
                                                type="date"
                                                className="form-control"
                                                value={formData.scheduled_date}
                                                onChange={(e) => handleChange('scheduled_date', e.target.value)}
                                            />
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Product Details */}
                            <div className="card card-flush py-4">
                                <div className="card-header">
                                    <div className="card-title">
                                        <h2>Product Details</h2>
                                    </div>
                                </div>
                                <div className="card-body pt-0">
                                    {/* Categories */}
                                    <div className="mb-5">
                                        <label className="form-label">Categories *</label>
                                        <select 
                                            className="form-select"
                                            multiple
                                            value={formData.categories}
                                            onChange={(e) => handleChange('categories', Array.from(e.target.selectedOptions, option => option.value))}
                                            size="4"
                                        >
                                            {selectOptions.categories?.map(cat => (
                                                <option key={cat.id} value={cat.id.toString()}>
                                                    {typeof cat.name === 'object' ? (cat.name.en || cat.name.ar || JSON.stringify(cat.name)) : cat.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    {/* Tags */}
                                    <div className="mb-5">
                                        <label className="form-label">Tags</label>
                                        <select 
                                            className="form-select"
                                            multiple
                                            value={formData.tags}
                                            onChange={(e) => handleChange('tags', Array.from(e.target.selectedOptions, option => option.value))}
                                            size="4"
                                        >
                                            {selectOptions.tags?.map(tag => (
                                                <option key={tag.id} value={tag.id.toString()}>
                                                    {typeof tag.name === 'object' ? (tag.name.en || tag.name.ar || JSON.stringify(tag.name)) : tag.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    {/* Brand */}
                                    <div className="mb-5">
                                        <label className="form-label">Brand *</label>
                                        <select 
                                            className="form-select"
                                            value={formData.brand_id}
                                            onChange={(e) => handleChange('brand_id', e.target.value)}
                                            required
                                        >
                                            <option value="">Select Brand</option>
                                            {selectOptions.brands?.map(brand => (
                                                <option key={brand.id} value={brand.id}>
                                                    {typeof brand.name === 'object' ? (brand.name.en || brand.name.ar || JSON.stringify(brand.name)) : brand.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    {/* Unit */}
                                    <div className="mb-0">
                                        <label className="form-label">Product Unit *</label>
                                        <select 
                                            className="form-select"
                                            value={formData.unit_id}
                                            onChange={(e) => handleChange('unit_id', e.target.value)}
                                            required
                                        >
                                            <option value="">Select Unit</option>
                                            {selectOptions.units?.map(unit => (
                                                <option key={unit.id} value={unit.id}>
                                                    {typeof unit.name === 'object' ? (unit.name.en || unit.name.ar || JSON.stringify(unit.name)) : unit.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Main Column (same as ProductCreate but with existing data) */}
                        <div className="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
                            {/* General */}
                            <div className="card card-flush py-4">
                                <div className="card-header">
                                    <div className="card-title">
                                        <h2>General</h2>
                                    </div>
                                </div>
                                <div className="card-body pt-0">
                                    <div className="mb-5">
                                        <label className="form-label required">Product Name</label>
                                        <input 
                                            type="text"
                                            className="form-control"
                                            value={formData.product_name}
                                            onChange={(e) => handleChange('product_name', e.target.value)}
                                            required
                                        />
                                    </div>

                                    <div className="mb-5">
                                        <label className="form-label">Description</label>
                                        <RichTextEditor
                                            value={formData.description}
                                            onChange={(value) => handleChange('description', value)}
                                        />
                                    </div>

                                    {/* Checkboxes */}
                                    <div className="row">
                                        <div className="col-md-3">
                                            <div className="form-check mb-3">
                                                <input 
                                                    className="form-check-input"
                                                    type="checkbox"
                                                    checked={formData.is_featured}
                                                    onChange={(e) => handleChange('is_featured', e.target.checked)}
                                                />
                                                <label className="form-check-label">Featured</label>
                                            </div>
                                        </div>
                                        <div className="col-md-3">
                                            <div className="form-check mb-3">
                                                <input 
                                                    className="form-check-input"
                                                    type="checkbox"
                                                    checked={formData.is_variant}
                                                    onChange={(e) => handleChange('is_variant', e.target.checked)}
                                                />
                                                <label className="form-check-label">Variant</label>
                                            </div>
                                        </div>
                                        <div className="col-md-3">
                                            <div className="form-check mb-3">
                                                <input 
                                                    className="form-check-input"
                                                    type="checkbox"
                                                    checked={formData.is_batch}
                                                    onChange={(e) => handleChange('is_batch', e.target.checked)}
                                                />
                                                <label className="form-check-label">Batch</label>
                                            </div>
                                        </div>
                                        <div className="col-md-3">
                                            <div className="form-check mb-3">
                                                <input 
                                                    className="form-check-input"
                                                    type="checkbox"
                                                    checked={formData.serial_imei_number}
                                                    onChange={(e) => handleChange('serial_imei_number', e.target.checked)}
                                                />
                                                <label className="form-check-label">Serial/IMEI</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Media */}
                            <div className="card card-flush py-4">
                                <div className="card-header">
                                    <div className="card-title">
                                        <h2>Media</h2>
                                    </div>
                                </div>
                                <div className="card-body pt-0">
                                    <ImageUploader
                                        type="gallery"
                                        multiple={true}
                                        existingImages={formData.product_images}
                                        onUploadSuccess={(images) => handleChange('product_images', images)}
                                        label="Product Gallery"
                                    />
                                </div>
                            </div>

                            {/* Pricing */}
                            <div className="card card-flush py-4">
                                <div className="card-header">
                                    <div className="card-title">
                                        <h2>Pricing</h2>
                                    </div>
                                </div>
                                <div className="card-body pt-0">
                                    <div className="row">
                                        <div className="col-md-6 mb-5">
                                            <label className="form-label required">Base Price</label>
                                            <input 
                                                type="number"
                                                step="0.01"
                                                className="form-control"
                                                value={formData.base_price}
                                                onChange={(e) => handleChange('base_price', e.target.value)}
                                                required
                                                readOnly={formData.product_type === 'Combo'}
                                            />
                                        </div>
                                        <div className="col-md-6 mb-5">
                                            <label className="form-label required">Sale Price</label>
                                            <input 
                                                type="number"
                                                step="0.01"
                                                className="form-control"
                                                value={formData.sale_price}
                                                onChange={(e) => handleChange('sale_price', e.target.value)}
                                                required
                                            />
                                        </div>
                                    </div>

                                    <div className="row">
                                        <div className="col-md-6 mb-5">
                                            <label className="form-label required">Vat Type</label>
                                            <select 
                                                className="form-select"
                                                value={formData.tax_type}
                                                onChange={(e) => handleChange('tax_type', e.target.value)}
                                            >
                                                {selectOptions.taxTypes?.map(type => (
                                                    <option key={type} value={type}>{type}</option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="col-md-6 mb-0">
                                            <label className="form-label required">Vat</label>
                                            <select 
                                                className="form-select"
                                                value={formData.tax_id}
                                                onChange={(e) => handleChange('tax_id', e.target.value)}
                                            >
                                                <option value="">Select Tax</option>
                                                {selectOptions.taxes?.map(tax => (
                                                    <option key={tax.id} value={tax.id}>
                                                        {typeof tax.name === 'object' ? (tax.name.en || tax.name.ar || JSON.stringify(tax.name)) : tax.name} ({tax.rate}%)
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Inventory */}
                            <div className="card card-flush py-4">
                                <div className="card-header">
                                    <div className="card-title">
                                        <h2>Inventory</h2>
                                    </div>
                                </div>
                                <div className="card-body pt-0">
                                    <div className="row">
                                        <div className="col-md-4 mb-5">
                                            <label className="form-label required">SKU</label>
                                            <input 
                                                type="text"
                                                className="form-control"
                                                value={formData.sku}
                                                onChange={(e) => handleChange('sku', e.target.value)}
                                                required
                                            />
                                        </div>
                                        <div className="col-md-4 mb-5">
                                            <label className="form-label">Barcode</label>
                                            <input 
                                                type="text"
                                                className="form-control"
                                                value={formData.barcode}
                                                onChange={(e) => handleChange('barcode', e.target.value)}
                                            />
                                        </div>
                                        <div className="col-md-4 mb-5">
                                            <label className="form-label required">Quantity</label>
                                            <input 
                                                type="number"
                                                className="form-control"
                                                value={formData.quantity}
                                                onChange={(e) => handleChange('quantity', e.target.value)}
                                                required
                                            />
                                        </div>
                                    </div>

                                    <div className="row">
                                        <div className="col-md-6 mb-0">
                                            <label className="form-label">Warehouse</label>
                                            <select 
                                                className="form-select"
                                                value={formData.warehouse_id}
                                                onChange={(e) => handleChange('warehouse_id', e.target.value)}
                                            >
                                                <option value="">Select Warehouse</option>
                                                {selectOptions.warehouses?.map(wh => (
                                                    <option key={wh.id} value={wh.id}>
                                                        {typeof wh.name === 'object' ? (wh.name.en || wh.name.ar || JSON.stringify(wh.name)) : wh.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="col-md-6 mb-0">
                                            <label className="form-label">Warehouse Quantity</label>
                                            <input 
                                                type="number"
                                                className="form-control"
                                                value={formData.warehouse_quantity}
                                                onChange={(e) => handleChange('warehouse_quantity', e.target.value)}
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Composite Products */}
                            {formData.product_type === 'Combo' && (
                                <div className="card card-flush py-4">
                                    <div className="card-header">
                                        <div className="card-title">
                                            <h2>Composite Products</h2>
                                        </div>
                                    </div>
                                    <div className="card-body pt-0">
                                        <CompositeProductRepeater
                                            items={formData.composite_items}
                                            onChange={(items) => handleChange('composite_items', items)}
                                            onTotalChange={(total) => handleChange('base_price', total.toFixed(2))}
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Actions */}
                            <div className="d-flex justify-content-end">
                                <button 
                                    type="button"
                                    className="btn btn-light me-3"
                                    onClick={() => {
                                        const event = new CustomEvent('spa-navigate', {
                                            detail: { route: '/merchant/sales/products' }
                                        });
                                        window.dispatchEvent(event);
                                    }}
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="submit"
                                    className="btn btn-primary"
                                    disabled={loading}
                                >
                                    {loading ? (
                                        <>
                                            <span className="spinner-border spinner-border-sm me-2"></span>
                                            Updating...
                                        </>
                                    ) : 'Update Product'}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}


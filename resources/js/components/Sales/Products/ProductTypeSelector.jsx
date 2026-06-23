import React from 'react';

export default function ProductTypeSelector({ value, onChange }) {
    const productTypes = [
        { value: 'Standard', label: 'Standard Product', description: 'Physical product with inventory tracking' },
        { value: 'Combo', label: 'Composite Product', description: 'Bundle of multiple products' },
        { value: 'Digital', label: 'Digital Product', description: 'Downloadable or virtual product' }
    ];

    return (
        <div className="fv-row mb-10">
            <label className="form-label">Product Type</label>
            <div className="row row-cols-1 row-cols-md-3 g-5">
                {productTypes.map((type) => (
                    <div className="col" key={type.value}>
                        <label 
                            className={`btn btn-outline btn-outline-dashed w-100 p-5 d-flex flex-column align-items-start ${
                                value === type.value ? 'btn-active-light-primary active' : ''
                            }`}
                            style={{ cursor: 'pointer', height: '100%' }}
                        >
                            <input
                                type="radio"
                                className="btn-check"
                                name="product_type"
                                value={type.value}
                                checked={value === type.value}
                                onChange={(e) => onChange(e.target.value)}
                            />
                            <span className="fs-4 fw-bold text-gray-800 d-block mb-2">
                                {type.label}
                            </span>
                            <span className="text-muted fs-7">
                                {type.description}
                            </span>
                        </label>
                    </div>
                ))}
            </div>
        </div>
    );
}


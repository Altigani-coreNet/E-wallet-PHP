import React from 'react';

const ProductLoadingSkeleton = ({ count = 12 }) => {
    const skeletons = Array.from({ length: count }, (_, index) => index);

    return (
        <div className="d-flex flex-wrap justify-content-center align-items-center gap-xxl-9">
            {skeletons.map((index) => (
                <div key={index} className="card card-flush flex-row-fluid p-6 pb-5 mw-100 col-md-4">
                    <div className="card-body text-center">
                        {/* Image skeleton */}
                        <div className="product-skeleton-image skeleton" />
                        
                        {/* Title skeleton */}
                        <div className="mb-2">
                            <div className="product-skeleton-title skeleton" />
                            <div className="product-skeleton-subtitle skeleton" />
                        </div>
                        
                        {/* Price skeleton */}
                        <div className="product-skeleton-price skeleton" />
                    </div>
                </div>
            ))}
            

        </div>
    );
};

export default ProductLoadingSkeleton;


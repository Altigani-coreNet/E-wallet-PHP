import React, { useEffect, useCallback, useMemo } from 'react';
import usePosStore from '../../store/usePosStore';
import useProductsStore from '../../store/products';
import ProductCard from './ProductCard';
import ProductLoadingSkeleton from './ProductLoadingSkeleton';
import useInfiniteScroll from '../../hooks/useInfiniteScroll';
import ErrorBoundary from './ErrorBoundary';

const ProductsGrid = () => {
    const { 
        products, 
        productsLoading, 
        productsHasMore, 
        productsCurrentPage,
        fetchProducts, 
        fetchMoreProducts 
    } = useProductsStore();
    const { searchTerm, activeCategory, activeBrand } = usePosStore();
    
    // Filter products based on various criteria
    const filteredProducts = useMemo(() => {
        if (!products || products.length === 0) return [];
        
        return products.filter(product => {
            // Filter by search term
            if (searchTerm) {
                const searchLower = searchTerm.toLowerCase();
                const matchesName = product.name?.toLowerCase().includes(searchLower);
                const matchesCode = product.code?.toLowerCase().includes(searchLower);
                
                if (!matchesName && !matchesCode) {
                    return false;
                }
            }
            
            // Filter by category (using category name since we don't have category_id)
            if (activeCategory && product.category !== activeCategory.name) {
                return false;
            }
            
            // Filter by brand (using brand name since we don't have brand_id)
            if (activeBrand && product.brand !== activeBrand.name) {
                return false;
            }
            
            return true;
        });
    }, [products, searchTerm, activeCategory, activeBrand]);
    
    // Fetch products on component mount and when filters change
    useEffect(() => {
        const categoryId = activeCategory?.id || null;
        const brandId = activeBrand?.id || null;
        
        fetchProducts(1, false, searchTerm, categoryId, brandId);
    }, [fetchProducts, searchTerm, activeCategory, activeBrand]);

    // Handle infinite scroll
    const handleLoadMore = useCallback(() => {
        if (!productsLoading && productsHasMore) {
            const categoryId = activeCategory?.id || null;
            const brandId = activeBrand?.id || null;
            fetchMoreProducts(searchTerm, categoryId, brandId);
        }
    }, [productsLoading, productsHasMore, fetchMoreProducts, searchTerm, activeCategory, activeBrand]);

    // Set up infinite scroll
    useInfiniteScroll(handleLoadMore, productsHasMore, productsLoading);

    if (productsLoading && (!products || products.length === 0)) {
        return <ProductLoadingSkeleton count={12} />;
    }

    if (!filteredProducts || filteredProducts.length === 0) {
        return (
            <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
                <div className="text-center">
                    <i className="ki-duotone ki-information-5 fs-2hx text-muted mb-4">
                        <span className="path1"></span>
                        <span className="path2"></span>
                        <span className="path3"></span>
                    </i>
                    <h3 className="text-muted">No products found</h3>
                    <p className="text-muted">
                        {searchTerm || activeCategory || activeBrand 
                            ? "Try adjusting your search or filter criteria." 
                            : "No products available in the system."
                        }
                    </p>
                    {(searchTerm || activeCategory || activeBrand) && (
                        <button 
                            className="btn btn-primary"
                            onClick={() => {
                                // Reset filters - you might need to implement this in your store
                                window.location.reload();
                            }}
                        >
                            Clear Filters
                        </button>
                    )}
                </div>
            </div>
        );
    }

    return (
        <ErrorBoundary>
            <div className="d-flex flex-wrap justify-content-center align-items-center gap-xxl-9">
                {filteredProducts.map((product) => (
                    <ProductCard key={product.id} product={product} />
                ))}
            </div>
            
            {/* Loading indicator for infinite scroll */}
            {productsLoading && filteredProducts.length > 0 && (
                <div className="d-flex justify-content-center align-items-center mt-4">
                    <div className="spinner-border text-primary" role="status">
                        <span className="visually-hidden">Loading more products...</span>
                    </div>
                </div>
            )}
            
            {/* End of products indicator */}
            {!productsHasMore && filteredProducts.length > 0 && (
                <div className="text-center mt-4">
                    <p className="text-muted">
                        Showing {filteredProducts.length} of {products.length} products
                    </p>
                    <p className="text-muted">No more products to load</p>
                </div>
            )}
            
            {/* Products summary */}
            {filteredProducts.length > 0 && (
                <div className="text-center mt-3">
                    <small className="text-muted">
                        {filteredProducts.length} product{filteredProducts.length !== 1 ? 's' : ''} found
                        {searchTerm && ` for "${searchTerm}"`}
                        {activeCategory && ` in ${activeCategory.name}`}
                        {activeBrand && ` from ${activeBrand.name}`}
                    </small>
                </div>
            )}
        </ErrorBoundary>
    );
};

export default ProductsGrid;


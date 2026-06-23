import React, { useEffect, useState, useRef, useCallback } from 'react';
import BrandItem from './BrandItem';
import BrandLoadingItem from './BrandLoadingItem';
import usePosStore from '../../store/usePosStore';

const BrandsNav = () => {
    const {
        brands,
        activeBrand,
        brandsLoading,
        brandsHasMore,
        setActiveBrand,
        fetchMoreBrands
    } = usePosStore();

    const brandsScrollRef = useRef(null);
    const [isScrollingLeft, setIsScrollingLeft] = useState(false);
    const [lastScrollLeft, setLastScrollLeft] = useState(0);
    const scrollTimeoutRef = useRef(null);

    // Debounced scroll handler
    const debouncedScrollHandler = useCallback(
        async (scrollLeft, scrollWidth, clientWidth, isScrollingRightDirection) => {
            // Check if scrolling right and near the end
            const isNearEnd = scrollLeft + clientWidth >= scrollWidth - 150; // 150px threshold
            
            // Debug logging
            console.log('=== BRANDS SCROLL DEBUG ===');
            console.log('scrollLeft:', scrollLeft);
            console.log('clientWidth:', clientWidth);
            console.log('scrollWidth:', scrollWidth);
            console.log('scrollLeft + clientWidth:', scrollLeft + clientWidth);
            console.log('scrollWidth - 150:', scrollWidth - 150);
            console.log('isNearEnd:', isNearEnd);
            console.log('isScrollingRightDirection:', isScrollingRightDirection);
            console.log('brandsHasMore:', brandsHasMore);
            console.log('brandsLoading:', brandsLoading);
            console.log('===================');
            
            if (isScrollingRightDirection && isNearEnd && brandsHasMore && !brandsLoading) {
                console.log('🚀 CONDITIONS MET - Fetching more brands...');
                try {
                    await fetchMoreBrands();
                    console.log('✅ API call completed');
                } catch (error) {
                    console.error('❌ API call failed:', error);
                }
            } else {
                console.log('❌ CONDITIONS NOT MET:');
                console.log('  - isScrollingRightDirection:', isScrollingRightDirection);
                console.log('  - isNearEnd:', isNearEnd);
                console.log('  - brandsHasMore:', brandsHasMore);
                console.log('  - !brandsLoading:', !brandsLoading);
            }
        },
        [brandsHasMore, brandsLoading, fetchMoreBrands]
    );

    // Add scroll event listener
    useEffect(() => {
        const scrollContainer = brandsScrollRef.current;
        if (scrollContainer) {
            const handleScroll = (event) => {
                const scrollContainer = event.target;
                const { scrollLeft, scrollWidth, clientWidth } = scrollContainer;
                
                // Detect scroll direction
                const isScrollingRightDirection = true;
                setIsScrollingLeft(!isScrollingRightDirection);
                setLastScrollLeft(scrollLeft);
                
                // Clear existing timeout
                if (scrollTimeoutRef.current) {
                    clearTimeout(scrollTimeoutRef.current);
                }
                
                // Set new timeout for debounced handling
                scrollTimeoutRef.current = setTimeout(() => {
                    debouncedScrollHandler(scrollLeft, scrollWidth, clientWidth, isScrollingRightDirection);
                }, 100);
                // console.log(scrollLeft, scrollWidth, clientWidth, isScrollingRightDirection);
            };
            
            scrollContainer.addEventListener('scroll', handleScroll);
            
            return () => {
                scrollContainer.removeEventListener('scroll', handleScroll);
                if (scrollTimeoutRef.current) {
                    clearTimeout(scrollTimeoutRef.current);
                }
            };
        }
    }, [lastScrollLeft, debouncedScrollHandler]);

    return (
        <ul 
            ref={brandsScrollRef}
            className="nav nav-pills d-flex flex-nowrap nav-pills-custom gap-3 mb-6 overflow-x-auto pb-2" 
            role="tablist" 
            style={{
                scrollbarWidth: 'thin', 
                msOverflowStyle: 'none', 
                WebkitOverflowScrolling: 'touch'
            }}
        >
            {/* Loading cards at the beginning when fetching more */}
            {brandsLoading && (
                <BrandLoadingItem count={3} />
            )}
            
            {/* Brands */}
            {brands && brands.map((brand) => (
                <BrandItem
                    key={brand.id}
                    brand={brand}
                    isActive={activeBrand === brand.id}
                    onClick={() => setActiveBrand(brand)}
                />
            ))}
            
            {/* Loading item when there are more brands */}
            {brandsHasMore && !brandsLoading && (
                <BrandLoadingItem count={1} />
            )}
        </ul>
    );
};

export default BrandsNav;


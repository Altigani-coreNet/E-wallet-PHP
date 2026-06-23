import React, { useEffect, useState, useRef, useCallback } from 'react';
import CategoryItem from './CategoryItem';
import CategoryLoadingItem from './CategoryLoadingItem';
import usePosStore from '../../store/usePosStore';

const CategoriesNav = () => {
    const {
        categories,
        activeCategory,
        categoriesLoading,
        categoriesHasMore,
        setActiveCategory,
        fetchMoreCategories
    } = usePosStore();

    const categoriesScrollRef = useRef(null);
    const [isScrollingLeft, setIsScrollingLeft] = useState(false);
    const [lastScrollLeft, setLastScrollLeft] = useState(0);
    const scrollTimeoutRef = useRef(null);

    // Debounced scroll handler
    const debouncedScrollHandler = useCallback(
        async (scrollLeft, scrollWidth, clientWidth, isScrollingRightDirection) => {
            // Check if scrolling right and near the end
            const isNearEnd = scrollLeft + clientWidth >= scrollWidth - 150; // 150px threshold
            
            
            
            if (isScrollingRightDirection && isNearEnd && categoriesHasMore && !categoriesLoading) {
                try {
                    await fetchMoreCategories();
                } catch (error) {
                    console.error('API call failed:', error);
                }
            }
        },
        [categoriesHasMore, categoriesLoading, fetchMoreCategories]
    );

    // Add scroll event listener
    useEffect(() => {
        const scrollContainer = categoriesScrollRef.current;
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
            ref={categoriesScrollRef}
            className="nav nav-pills d-flex flex-nowrap nav-pills-custom gap-3 mb-6 overflow-x-auto pb-2" 
            role="tablist" 
            style={{
                scrollbarWidth: 'thin', 
                msOverflowStyle: 'none', 
                WebkitOverflowScrolling: 'touch'
            }}
        >
            {/* Loading cards at the beginning when fetching more */}
            {categoriesLoading && (
                <CategoryLoadingItem count={3} />
            )}
            
            {/* Categories */}
            {categories && categories.map((category) => (
                <CategoryItem
                    key={category.id}
                    category={category}
                    isActive={activeCategory === category.id}
                    onClick={() => setActiveCategory(category)}
                />
            ))}
            
            {/* Loading item when there are more categories */}
            {categoriesHasMore && !categoriesLoading && (
                <CategoryLoadingItem count={1} />
            )}
        </ul>
    );
};

export default CategoriesNav;


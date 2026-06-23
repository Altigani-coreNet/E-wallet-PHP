import React, { useEffect } from 'react';
import usePosStore from '../../store/usePosStore';
import Breadcrumbs from './Breadcrumbs';
import NavigationTypeSelector from './NavigationTypeSelector';
import CategoriesNav from './CategoriesNav';
import BrandsNav from './BrandsNav';
import ProductsGrid from './ProductsGrid';
import CartSection from './CartSection';
import { apiGet } from '../../utils/apiUtils';

const PosIndex = () => {
    const {
        navigationType,
        fetchProducts,
        fetchCategories,
        fetchBrands,
        processSale,
        applyDiscount
    } = usePosStore();

    // Hide toolbar for POS view
    useEffect(() => {
        

        // Cleanup: show toolbar when component unmounts
        return () => {
            const toolbar = document.getElementById("kt_app_toolbar");
            if (toolbar) {
                toolbar.style.display = "";
            }
        };
    }, []);

    // Initialize data on component mount
    useEffect(() => {
        // Fetch categories, brands, and products from API
        const initializeData = async () => {
            await fetchCategories(1); // Start with page 1
            await fetchBrands(1); // Start with page 1
            await fetchProducts(1); // Start with page 1
        };

        // Initialize data
        initializeData();
    }, [fetchCategories, fetchBrands, fetchProducts]);

    const handleProcessSale = () => {
        try {
            const sale = processSale();
            alert(`Sale processed successfully! Total: $${sale.total.toFixed(2)}`);
        } catch (error) {
            alert(error.message);
        }
    };

    const handleDiscountApply = () => {
        const discountAmount = parseFloat(prompt('Enter discount amount:') || '0');
        applyDiscount(discountAmount);
    };

    return (
        <div id="kt_app_content_container" className="app-container container-xxl">
            <Breadcrumbs title="POS System" breadcrumbs={[{ text: "Home", href: "/" }, { text: "Dashboards" }]} />
            {/*begin::Layout*/}
            <div className="d-flex flex-column flex-xl-row">
                {/*begin::Content*/}
                <div className="d-flex flex-row-fluid me-xl-9 mb-10 mb-xl-0">
                    {/*begin::Pos food*/}
                    <div className="card card-flush card-p-0 bg-transparent border-0 w-100">
                        {/*begin::Body*/}
                        <div className="card-body">
                            {/*begin::Navigation Type Selector*/}
                            <NavigationTypeSelector />
                            {/*end::Navigation Type Selector*/}
                            
                            {/*begin::Nav*/}
                            {navigationType === 'categories' ? (
                                <CategoriesNav />
                            ) : (
                                <BrandsNav />
                            )}
                            {/*end::Nav*/}
                            
                            {/*begin::Search Bar*/}
                            {/* <div className="mb-6">
                                <div className="position-relative">
                                    <i className="ki-duotone ki-magnifier fs-3 position-absolute ms-4 top-50 translate-middle-y">
                                        <span className="path1"></span>
                                        <span className="path2"></span>
                                    </i>
                                    <input
                                        type="text"
                                        className="form-control form-control-lg ps-12"
                                        placeholder="Search products..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                    />
                                </div>
                            </div> */}
                            {/*end::Search Bar*/}
                            
                            {/*begin::Products Grid*/}
                            <ProductsGrid />
                            {/*end::Products Grid*/}
                        </div>
                        {/*end: Card Body*/}
                    </div>
                    {/*end::Pos food*/}
                </div>
                {/*end::Content*/}
                {/*begin::Sidebar*/}
                <CartSection />
                {/*end::Sidebar*/}
            </div>
            {/*end::Layout*/}
        </div>
    );
};

export default PosIndex;


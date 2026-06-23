import { create } from 'zustand';
import { devtools, persist } from 'zustand/middleware';
import { apiGet } from '../utils/apiUtils';
import CategoryModel from '../models/CategoryModel';
import BrandModel from '../models/BrandModel';
import ProductModel from '../models/ProductModel';
import { API_V_2, API_V_1 , POS_API_BASE } from '../utils/constants';

const usePosStore = create()(
    devtools(
        persist(
            (set, get) => ({
                // Auth State
                token: null,

                // Cart State
                cart: [],
                cartTotal: 0,
                tax: 0,
                discount: 0,
                paymentMethod: '1', // Default to Card payment method

                // Product State
                products: [],
                selectedProduct: null,
                productsCurrentPage: 1,
                productsHasMore: true,
                productsLoading: false,

                // Category State
                categories: [],
                activeCategory: null,
                categoriesCurrentPage: 1,
                categoriesHasMore: true,
                categoriesLoading: false,
                brands: [],
                activeBrand: null,
                brandsCurrentPage: 1,
                brandsHasMore: true,
                brandsLoading: false,

                // Brand State
                brands: [],
                activeBrand: null,
                brandsCurrentPage: 1,
                brandsHasMore: true,
                brandsLoading: false,

                // Customer State
                customers: [],
                selectedCustomer: null,
                customersLoading: false,
                customerGroups: [],
                customerGroupsLoading: false,

                // Sales State
                sales: [],
                currentSale: null,

                // UI State
                isLoading: false,
                searchTerm: '',
                showCustomerModal: false,
                showProductModal: false,
                navigationType: 'categories', // Default to categories
                isFullscreen: false, // Fullscreen state

                // Cart Actions
                addToCart: (product) => {
                    const cart = get().cart;
                    // console.log(product.qty);

                    const existingItem = cart.find(item => item.id === product.id);

                    if (existingItem) {
                        set({
                            cart: cart.map(item =>
                                item.id === product.id
                                    ? { ...item, quantity: item.quantity + 1 }
                                    : item
                            )
                        });
                    } else {
                        set({
                            cart: [...cart, { ...product, quantity: 1 }]
                        });
                    }

                    get().calculateCartTotal();
                },

                removeFromCart: (productId) => {
                    set({
                        cart: get().cart.filter(item => item.id !== productId)
                    });
                    get().calculateCartTotal();
                },

                updateQuantity: (productId, quantity) => {
                    if (quantity <= 0) {
                        get().removeFromCart(productId);
                        return;
                    }

                    set({
                        cart: get().cart.map(item =>
                            item.id === productId
                                ? { ...item, quantity }
                                : item
                        )
                    });
                    get().calculateCartTotal();
                },

                clearCart: () => {
                    set({
                        cart: [],
                        cartTotal: 0,
                        tax: 0,
                        discount: 0
                    });
                },

                calculateCartTotal: () => {
                    const cart = get().cart;
                    const subtotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
                    const tax = cart.reduce((total, item) =>{
                        // console.log( 'items ---' , item);
                       return  total + (item.tax * item.quantity)}, 0); // 10% tax
                    const discount = get().discount;
                    const total = subtotal + tax - discount;

                    set({
                        cartTotal: total,
                        tax
                    });
                },

                applyDiscount: (discountAmount) => {
                    set({ discount: discountAmount });
                    get().calculateCartTotal();
                },

                setPaymentMethod: (method) => {
                    set({ paymentMethod: method });
                },

                // Product Actions
                setProducts: (products) => set({ products }),
                selectProduct: (product) => set({ selectedProduct: product }),

                appendProducts: (newProducts) => {
                    const currentProducts = get().products;
                    const uniqueProducts = [...currentProducts];

                    // Add only new products (avoid duplicates)
                    newProducts.forEach(newProduct => {
                        if (!uniqueProducts.some(prod => prod.id === newProduct.id)) {
                            uniqueProducts.push(newProduct);
                        }
                    });

                    set({ products: uniqueProducts });
                },

                // Category Actions
                setCategories: (categories) => set({ categories }),
                setActiveCategory: (category) => set({ activeCategory: category }),

                // Brand Actions
                setBrands: (brands) => set({ brands }),
                setActiveBrand: (brand) => set({ activeBrand: brand }),

                appendCategories: (newCategories) => {
                    const currentCategories = get().categories;
                    const uniqueCategories = [...currentCategories];

                    // Add only new categories (avoid duplicates)
                    newCategories.forEach(newCategory => {
                        if (!uniqueCategories.some(cat => cat.id === newCategory.id)) {
                            uniqueCategories.push(newCategory);
                        }
                    });

                    set({ categories: uniqueCategories });
                },

                appendBrands: (newBrands) => {
                    const currentBrands = get().brands;
                    const uniqueBrands = [...currentBrands];

                    // Add only new brands (avoid duplicates)
                    newBrands.forEach(newBrand => {
                        if (!uniqueBrands.some(brand => brand.id === newBrand.id)) {
                            uniqueBrands.push(newBrand);
                        }
                    });

                    set({ brands: uniqueBrands });
                },

                fetchCategories: async (page = 1, append = false) => {
                    set({ categoriesLoading: true });
                    try {
                        const response = await apiGet(`${POS_API_BASE}/api/v1/categories?page=${page}&per_page=7`);
                        if (response.success) {
                            // Map API response using CategoryModel
                            const categoriesData = response.data.data?.categories || [];
                            const mappedCategories = CategoryModel.fromApiResponseArray(categoriesData);
                            
                            if (append) {
                                get().appendCategories(mappedCategories);
                            } else {
                                set({ categories: mappedCategories });
                            }

                            // Calculate pagination info
                            const total = response.data.data?.total || 0;
                            const perPage = 10;
                            const hasMore = (page * perPage) < total;

                            // Update pagination info
                            set({
                                categoriesCurrentPage: page,
                                categoriesHasMore: hasMore
                            });
                        } else {
                            console.error('Failed to fetch categories:', response.error);
                        }
                    } catch (error) {
                        console.error('Error fetching categories:', error);
                    } finally {
                        set({ categoriesLoading: false });
                    }
                },

                fetchMoreCategories: async () => {
                    const { categoriesCurrentPage, categoriesHasMore, categoriesLoading } = get();

                    if (!categoriesHasMore || categoriesLoading) {
                        return;
                    }

                    await get().fetchCategories(categoriesCurrentPage + 1, true);
                },

                fetchBrands: async (page = 1, append = false) => {
                    set({ brandsLoading: true });
                    try {
                        const response = await apiGet(`${POS_API_BASE}/api/v1/brands?page=${page}&per_page=7`);
                        if (response.success) {
                            // Map API response using BrandModel
                            const brandsData = response.data.data?.brands || [];
                            const mappedBrands = BrandModel.fromApiResponseArray(brandsData);

                            if (append) {
                                get().appendBrands(mappedBrands);
                            } else {
                                set({ brands: mappedBrands });
                            }

                            // Calculate pagination info
                            const total = response.data.data?.total || 0;
                            const perPage = 10;
                            const hasMore = (page * perPage) < total;

                            // Update pagination info
                            set({
                                brandsCurrentPage: page,
                                brandsHasMore: hasMore
                            });
                        } else {
                            console.error('Failed to fetch brands:', response.error);
                        }
                    } catch (error) {
                        console.error('Error fetching brands:', error);
                    } finally {
                        set({ brandsLoading: false });
                    }
                },

                fetchMoreBrands: async () => {
                    const { brandsCurrentPage, brandsHasMore, brandsLoading } = get();

                    if (!brandsHasMore || brandsLoading) {
                        return;
                    }

                    await get().fetchBrands(brandsCurrentPage + 1, true);
                },

                fetchProducts: async (page = 1, append = false, search = '', categoryId = null, brandId = null) => {
                    set({ productsLoading: true });
                    try {
                        // Build query parameters
                        const params = {
                            page: page,
                            per_page: 12
                        };

                        // console.log(search, categoryId, brandId);
                        if (search) params.search = search;
                        if (categoryId) params.category_id = categoryId;
                        if (brandId) params.brand_id = brandId;

                        const response = await apiGet(`${POS_API_BASE}/api/v2/products`, params);

                        if (response.success) {
                            // Map API response using ProductModel
                            const productsData = response.data.data?.products || [];
                            const mappedProducts = ProductModel.fromApiResponseArray(productsData);
                            // console.log(mappedProducts , 'products');

                            if (append) {
                                get().appendProducts(mappedProducts);
                            } else {
                                set({ products: mappedProducts });
                            }

                            // Calculate pagination info
                            const total = response.data.data?.total || 0;
                            const perPage = 12;
                            const hasMore = (page * perPage) < total;

                            // Update pagination info
                            set({
                                productsCurrentPage: page,
                                productsHasMore: hasMore
                            });

                        } else {
                            console.error('Failed to fetch products:', response.error);
                        }
                    } catch (error) {
                        console.error('Error fetching products:', error);
                    } finally {
                        set({ productsLoading: false });
                    }
                },
                fetchProductsWithSearch: async (searchTerm, page = 1, append = false) => {
                    set({ productsLoading: true });
                    try {
                        const response = await apiGet(`${POS_API_BASE}/api/v2/products?page=${page}&per_page=12&search=${encodeURIComponent(searchTerm)}`);
                        if (response.success) {
                            // Map API response using ProductModel
                            const productsData = response.data.data?.products || [];
                            // console.log(productsData , 'products data');
                            const mappedProducts = ProductModel.fromApiResponseArray(productsData);
                            // console.log(mappedProducts , 'products with search');
                            if (append) {
                                get().appendProducts(mappedProducts);
                            } else {
                                set({ products: mappedProducts });
                            }

                            // Calculate pagination info
                            const total = response.data.data?.total || 0;
                            const perPage = 12;
                            const hasMore = (page * perPage) < total;

                            // Update pagination info
                            set({
                                productsCurrentPage: page,
                                productsHasMore: hasMore
                            });
                        } else {
                            console.error('Failed to fetch products with search:', response.error);
                        }
                    } catch (error) {
                        console.error('Error fetching products with search:', error);
                    } finally {
                        set({ productsLoading: false });
                    }
                },

                fetchMoreProducts: async () => {
                    const { productsCurrentPage, productsHasMore, productsLoading, activeCategory, activeBrand, searchTerm } = get();

                    if (!productsHasMore || productsLoading) {
                        return;
                    }

                    // Get category and brand IDs if they exist
                    const categoryId = activeCategory ? activeCategory.id : null;
                    const brandId = activeBrand ? activeBrand.id : null;

                    await get().fetchProducts(productsCurrentPage + 1, true, searchTerm, categoryId, brandId);
                },

                // Customer Actions
                setCustomers: (customers) => set({ customers }),
                selectCustomer: (customer) => set({ selectedCustomer: customer }),
                setCustomersLoading: (loading) => set({ customersLoading: loading }),

                fetchCustomers: async (search = '') => {
                    set({ customersLoading: true });
                    try {
                        const response = await apiGet(`${POS_API_BASE}/api/v1/customer/search`, { search });
                        if (response.success) {
                            set({ customers: response.data.data?.customers || [] });
                        } else {
                            console.error('Failed to fetch customers:', response.error);
                        }
                    } catch (error) {
                        console.error('Error fetching customers:', error);
                    } finally {
                        set({ customersLoading: false });
                    }
                },

                // Customer Groups Actions
                setCustomerGroups: (customerGroups) => set({ customerGroups }),
                setCustomerGroupsLoading: (loading) => set({ customerGroupsLoading: loading }),

                fetchCustomerGroups: async () => {
                    set({ customerGroupsLoading: true });
                    try {
                        const response = await apiGet(`${API_V_1}/customer/groups`);
                        if (response.success) {
                            // console.log(response.data.data.customerGroups , 'customer groups');
                            set({ customerGroups: response.data.data?.customerGroups || [] });
                        } else {
                            console.error('Failed to fetch customer groups:', response.error);
                        }
                    } catch (error) {
                        console.error('Error fetching customer groups:', error);
                    } finally {
                        set({ customerGroupsLoading: false });
                    }
                },

                // Sales Actions
                processSale: () => {
                    const { cart, cartTotal, selectedCustomer } = get();

                    if (cart.length === 0) {
                        throw new Error('Cart is empty');
                    }

                    const sale = {
                        id: Date.now(),
                        items: cart,
                        total: cartTotal,
                        customer: selectedCustomer,
                        timestamp: new Date().toISOString(),
                        status: 'completed'
                    };

                    set({
                        sales: [...get().sales, sale],
                        currentSale: sale
                    });

                    get().clearCart();
                    return sale;
                },

                // UI Actions
                setLoading: (isLoading) => set({ isLoading }),
                setSearchTerm: (searchTerm) => {
                    set({ searchTerm });
                    // Trigger API search when search term changes
                    if (searchTerm.trim()) {
                        get().fetchProductsWithSearch(searchTerm.trim());
                    } else {
                        // If search is empty, fetch all products
                        get().fetchProducts();
                    }
                },
                toggleCustomerModal: () => set({ showCustomerModal: !get().showCustomerModal }),
                toggleProductModal: () => set({ showProductModal: !get().showProductModal }),
                setNavigationType: (navigationType) => set({ navigationType }),
                toggleFullscreen: () => set({ isFullscreen: !get().isFullscreen }),

                // Filtered Data
                getFilteredProducts: () => {
                    const { products, searchTerm } = get();
                    if (!searchTerm) return products;

                    return products.filter(product =>
                        product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        product.code?.includes(searchTerm)
                    );
                },

                getCartItemCount: () => {
                    return get().cart.reduce((total, item) => total + item.quantity, 0);
                },

                getCartSubtotal: () => {
                    return get().cart.reduce((total, item) => total + (item.price * item.quantity), 0);
                },

                // Reset Store
                reset: () => {
                    set({
                        cart: [],
                        cartTotal: 0,
                        tax: 0,
                        discount: 0,
                        paymentMethod: '1',
                        selectedProduct: null,
                        selectedCustomer: null,
                        customers: [],
                        customersLoading: false,
                        currentSale: null,
                        categories: [],
                        activeCategory: null,
                        categoriesCurrentPage: 1,
                        categoriesHasMore: true,
                        categoriesLoading: false,
                        products: [],
                        productsCurrentPage: 1,
                        productsHasMore: true,
                        productsLoading: false,
                        isLoading: false,
                        searchTerm: '',
                        showCustomerModal: false,
                        showProductModal: false,
                        navigationType: 'categories',
                        isFullscreen: false,
                    });
                }
            }),
            {
                name: 'pos-store',
                partialize: (state) => ({
                    cart: state.cart,
                    cartTotal: state.cartTotal,
                    tax: state.tax,
                    discount: state.discount,
                    paymentMethod: state.paymentMethod,
                    selectedCustomer: state.selectedCustomer,
                    sales: state.sales,
                })
            }
        )
    )
);

export default usePosStore;


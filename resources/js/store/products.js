import { create } from 'zustand';
import { devtools, persist } from 'zustand/middleware';
import { apiGet } from '../utils/apiUtils';
import ProductModel from '../models/ProductModel';
import { API_V_2 } from '../utils/constants';
const useProductsStore = create()(
    devtools(
        persist(
            (set, get) => ({
                // Product State
                products: [],
                selectedProduct: null,
                productsCurrentPage: 1,
                productsHasMore: true,
                productsLoading: false,
                
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
                        
                        const response = await apiGet(`${API_V_2}/products`, params);
                        
                        if (response.success) {
                            // Map API response using ProductModel
                            const productsData = response.data.data?.products || [];
                            const mappedProducts = ProductModel.fromApiResponseArray(productsData);
                            // console.log(mappedProducts);

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
                
                fetchMoreProducts: async (searchTerm = '', categoryId = null, brandId = null) => {
                    const { productsCurrentPage, productsHasMore, productsLoading } = get();
                    
                    if (!productsHasMore || productsLoading) {
                        return;
                    }
                    
                    await get().fetchProducts(productsCurrentPage + 1, true, searchTerm, categoryId, brandId);
                },
                
                // Filtered Products
                getFilteredProducts: (searchTerm) => {
                    const { products } = get();
                    if (!searchTerm) return products;
                    
                    return products.filter(product =>
                        product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        product.code?.includes(searchTerm)
                    );
                },
                
                // Reset Products
                resetProducts: () => {
                    set({
                        products: [],
                        selectedProduct: null,
                        productsCurrentPage: 1,
                        productsHasMore: true,
                        productsLoading: false,
                    });
                }
            }),
            {
                name: 'products-store',
                partialize: (state) => ({
                    products: state.products,
                    selectedProduct: state.selectedProduct,
                })
            }
        )
    )
);

export default useProductsStore;


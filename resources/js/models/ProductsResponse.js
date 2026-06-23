// Product Model for mapping API response
export class ProductModel {
    constructor(data) {
        this.id = data.id || null;
        this.name = data.name || data.product_name || '';
        this.thumbnail = data.thumbnail || data.image || 'assets/media/stock/food/img-2.jpg';
        this.brand = data.brand || null;
        this.category = data.category || null;
        this.unit = data.unit || 'pcs';
        this.price = parseFloat(data.price) || parseFloat(data.sale_price) || 0;
        this.qty = parseInt(data.qty) || parseInt(data.quantity) || 0;
        this.code = data.code || '';
        this.discount = parseFloat(data.discount) || 0;
        this.tax = parseFloat(data.tax) || 0;
    }

    // Static method to map single product
    static fromApiResponse(apiData) {
        return new ProductModel(apiData);
    }

    // Static method to map array of products
    static fromApiResponseArray(apiDataArray) {
        if (!Array.isArray(apiDataArray)) {
            console.warn('Expected array but got:', typeof apiDataArray);
            return [];
        }
        
        return apiDataArray.map(item => ProductModel.fromApiResponse(item));
    }

    // Method to convert back to plain object
    toPlainObject() {
        return {
            id: this.id,
            name: this.name,
            thumbnail: this.thumbnail,
            brand: this.brand,
            category: this.category,
            unit: this.unit,
            price: this.price,
            qty: this.qty,
            code: this.code,
            discount: this.discount,
            tax: this.tax,
        };
    }

    // Method to check if product is in stock
    isInStock() {
        return this.qty > 0;
    }

    // Method to get display name with stock info
    getDisplayName() {
        return `${this.name} (${this.qty} ${this.unit})`;
    }
}

// Export default for convenience
export default ProductModel;


# Payment Links - Customer Fields Update ✅

## Overview
Successfully updated the Payment Links feature to store customer information directly in the `payment_by_links` table instead of using a foreign key relationship to the `customers` table.

## What Changed

### 1. **Database Changes**

#### Model Update
📁 `SoftPos/app/Models/PaymentByLink.php`

**Changes:**
- ✅ Added `customer_name`, `customer_phone`, `customer_email` to fillable
- ✅ Commented out `customer_id` from fillable
- ✅ Commented out the `customer()` relationship method

```php
protected $fillable = [
    'merchant_id',
    'status',
    'link',
    'payment_sdk',
    'amount',
    'currency_id',
    'payment_method_types',
    'scheduled_date',
    'expired_date',
    // 'customer_id', // Commented out - storing customer info directly
    'customer_name',
    'customer_phone',
    'customer_email',
    'country_id',
    'uuid',
    'short_uuid',
    'metadata',
    'payment_status',
];

// Commented out - storing customer info directly in payment_by_links table
// public function customer()
// {
//     return $this->belongsTo(Customer::class);
// }
```

#### Migration
📁 `SoftPos/database/migrations/2025_01_27_000001_add_customer_fields_to_payment_by_links_table.php`

**New migration to:**
- ✅ Add `customer_name` (string, nullable)
- ✅ Add `customer_phone` (string, nullable)
- ✅ Add `customer_email` (string, nullable)
- ✅ Make `customer_id` nullable

**To run the migration:**
```bash
php artisan migrate
```

### 2. **API Controller Updates**

📁 `SoftPos/app/Http/Controllers/Api/MerchantPaymentLinkApiController.php`

**Changes:**

#### Removed Customer Relationship Loading
```php
// OLD:
$query = PaymentByLink::where('merchant_id', $merchantId)
    ->with(['merchant', 'customer', 'currency']);

// NEW:
$query = PaymentByLink::where('merchant_id', $merchantId)
    ->with(['merchant', 'currency']);
```

#### Updated Validation
```php
// OLD:
'customer_id' => 'required|exists:customers,id',

// NEW:
'customer_name' => 'required|string|max:255',
'customer_phone' => 'nullable|string|max:20',
'customer_email' => 'nullable|email|max:255',
```

#### Updated Search Filters
```php
// OLD:
->orWhereHas('customer', function ($cq) use ($search) {
    $cq->where('name', 'like', "%{$search}%")
       ->orWhere('email', 'like', "%{$search}%");
})

// NEW:
->orWhere('customer_name', 'like', "%{$search}%")
->orWhere('customer_email', 'like', "%{$search}%")
->orWhere('customer_phone', 'like', "%{$search}%")
```

#### Updated Export CSV Headers
```php
// OLD:
['ID', 'UUID', 'Merchant', 'Customer', 'Amount', 'Currency', ...]

// NEW:
['ID', 'UUID', 'Merchant', 'Customer Name', 'Customer Email', 'Customer Phone', 'Amount', 'Currency', ...]
```

### 3. **React Components Updates**

#### PaymentLinkForm Component
📁 `SoftPos/resources/js/components/payment-links/PaymentLinkForm.jsx`

**Changes:**
- ✅ Replaced `customer_id` field with `customer_name`, `customer_phone`, `customer_email`
- ✅ Updated form state
- ✅ Updated form validation

**New Form Fields:**
```jsx
{/* Customer Name */}
<input
    type="text"
    name="customer_name"
    placeholder="Enter customer name"
    required
/>

{/* Customer Email */}
<input
    type="email"
    name="customer_email"
    placeholder="Enter customer email"
/>

{/* Customer Phone */}
<input
    type="tel"
    name="customer_phone"
    placeholder="Enter customer phone"
/>
```

#### PaymentLinksIndex Component
📁 `SoftPos/resources/js/components/payment-links/PaymentLinksIndex.jsx`

**Changes:**
- ✅ Removed `customers` state
- ✅ Removed `getCustomersByIds` import
- ✅ Removed customer fetching logic
- ✅ Removed `customers` prop from PaymentLinksTable

#### PaymentLinksTable Component
📁 `SoftPos/resources/js/components/payment-links/PaymentLinksTable.jsx`

**Changes:**
- ✅ Removed `customers` prop
- ✅ Removed `customer` prop passed to PaymentLinkTableRow

#### PaymentLinkTableRow Component
📁 `SoftPos/resources/js/components/payment-links/PaymentLinkTableRow.jsx`

**Changes:**
- ✅ Removed `customer` prop
- ✅ Display customer info directly from `paymentLink` object

**New Display Logic:**
```jsx
<td>
    <div className="d-flex align-items-center">
        <div className="d-flex flex-column">
            <span className="text-gray-800 text-hover-primary mb-1">
                {paymentLink.customer_name || 'N/A'}
            </span>
            {paymentLink.customer_email && (
                <span className="text-muted fs-7">
                    {paymentLink.customer_email}
                </span>
            )}
            {paymentLink.customer_phone && (
                <span className="text-muted fs-7">
                    {paymentLink.customer_phone}
                </span>
            )}
        </div>
    </div>
</td>
```

#### PaymentLinksFilters Component
📁 `SoftPos/resources/js/components/payment-links/PaymentLinksFilters.jsx`

**Changes:**
- ✅ Changed "Customer ID" label to "Customer Name"
- ✅ Changed placeholder to "Search by customer name"

#### SendModal Component
📁 `SoftPos/resources/js/components/payment-links/SendModal.jsx`

**Changes:**
- ✅ Display customer info directly from `paymentLink` object
- ✅ Show customer_name, customer_email, customer_phone

## Benefits of This Change

### ✅ **Simplified Architecture**
- No need to join with customers table
- Faster queries
- No dependency on customer records

### ✅ **Data Preservation**
- Customer information is preserved even if customer is deleted
- Payment link history remains intact
- Better for audit trails

### ✅ **Flexibility**
- Can create payment links without requiring a customer record
- Easier to create one-time payment links
- Better for guest/anonymous payments

### ✅ **Performance**
- Fewer database joins
- Faster API responses
- Reduced complexity

## Migration Steps

### 1. **Run the Migration**
```bash
cd SoftPos
php artisan migrate
```

### 2. **Test the Changes**

#### Create a Payment Link:
```bash
# Make a POST request to create
POST /v1/merchant/payment-links
{
    "amount": 100.00,
    "currency_id": 1,
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "customer_phone": "+1234567890",
    "payment_method_types": ["card"]
}
```

#### Update a Payment Link:
```bash
# Make a PUT request to update
PUT /v1/merchant/payment-links/1
{
    "amount": 150.00,
    "currency_id": 1,
    "customer_name": "John Doe Updated",
    "customer_email": "john.updated@example.com",
    "customer_phone": "+1234567890"
}
```

### 3. **Verify the UI**

Navigate to:
- `http://localhost:8001/merchant/payment-links/create`
- Fill in customer name, email, and phone
- Submit and verify data is saved correctly

## Data Migration (Optional)

If you have existing payment links with `customer_id`, you can migrate the data:

```php
// Create a migration or run this in tinker
use App\Models\PaymentByLink;
use App\Models\Customer;

PaymentByLink::whereNotNull('customer_id')->chunk(100, function ($links) {
    foreach ($links as $link) {
        if ($link->customer) {
            $link->update([
                'customer_name' => $link->customer->name,
                'customer_email' => $link->customer->email,
                'customer_phone' => $link->customer->phone,
            ]);
        }
    }
});
```

## API Request/Response Examples

### Create Payment Link Request:
```json
POST /v1/merchant/payment-links
{
    "amount": 100.50,
    "currency_id": 1,
    "customer_name": "Jane Smith",
    "customer_email": "jane@example.com",
    "customer_phone": "+1-555-0123",
    "scheduled_date": "2025-02-01",
    "expired_date": "2025-03-01",
    "payment_method_types": ["card", "alipay"]
}
```

### Response:
```json
{
    "success": true,
    "message": "Payment link created successfully",
    "data": {
        "id": 1,
        "uuid": "abc123-def456...",
        "merchant_id": 1,
        "amount": 100.50,
        "currency_id": 1,
        "customer_name": "Jane Smith",
        "customer_email": "jane@example.com",
        "customer_phone": "+1-555-0123",
        "status": "active",
        "link": "https://...",
        "created_at": "2025-01-27T10:00:00.000000Z",
        "updated_at": "2025-01-27T10:00:00.000000Z"
    }
}
```

### List Payment Links Response:
```json
{
    "success": true,
    "message": "Payment links retrieved successfully",
    "data": [
        {
            "id": 1,
            "uuid": "abc123-def456...",
            "merchant_id": 1,
            "amount": 100.50,
            "customer_name": "Jane Smith",
            "customer_email": "jane@example.com",
            "customer_phone": "+1-555-0123",
            "currency": {
                "id": 1,
                "currency_code": "USD",
                "symbol": "$"
            },
            "status": "active",
            "created_at": "2025-01-27T10:00:00.000000Z"
        }
    ],
    "pagination": {
        "total": 1,
        "per_page": 15,
        "current_page": 1,
        "last_page": 1
    }
}
```

## Search & Filter Examples

### Search by Customer Name:
```
GET /v1/merchant/payment-links?search=John
```

### Filter by Customer Name:
```
GET /v1/merchant/payment-links?customer=John
```

### Combined Filters:
```
GET /v1/merchant/payment-links?search=John&from_date=2025-01-01&to_date=2025-12-31
```

## Database Schema

### New `payment_by_links` Table Structure:
```sql
CREATE TABLE payment_by_links (
    id BIGINT UNSIGNED PRIMARY KEY,
    merchant_id BIGINT UNSIGNED NOT NULL,
    uuid VARCHAR(255) UNIQUE,
    short_uuid VARCHAR(255),
    amount DECIMAL(10,2),
    currency_id BIGINT UNSIGNED,
    status VARCHAR(50),
    
    -- Customer fields (NEW)
    customer_name VARCHAR(255),
    customer_email VARCHAR(255),
    customer_phone VARCHAR(20),
    
    -- Old field (now nullable)
    customer_id BIGINT UNSIGNED NULL,
    
    scheduled_date DATETIME,
    expired_date DATETIME,
    payment_method_types JSON,
    metadata JSON,
    payment_status VARCHAR(50),
    link TEXT,
    payment_sdk VARCHAR(255),
    country_id BIGINT UNSIGNED,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Troubleshooting

### Issue: "customer_name is required" validation error
**Solution:** Make sure you're sending `customer_name` in the request body (it's required).

### Issue: Existing payment links not showing customer data
**Solution:** Run the data migration script to copy customer data from the customers table.

### Issue: "Column not found: customer_name"
**Solution:** Run the migration: `php artisan migrate`

## Done! 🎉

The Payment Links feature now stores customer information directly without requiring a foreign key relationship to the customers table. This makes it more flexible and preserves data integrity!

## Next Steps

1. ✅ Run migration: `php artisan migrate`
2. ✅ Test creating new payment links
3. ✅ Test editing existing payment links
4. ✅ Verify search and filters work
5. ✅ Test export functionality
6. ✅ (Optional) Migrate existing customer data


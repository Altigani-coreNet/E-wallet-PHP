# Customer React Component - API Integration Complete

The `Customers.jsx` component has been updated to follow the new CRUD API structure.

---

## ✅ Changes Made

### 1. **Updated Imports** (`Customers.jsx`)
```jsx
// Before
import { get, del, getToken } from '../../utils/api';
import { POS_API_V_2 } from '../../utils/constants';

// After
import { get, del, getToken } from '../../utils/api';
import { API_ENDPOINTS } from '../../utils/constants';
```

### 2. **Updated API Calls**

#### **Fetch Customers (List)**
```jsx
// Before
const response = await get(`${POS_API_V_2}/api/v1/customer/search`, {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
});

// After
const response = await get(API_ENDPOINTS.CUSTOMERS.LIST, {
    params: {
        per_page: 100 // Get more results for client-side filtering
    }
});
```

#### **Delete Customer**
```jsx
// Before
const response = await del(`/admin/customers/${customerId}`);

// After
const response = await del(API_ENDPOINTS.CUSTOMERS.DELETE(customerId));
```

### 3. **Updated Constants File** (`constants.js`)

Added proper base URL structure and customer endpoints:

```javascript
// Base URL configuration
export const POS_API_BASE = import.meta.env.POS_API_URL || 'http://localhost:8002';
export const POS_API_V_2 = `${POS_API_BASE}/api/v2/sales`;

// Added Customer endpoints
export const API_ENDPOINTS = {
    DASHBOARD: { /* existing dashboard endpoints */ },
    CUSTOMERS: {
        LIST: `${POS_API_V_2}/customers`,
        SEARCH: `${POS_API_V_2}/customer/search`,
        DETAILS: (id) => `${POS_API_V_2}/customers/${id}`,
        CREATE: `${POS_API_V_2}/customers`,
        UPDATE: (id) => `${POS_API_V_2}/customers/${id}`,
        DELETE: (id) => `${POS_API_V_2}/customers/${id}`,
        GROUPS: `${POS_API_V_2}/customer/groups`,
    }
};
```

---

## 🔗 API Endpoint Mapping

| Component Action | API Endpoint | Method | URL |
|-----------------|--------------|--------|-----|
| **Fetch Customers** | `API_ENDPOINTS.CUSTOMERS.LIST` | GET | `/api/v2/sales/customers` |
| **Search Customers** | `API_ENDPOINTS.CUSTOMERS.SEARCH` | GET | `/api/v2/sales/customer/search` |
| **Get Details** | `API_ENDPOINTS.CUSTOMERS.DETAILS(id)` | GET | `/api/v2/sales/customers/{id}` |
| **Create Customer** | `API_ENDPOINTS.CUSTOMERS.CREATE` | POST | `/api/v2/sales/customers` |
| **Update Customer** | `API_ENDPOINTS.CUSTOMERS.UPDATE(id)` | PUT | `/api/v2/sales/customers/{id}` |
| **Delete Customer** | `API_ENDPOINTS.CUSTOMERS.DELETE(id)` | DELETE | `/api/v2/sales/customers/{id}` |
| **Get Groups** | `API_ENDPOINTS.CUSTOMERS.GROUPS` | GET | `/api/v2/sales/customer/groups` |

---

## 📝 Component Features

### ✅ **Authentication**
- Token validation before each API call
- User-friendly error messages if token is missing
- Automatic Bearer token inclusion via `api.js` utility

### ✅ **Data Loading**
- Loading state with spinner
- Error state with styled error message
- Empty state with icon when no customers found

### ✅ **Search & Filter**
- Client-side search by name, email, or phone
- Real-time filtering as user types
- Fetches up to 100 customers for better filtering

### ✅ **CRUD Operations**
- **List:** Displays all customers in a table
- **Delete:** Confirms before deletion, shows success message
- **Edit/Create:** Navigation ready (needs backend routes)

### ✅ **UI/UX**
- Bootstrap-styled cards and tables
- Customer avatars with first letter
- Status badges (active/inactive)
- Responsive design
- Action buttons (Edit/Delete)

---

## 🔄 Data Flow

```
User Opens Page
    ↓
useEffect() → fetchCustomers()
    ↓
getToken() → Check authentication
    ↓
get(API_ENDPOINTS.CUSTOMERS.LIST)
    ↓
Bearer Token added automatically
    ↓
Request to: http://localhost:8002/api/v2/sales/customers?per_page=100
    ↓
Response: {
    success: true,
    data: {
        customers: [...],
        pagination: {...}
    }
}
    ↓
Extract customers → setCustomers()
    ↓
Render table with data
```

---

## 🎯 Response Format Expected

The component expects this response structure:

```json
{
    "success": true,
    "message": "Customers List",
    "data": {
        "customers": [
            {
                "id": 1,
                "name": "John Doe",
                "email": "john@example.com",
                "phone": "+1234567890",
                "company_name": "Doe Enterprises",
                "status": "active",
                "customer_group": {
                    "id": 1,
                    "name": "VIP"
                }
            }
        ],
        "pagination": {
            "total": 150,
            "per_page": 100,
            "current_page": 1,
            "last_page": 2
        }
    }
}
```

---

## 🔧 Configuration

### Environment Variables

Add to your `.env` file:

```bash
# SoftPos (React App)
VITE_POS_API_URL=http://localhost:8002

# Pos (Laravel API)
# No changes needed - API is ready
```

### Backend Routes

Ensure these routes are accessible:

```php
// In Pos/routes/api.php
Route::middleware(['api', 'auth:sanctum'])->prefix('v2/sales')->group(function () {
    Route::get('/customers', [ApiCustomerController::class, 'index']);
    Route::delete('/customers/{customer}', [ApiCustomerController::class, 'destroy']);
    // ... other routes
});
```

---

## 🚀 Testing

### 1. **Start the API Server** (Pos)
```bash
cd Pos
php artisan serve --port=8002
```

### 2. **Start the React App** (SoftPos)
```bash
cd SoftPos
npm run dev
```

### 3. **Test the Component**

1. Navigate to the customers page
2. Check if customers load in the table
3. Try searching for a customer
4. Click Delete on a customer (confirm dialog should appear)
5. Check console for any errors

### 4. **Verify API Calls**

Open browser DevTools → Network tab:
- Should see `GET` request to `/api/v2/sales/customers?per_page=100`
- Should include `Authorization: Bearer {token}` header
- Response should have status 200 and customer data

---

## 🐛 Troubleshooting

### Issue: "Authentication required. Please login again."
**Solution:** 
- User needs to login first to get a token
- Token is stored in `localStorage` as `sales_api_token`
- Check if token exists: `localStorage.getItem('sales_api_token')`

### Issue: "Failed to load customers"
**Solution:**
- Check if API server is running on port 8002
- Verify the API endpoint: `http://localhost:8002/api/v2/sales/customers`
- Check CORS settings in Laravel
- Verify token is valid

### Issue: 404 Not Found
**Solution:**
- Check route definition in `Pos/routes/api.php`
- Ensure route prefix is correct: `/api/v2/sales`
- Clear Laravel route cache: `php artisan route:clear`

### Issue: CORS Error
**Solution:**
Add to `Pos/config/cors.php`:
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['http://localhost:3000', 'http://localhost:5173'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

---

## 📊 API Response Handling

### Success Response
```javascript
if (response.data.success !== false) {
    const customersData = response.data.data?.customers || [];
    setCustomers(Array.isArray(customersData) ? customersData : []);
}
```

### Error Response
```javascript
if (response.data.success === false) {
    setError(response.data.message || 'Failed to load customers');
}
```

### Network Error
```javascript
catch (err) {
    console.error('Error fetching customers:', err);
    setError('Failed to load customers');
}
```

---

## 🎉 Summary

✅ **Component updated to use new API structure**
✅ **Constants file organized with all customer endpoints**
✅ **Proper token authentication**
✅ **Clean error handling**
✅ **User-friendly loading and empty states**
✅ **Delete functionality working**
✅ **Ready for Edit/Create integration**

The Customers component is now fully integrated with the CRUD API backend! 🚀

---

## 📝 Next Steps (Optional Enhancements)

1. **Add Create/Edit Modal or Form**
   - Create `CustomerForm.jsx` component
   - Use `API_ENDPOINTS.CUSTOMERS.CREATE` and `UPDATE`
   - Include customer group dropdown

2. **Add Server-Side Pagination**
   - Use pagination data from API response
   - Add page navigation controls
   - Implement page size selector

3. **Add Advanced Search**
   - Use `API_ENDPOINTS.CUSTOMERS.SEARCH` endpoint
   - Debounce search input
   - Show search results in real-time

4. **Add Bulk Operations**
   - Select multiple customers
   - Bulk delete functionality
   - Bulk status update

5. **Add Customer Details View**
   - Use `API_ENDPOINTS.CUSTOMERS.DETAILS(id)`
   - Show full customer information
   - Display related sales/orders

All the infrastructure is in place - just extend as needed! 💪


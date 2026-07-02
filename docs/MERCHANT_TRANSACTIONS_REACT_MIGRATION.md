# Merchant Transactions React Migration

This document outlines the complete migration of the Merchant Transactions page from Blade/jQuery to React with API endpoints.

## 📋 Overview

The merchant transactions page has been converted to a modern React application with API-based communication, similar to the payment links implementation. This provides better performance, maintainability, and user experience.

## 🎯 Features Implemented

### ✅ Core Features
- **Server-side paginated DataTable** with sorting and filtering
- **Advanced Filters**: Search, status, payment type, terminal, date range
- **Real-time Statistics**: Sale, Refund, and Void transaction cards
- **Export Functionality**: CSV export with applied filters
- **Bulk Operations**: Multi-select and bulk delete
- **Transaction Actions**:
  - View transaction details
  - Void transactions
  - Refund transactions (full and partial)
  - Send receipt via email

### 🎨 UI Components
1. **MerchantTransactions** (Main component)
2. **TransactionFilters** (Filter panel)
3. **TransactionStatistics** (Statistics cards)
4. **TransactionActions** (Action buttons)

## 📁 Files Created/Modified

### New React Components
```
SoftPos/resources/js/components/merchant/
├── MerchantTransactions.jsx         # Main component with DataTable
├── TransactionFilters.jsx           # Filter controls
├── TransactionStatistics.jsx        # Statistics cards
└── TransactionActions.jsx           # Action buttons
```

### New Blade Template
```
SoftPos/resources/views/merchant/transactions/index-react.blade.php
```

### Modified Files
- `SoftPos/resources/js/merchant-app.jsx` - Added MerchantTransactions route and ToastContainer
- `SoftPos/app/Http/Controllers/MerchantTransactionController.php` - Added API support
- `SoftPos/routes/api.php` - Added API routes
- `SoftPos/package.json` - Added dependencies (react-data-table-component, react-toastify)

## 🔧 API Endpoints

All endpoints are under the `v1/merchant` prefix with JWT authentication middleware.

### Transaction Endpoints
```
GET     /api/v1/merchant/transactions/data              # Get paginated transactions
GET     /api/v1/merchant/transactions/statistics        # Get transaction statistics
GET     /api/v1/merchant/transactions/export            # Export transactions (CSV)
POST    /api/v1/merchant/transactions/bulk-delete       # Bulk delete transactions
GET     /api/v1/merchant/transactions/{id}              # Get single transaction
POST    /api/v1/merchant/transactions/{id}/void         # Void a transaction
POST    /api/v1/merchant/transactions/{id}/refund       # Refund a transaction
POST    /api/v1/merchant/transactions/{id}/send-receipt # Send receipt via email
GET     /api/v1/merchant/transactions/{id}/receipt      # View receipt
```

### Helper Endpoints
```
GET     /api/softpos/merchant/terminals                 # Get merchant terminals for dropdown
```

## 📝 Controller Updates

### New/Updated Methods in `MerchantTransactionController.php`

#### 1. `data()` - Enhanced for API
```php
// Now returns JSON for API requests, DataTables for web requests
if ($request->expectsJson() || $request->is('api/*')) {
    return response()->json([
        'data' => $transactions->items(),
        'total' => $transactions->total(),
        'per_page' => $transactions->perPage(),
        'current_page' => $transactions->currentPage(),
        'last_page' => $transactions->lastPage(),
    ]);
}
```

#### 2. `statistics()` - Enhanced for API
```php
// Returns JSON statistics for both API and web requests
public function statistics(Request $request, $merchantId = null)
{
    // ... calculate statistics
    
    if ($request->expectsJson() || $request->is('api/*')) {
        return response()->json($statistics);
    }
    return $statistics;
}
```

#### 3. `bulkDelete()` - New Method
```php
public function bulkDelete(Request $request)
{
    // Validates and deletes multiple transactions
    $deletedCount = Transaction::where('merchant_id', $merchant->id)
        ->whereIn('id', $request->ids)
        ->delete();
        
    return response()->json([
        'success' => true,
        'deleted_count' => $deletedCount
    ]);
}
```

#### 4. `getTerminals()` - New Method
```php
public function getTerminals(Request $request)
{
    // Returns active terminals for the merchant
    $terminals = $merchant->terminals()
        ->where('is_active', true)
        ->get();
        
    return response()->json($terminals);
}
```

## 🔐 Authentication & Authorization

### JWT Token Authentication
The application uses JWT tokens stored in localStorage:

```javascript
// In merchant-transactions.jsx
const jwtToken = localStorage.getItem('jwt_token');
if (jwtToken) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${jwtToken}`;
}
```

### Permission Checks
All endpoints check user permissions:
- `transactions` or `view_transactions` - View transactions
- `export_transactions` - Export functionality
- `delete_transactions` - Delete transactions
- `void_transactions` - Void transactions
- `refund_transactions` - Refund transactions
- `statistics` - View statistics

## 💻 Usage

### Installation

1. **Install npm dependencies:**
```bash
cd SoftPos
npm install
```

This will install:
- `react-data-table-component` - DataTable component
- `react-toastify` - Toast notifications

2. **Build assets:**
```bash
npm run build
# or for development
npm run dev
```

### Using the React Page

The transactions page is now integrated into the `merchant-app.jsx` router. Simply navigate to `/merchant/transactions` and it will automatically load the React component.

**Update your route to use the React view:**
```php
// routes/web.php
Route::get('/merchant/transactions', function() {
    $type = request()->get('type');
    return view('merchant.transactions.index-react', compact('type'));
})->middleware(['auth', 'merchant']);
```

The URL `/merchant/transactions` will automatically be handled by React Router in `merchant-app.jsx`.

### Configuration

The Blade template passes configuration to React through `window` object and `merchant-app.jsx` handles routing:

```javascript
// In the blade template
window.merchantTransactionsConfig = {
    merchantId: {{ auth()->user()->merchant_id }},
    type: '{{ $type ?? '' }}',
    apiBaseUrl: '{{ url('/api/v1/merchant') }}',
    csrfToken: '{{ csrf_token() }}'
};

window.merchantAppConfig = window.merchantAppConfig || {};
window.merchantAppConfig.merchantId = {{ auth()->user()->merchant_id }};
```

### Routing

The transactions page is integrated into the merchant app routing:

```javascript
// In merchant-app.jsx
<Route path="/merchant/transactions" element={<MerchantTransactions />} />
```

## 🎨 Component Usage

### Main Component
```jsx
<MerchantTransactions
    merchantId={merchantId}
    initialType={type}  // Optional: 'sale', 'refund', 'void'
/>
```

### Props
- `merchantId` (required) - The merchant ID
- `initialType` (optional) - Filter by transaction type on load

## 🔄 Filter System

### Available Filters
1. **Search** - Transaction ID, RRN, Auth Code
2. **Status** - APPROVED, DECLINED, PENDING, CAPTURED, VOIDED, REFUNDED
3. **Payment Type** - card, web, bank, mobile, qr, other
4. **Terminal** - Select from merchant's terminals
5. **Date Range** - Start date and end date

### Filter Summary
The filter panel shows:
- Active filter count
- Quick summary of applied filters
- Clear filters button

## 📊 Statistics Cards

Three cards display transaction metrics:

1. **Sale Transactions** (Green)
   - Count of APPROVED, PENDING, CAPTURED
   - Total amount

2. **Refund Transactions** (Red)
   - Count of REFUNDED
   - Total refunded amount

3. **Void Transactions** (Dark)
   - Count of VOIDED
   - Total voided amount

## 🎯 Transaction Actions

### View
Opens transaction detail page (still uses traditional route)

### Void
- Available for APPROVED and CAPTURED transactions
- Prompts for reason
- Updates transaction status to VOIDED

### Refund
- Available for APPROVED and CAPTURED transactions
- Prompts for amount and reason
- Validates amount against refundable amount
- Creates new refund transaction

### Send Receipt
- Available for all transactions
- Prompts for email and optional message
- Sends email with transaction receipt and PDF

## 📤 Export Functionality

### CSV Export
- Exports transactions with current filters applied
- Includes columns:
  - Transaction ID
  - RRN
  - Merchant
  - Payment Type
  - Card Number (masked)
  - Amount
  - Status
  - Batch No
  - SDK
  - Created At

### Usage
```javascript
const handleExport = async () => {
    const response = await axios.get(`/api/merchant/transactions/export`, {
        params: filters,
        responseType: 'blob'
    });
    // Download file
};
```

## 🎭 Bulk Operations

### Bulk Delete
1. Select transactions using checkboxes
2. Click "Delete Selected" button
3. Confirm deletion
4. Transactions are deleted

### Implementation
```javascript
const handleBulkDelete = async () => {
    const ids = selectedRows.map(row => row.id);
    await axios.post('/api/merchant/transactions/bulk-delete', { ids });
};
```

## 🔔 Toast Notifications

Uses `react-toastify` for user feedback:

```javascript
import { toast } from 'react-toastify';

// Success
toast.success('Transaction voided successfully');

// Error
toast.error('Failed to void transaction');

// Warning
toast.warning('Please select transactions to delete');
```

## 🎨 Styling

### DataTable Customization
```javascript
customStyles={{
    headRow: {
        style: {
            backgroundColor: '#f9fafb',
            fontSize: '14px',
            fontWeight: '600'
        }
    },
    rows: {
        style: {
            fontSize: '14px',
            '&:hover': {
                backgroundColor: '#f9fafb'
            }
        }
    }
}}
```

## 🐛 Error Handling

### API Errors
```javascript
try {
    const response = await axios.get('/api/merchant/transactions/data');
    setTransactions(response.data.data);
} catch (error) {
    console.error('Error fetching transactions:', error);
    toast.error('Failed to load transactions');
}
```

### Validation Errors
- Form validation using SweetAlert2
- Server-side validation in controller
- User-friendly error messages

## 📈 Performance Optimizations

1. **Server-side Pagination** - Only loads required data
2. **Debounced Search** - 500ms delay on search input
3. **Memoized Callbacks** - Using `useCallback` for optimization
4. **Lazy Loading** - Components loaded on demand

## 🔒 Security

1. **CSRF Protection** - Token included in all requests
2. **JWT Authentication** - Secure API access
3. **Permission Checks** - Server-side authorization
4. **Input Validation** - Both client and server side
5. **XSS Protection** - React automatically escapes content

## 🧪 Testing

### Test API Endpoints
```bash
# Test data endpoint
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://localhost:8000/api/v1/merchant/transactions/data?merchant_id=1

# Test statistics
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://localhost:8000/api/v1/merchant/transactions/statistics?merchant_id=1
```

### Test React Components
1. Navigate to `/merchant/transactions-react`
2. Test all filters
3. Test pagination
4. Test actions (void, refund, send receipt)
5. Test bulk operations
6. Test export

## 🚀 Deployment Checklist

- [ ] Run `npm install` to install dependencies
- [ ] Run `npm run build` to build production assets
- [ ] Clear Laravel cache: `php artisan cache:clear`
- [ ] Clear view cache: `php artisan view:clear`
- [ ] Test all API endpoints
- [ ] Test all UI features
- [ ] Verify permissions work correctly
- [ ] Test on different browsers

## 📚 Additional Resources

### Similar Implementations
- Payment Links React Migration
- Merchant Terminals React Implementation

### Documentation
- [React Data Table Component](https://react-data-table-component.netlify.app/)
- [React Toastify](https://fkhadra.github.io/react-toastify/introduction)
- [SweetAlert2](https://sweetalert2.github.io/)

## 🆘 Troubleshooting

### Issue: JWT Token Not Found
**Solution:** Ensure token is stored in localStorage after login:
```javascript
localStorage.setItem('jwt_token', token);
```

### Issue: 403 Unauthorized
**Solution:** Check user permissions in database

### Issue: CORS Errors
**Solution:** Verify CORS configuration in Laravel

### Issue: React Component Not Rendering
**Solution:** 
1. Check browser console for errors
2. Verify Vite build completed successfully
3. Check that merchant_id is passed correctly

## 📝 Migration from Old to New

### Before (Blade/jQuery)
```blade
@extends('layouts.merchant.merchant_layout')
@section('content')
    <!-- Blade template with jQuery -->
@endsection
```

### After (React)
```blade
@extends('layouts.merchant.merchant_layout')
@section('content')
    <div id="merchant-transactions-root"></div>
@endsection
@push('scripts')
    @vite(['resources/js/merchant-transactions.jsx'])
@endpush
```

## 🎉 Benefits

1. **Better Performance** - React's virtual DOM and efficient rendering
2. **Modern UI** - Enhanced user experience with smooth interactions
3. **Maintainability** - Cleaner, more organized code
4. **Scalability** - Easy to add new features
5. **Type Safety** - Can add TypeScript in the future
6. **Testing** - Easier to write unit and integration tests
7. **Unified App** - Integrated into merchant-app.jsx with other merchant features

## 🏗️ Architecture

### Integration with Merchant App

The transactions page is now part of the unified `merchant-app.jsx` architecture:

```
merchant-app.jsx (Main Router)
├── /merchant/branches          → BranchesIndex
├── /merchant/terminals         → TerminalsIndex
├── /merchant/payment-links     → PaymentLinksIndex
├── /merchant/transactions      → MerchantTransactions (NEW)
├── /merchant/contracts         → ContractView
├── /merchant/service-fees      → ServiceFeesIndex
└── /merchant/profile           → Profile
```

Benefits of this approach:
- **Single entry point** - One Vite build for all merchant features
- **Shared state** - Can share authentication and user state
- **Consistent UX** - ToastContainer and other shared UI elements
- **Better routing** - React Router handles all navigation
- **Smaller bundle** - Shared dependencies across all pages

## 📞 Support

For issues or questions:
1. Check this documentation
2. Review the code comments
3. Check similar implementations (Payment Links)
4. Contact the development team

---

**Last Updated:** October 2024
**Version:** 1.0.0
**Author:** Development Team


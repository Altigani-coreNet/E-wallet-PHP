# Payment Links - API & React Migration Complete ✅

## Overview
Successfully migrated the Payment Links feature from Blade templates to a modern API-driven React architecture, following the same pattern used for Branches and Terminals.

## What Was Created

### 1. **API Controller** 
📁 `SoftPos/app/Http/Controllers/Api/MerchantPaymentLinkApiController.php`

A complete RESTful API controller with the following endpoints:
- `GET /` - List payment links with pagination and filters
- `GET /{id}` - Get single payment link details
- `POST /` - Create new payment link
- `PUT /{id}` - Update payment link
- `DELETE /{id}` - Delete payment link
- `POST /bulk-delete` - Bulk delete payment links
- `POST /{id}/update-date` - Reschedule payment link
- `POST /{id}/send` - Send payment link via email/SMS/WhatsApp
- `GET /export` - Export payment links to CSV
- `GET /statistics` - Get payment link statistics

**Features:**
- JWT authentication via `external` guard
- Merchant isolation (users can only access their own payment links)
- Comprehensive error handling and logging
- Validation for all inputs
- Support for filtering, searching, and pagination

### 2. **Service Layer** 
📁 `SoftPos/resources/js/services/paymentLinksService.js`

Complete API service layer with methods for:
- CRUD operations (create, read, update, delete)
- Bulk operations
- Date rescheduling
- Sending payment links
- Statistics retrieval
- Export functionality

### 3. **React Components**

#### Main Components:
📁 `SoftPos/resources/js/components/payment-links/`

1. **PaymentLinksIndex.jsx** - Main container component
   - Displays statistics cards (Total, Active, Completed, Expired)
   - Manages state for payment links, filters, pagination
   - Handles bulk operations
   - Integrates all child components

2. **PaymentLinksTable.jsx** - Table display component
   - Renders payment links in a data table
   - Handles pagination (with page numbers)
   - Select all / individual selection
   - Responsive design

3. **PaymentLinkTableRow.jsx** - Individual row component
   - Displays payment link data
   - Action dropdown menu (View, Edit, Copy Link, Send, Reschedule, Delete)
   - Status badges with color coding
   - Date formatting with relative time
   - Copy to clipboard functionality

4. **PaymentLinksFilters.jsx** - Filters component
   - Search by text
   - Filter by customer ID
   - Date range filters (from/to)
   - Reset filters option

5. **PaymentLinkCreate.jsx** - Create page component
   - Uses shared form component
   - Success/error handling
   - Breadcrumb navigation

6. **PaymentLinkEdit.jsx** - Edit page component
   - Loads existing payment link data
   - Uses shared form component
   - Loading state while fetching data

7. **PaymentLinkForm.jsx** - Shared form component
   - Amount input
   - Currency selection
   - Customer ID input
   - Scheduled date picker
   - Expiry date picker
   - Payment method types (multiple selection)
   - Validation
   - Loading states

8. **RescheduleModal.jsx** - Modal for rescheduling
   - Date picker with minimum date validation
   - AJAX submission
   - Success feedback via SweetAlert2

9. **SendModal.jsx** - Modal for sending payment links
   - Checkboxes for Email/WhatsApp/SMS
   - Display payment link URL
   - Customer information display
   - AJAX submission

### 4. **Customer Service** 
📁 `SoftPos/resources/js/services/customersService.js`

Helper service for customer-related operations:
- Get customers by IDs (for displaying in payment links list)
- Get customers for select dropdown
- Get all customers with pagination

### 5. **API Routes** 
📁 `SoftPos/routes/api.php`

Added routes under `v1/merchant` prefix with JWT authentication:
```php
Route::prefix('payment-links')->group(function () {
    Route::get('/', [MerchantPaymentLinkApiController::class, 'index']);
    Route::get('/statistics', [MerchantPaymentLinkApiController::class, 'statistics']);
    Route::get('/export', [MerchantPaymentLinkApiController::class, 'export']);
    Route::post('/bulk-delete', [MerchantPaymentLinkApiController::class, 'bulkDelete']);
    Route::get('/{paymentLink}', [MerchantPaymentLinkApiController::class, 'show']);
    Route::post('/', [MerchantPaymentLinkApiController::class, 'store']);
    Route::put('/{paymentLink}', [MerchantPaymentLinkApiController::class, 'update']);
    Route::delete('/{paymentLink}', [MerchantPaymentLinkApiController::class, 'destroy']);
    Route::post('/{paymentLink}/update-date', [MerchantPaymentLinkApiController::class, 'updateDate']);
    Route::post('/{paymentLink}/send', [MerchantPaymentLinkApiController::class, 'send']);
});
```

## How to Use

### 1. **Setup**

Ensure your React app has the following:
- JWT token stored and accessible via `document.getElementById('merchant-app-root')?.dataset?.apiToken`
- Environment variable `VITE_AUTH_SERVICE_URL` set to your API base URL
- SweetAlert2 installed for notifications
- API utils (`apiGet`, `apiPost`, `apiPut`, `apiDelete`) configured

### 2. **Integrating the Components**

#### For the Index Page (List View):
```jsx
import PaymentLinksIndex from './components/payment-links/PaymentLinksIndex';

// In your app or router
<PaymentLinksIndex />
```

#### For the Create Page:
```jsx
import PaymentLinkCreate from './components/payment-links/PaymentLinkCreate';

<PaymentLinkCreate />
```

#### For the Edit Page:
```jsx
import PaymentLinkEdit from './components/payment-links/PaymentLinkEdit';

<PaymentLinkEdit paymentLinkId={paymentLinkId} />
```

### 3. **Update Your Routes**

Update your web routes to render the React components:

```php
// In routes/web.php
Route::middleware(['auth', 'merchant'])->prefix('merchant')->group(function () {
    Route::get('/payment-links', function () {
        return view('merchant.payment-links.react-index'); // React mount point
    })->name('merchant.payment-links.index');
    
    Route::get('/payment-links/create', function () {
        return view('merchant.payment-links.react-create');
    })->name('merchant.payment-links.create');
    
    Route::get('/payment-links/{id}/edit', function ($id) {
        return view('merchant.payment-links.react-edit', ['paymentLinkId' => $id]);
    })->name('merchant.payment-links.edit');
});
```

### 4. **Create Blade Views for React Mount Points**

Create simple Blade views that mount your React components:

**resources/views/merchant/payment-links/react-index.blade.php:**
```blade
@extends('layouts.merchant.merchant_layout')

@section('content')
<div id="payment-links-root"></div>
@endsection

@push('scripts')
<script type="module">
    import PaymentLinksIndex from '@/components/payment-links/PaymentLinksIndex.jsx';
    import { createRoot } from 'react-dom/client';
    
    const root = createRoot(document.getElementById('payment-links-root'));
    root.render(<PaymentLinksIndex />);
</script>
@endpush
```

## Features Implemented

### ✅ Core Features:
- [x] List payment links with pagination
- [x] Search and filter payment links
- [x] Create new payment link
- [x] Edit existing payment link
- [x] Delete payment link (with confirmation)
- [x] Bulk delete multiple payment links
- [x] View payment link details

### ✅ Advanced Features:
- [x] Statistics dashboard (Total, Active, Completed, Expired)
- [x] Copy payment link URL to clipboard
- [x] Send payment link via Email/WhatsApp/SMS
- [x] Reschedule payment link
- [x] Export payment links to CSV
- [x] Status badges with color coding
- [x] Relative date formatting
- [x] Responsive design
- [x] Loading states
- [x] Error handling
- [x] Success notifications

### ✅ Security Features:
- [x] JWT authentication required
- [x] Merchant isolation (users can only access their own data)
- [x] Input validation
- [x] CSRF protection
- [x] SQL injection prevention (via Eloquent)

## API Endpoints

All endpoints are prefixed with: `v1/merchant/payment-links`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | List all payment links |
| GET | `/{id}` | Get single payment link |
| POST | `/` | Create new payment link |
| PUT | `/{id}` | Update payment link |
| DELETE | `/{id}` | Delete payment link |
| POST | `/bulk-delete` | Delete multiple payment links |
| POST | `/{id}/update-date` | Reschedule payment link |
| POST | `/{id}/send` | Send payment link |
| GET | `/export` | Export to CSV |
| GET | `/statistics` | Get statistics |

### Request/Response Examples

#### Create Payment Link
```javascript
POST /v1/merchant/payment-links
{
    "amount": 100.50,
    "currency_id": 1,
    "customer_id": 5,
    "scheduled_date": "2025-11-01",
    "expired_date": "2025-12-31",
    "payment_method_types": ["card", "alipay"]
}

Response:
{
    "success": true,
    "message": "Payment link created successfully",
    "data": {
        "id": 1,
        "uuid": "abc123...",
        "amount": 100.50,
        "status": "active",
        ...
    }
}
```

#### List with Filters
```javascript
GET /v1/merchant/payment-links?page=1&per_page=15&search=test&customer=5&from_date=2025-01-01&to_date=2025-12-31

Response:
{
    "success": true,
    "message": "Payment links retrieved successfully",
    "data": [...],
    "pagination": {
        "total": 100,
        "per_page": 15,
        "current_page": 1,
        "last_page": 7
    }
}
```

## Status Codes

| Status | Description | Color |
|--------|-------------|-------|
| active | Payment link is active and can be used | Green |
| inactive | Payment link is inactive | Red |
| expired | Payment link has expired | Yellow |
| completed | Payment has been completed | Blue |
| scheduled | Payment link is scheduled for future | Purple |

## Payment Method Types

The following payment methods are supported:
- card
- afterpay_clearpay
- alipay
- bancontact
- eps
- giropay
- grabpay
- ideal
- klarna
- oxxo
- p24 (Przelewy24)
- sepa_debit
- sofort
- us_bank_account
- wechat_pay

## Migration from Blade

### Old Blade Implementation:
- File: `SoftPos/resources/views/merchant/payment-links/index.blade.php`
- Used DataTables with server-side processing
- jQuery for interactions
- Modal dialogs in Blade

### New React Implementation:
- Modern React components with hooks
- Client-side state management
- API-driven data fetching
- Reusable component architecture
- Better performance and user experience

## Next Steps

1. **Test the API endpoints** using Postman or similar tool
2. **Update your Blade views** to mount React components
3. **Ensure JWT authentication** is properly configured
4. **Test all features** in the browser
5. **Update any navigation menus** to point to the new routes

## Troubleshooting

### Common Issues:

1. **Authentication errors:**
   - Ensure JWT token is properly set in the API token data attribute
   - Check that the `external` guard is configured in `auth.php`

2. **404 errors:**
   - Verify routes are registered in `api.php`
   - Check that the controller class is imported

3. **Unauthorized access:**
   - Ensure the authenticated user has a `merchant_id`
   - Check that the `getMerchantId()` method returns the correct value

4. **CORS issues:**
   - Configure CORS settings in `config/cors.php`
   - Ensure the API accepts requests from your frontend domain

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     React Frontend                          │
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐                 │
│  │ PaymentLinks    │  │ PaymentLink     │                 │
│  │ Index           │  │ Create/Edit     │                 │
│  └────────┬────────┘  └────────┬────────┘                 │
│           │                     │                          │
│           └──────────┬──────────┘                          │
│                      │                                     │
│           ┌──────────▼─────────┐                          │
│           │ paymentLinks       │                          │
│           │ Service            │                          │
│           └──────────┬─────────┘                          │
└──────────────────────┼──────────────────────────────────┘
                       │ API Calls (JWT Auth)
                       │
┌──────────────────────▼──────────────────────────────────┐
│                  Laravel Backend                        │
│                                                         │
│  ┌──────────────────────────────────────────────────┐ │
│  │ JwtAuthMiddleware                                │ │
│  └──────────┬───────────────────────────────────────┘ │
│             │                                          │
│  ┌──────────▼──────────────────────────────────────┐ │
│  │ MerchantPaymentLinkApiController                │ │
│  │ - index()    - store()    - send()              │ │
│  │ - show()     - update()   - updateDate()        │ │
│  │ - destroy()  - bulkDelete()  - export()         │ │
│  └──────────┬──────────────────────────────────────┘ │
│             │                                          │
│  ┌──────────▼──────────────────────────────────────┐ │
│  │ PaymentByLinkService                            │ │
│  └──────────┬──────────────────────────────────────┘ │
│             │                                          │
│  ┌──────────▼──────────────────────────────────────┐ │
│  │ PaymentByLink Model                             │ │
│  │ (payment_by_links table)                        │ │
│  └─────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────┘
```

## Comparison with Existing Implementations

This implementation follows the exact same pattern as:
- ✅ **Terminals** (`MerchantTerminalApiController.php` + React components)
- ✅ **Branches** (`branchesService.js` + React components)

Benefits:
- Consistent codebase
- Easy to maintain
- Familiar patterns for developers
- Reusable components

## Done! 🎉

You now have a complete API-driven React implementation for Payment Links that matches the quality and architecture of your Branches and Terminals features!


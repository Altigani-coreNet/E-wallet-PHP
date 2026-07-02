# 📊 Merchant Dashboard Setup Guide

## ✅ Changes Made

The merchant dashboard has been configured to properly render the React component.

### 1. Fixed Blade Template
**File:** `resources/views/merchant/dashboard.blade.php`

**Changes:**
- ✅ Changed from `@vite(['resources/js/app.jsx'])` to `@vite(['resources/js/merchant-dashboard-app.jsx'])`
- ✅ Fixed token configuration to use `session('jwt_token')`
- ✅ Added proper window configuration object

### 2. Configuration Flow

```
Blade Template (dashboard.blade.php)
    ↓
Loads: merchant-dashboard-app.jsx
    ↓
Initializes: MerchantDashboard Component
    ↓
Fetches data from: /api/v2/merchant/dashboard
```

## 🚀 How to Run

### Step 1: Install Dependencies (if not already done)
```bash
cd SoftPos
npm install
```

### Step 2: Build Assets
```bash
# Development mode (with hot reload)
npm run dev

# OR build for production
npm run build
```

### Step 3: Start Laravel Server
```bash
php artisan serve --port=8001
```

### Step 4: Access Dashboard
Navigate to: `http://localhost:8001/merchant/dashboard`

## 🔍 Data Flow

### Authentication & Configuration
```javascript
// Data passed from Blade to React:

1. Root Element Data Attributes:
   - data-merchant-id="{{ auth()->user()->merchant?->id ?? '' }}"
   - data-api-token="{{ session('jwt_token') ?? '' }}"

2. Window Configuration:
   window.merchantAppConfig = {
       merchantId: [merchant_id],
       apiToken: "[jwt_token]",
       apiBaseUrl: "/api/v2/merchant/dashboard",
       locale: "en"
   }
```

### Component Initialization
```javascript
// merchant-dashboard-app.jsx looks for:
1. Root element: #merchant-dashboard-root ✅
2. Merchant ID from:
   - Props
   - data-merchant-id attribute
   - window.merchantAppConfig
3. API Token from:
   - data-api-token attribute
   - window.merchantAppConfig
   - localStorage
   - sessionStorage
```

## 📋 Required API Endpoint

The dashboard makes a request to:
```
GET /api/v2/merchant/dashboard/
```

**Headers:**
```
Authorization: Bearer [JWT_TOKEN]
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "statistics": {
      "total_transactions": 0,
      "total_amount": 0,
      "successful_transactions": 0,
      "failed_transactions": 0,
      "success_rate": 0
    },
    "recent_transactions": [],
    "charts": {
      "daily": [],
      "weekly": [],
      "monthly": []
    }
  }
}
```

## 🔧 Troubleshooting

### Issue 1: "Loading dashboard..." shows forever

**Solution:**
```bash
# Check if Vite is running
npm run dev

# Check browser console for errors
# Press F12 → Console tab
```

### Issue 2: JWT Token not found

**Symptoms:**
- Console error: "Token not found in any source"

**Solution:**
1. Verify user is logged in
2. Check session has `jwt_token`:
```php
// In controller or middleware
session(['jwt_token' => $token]);
```

### Issue 3: API endpoint not responding

**Check:**
```bash
# Verify route exists
php artisan route:list | grep "merchant/dashboard"

# Should show:
# GET|HEAD  api/v2/merchant/dashboard  → MerchantDashboardController@getDashboardDataApi
```

### Issue 4: Component not rendering

**Debug steps:**
1. Check browser console for React errors
2. Verify element exists:
```javascript
console.log(document.getElementById('merchant-dashboard-root'));
```
3. Check Vite manifest:
```bash
ls public/build/manifest.json
```

## 📁 File Structure

```
SoftPos/
├── resources/
│   ├── views/
│   │   └── merchant/
│   │       └── dashboard.blade.php          ✅ Blade template
│   └── js/
│       ├── merchant-dashboard-app.jsx       ✅ Entry point
│       ├── components/
│       │   └── merchant/
│       │       ├── MerchantDashboard.jsx    ✅ Main component
│       │       ├── DashboardFilters.jsx     ✅ Filter component
│       │       ├── DashboardStatistics.jsx  ✅ Stats cards
│       │       ├── DashboardCharts.jsx      ✅ Charts component
│       │       └── DashboardLatestTransactions.jsx ✅ Transactions list
│       └── utils/
│           └── constants.js                 ✅ API constants
├── routes/
│   └── api.php                              ✅ API routes
├── app/
│   └── Http/
│       └── Controllers/
│           └── MerchantDashboardController.php ✅ Controller
└── vite.config.js                           ✅ Vite config
```

## 🎨 Features

### Dashboard Components

1. **Dashboard Filters**
   - Date range picker
   - Terminal filter
   - Branch filter

2. **Statistics Cards**
   - Total Transactions
   - Total Amount
   - Success Rate
   - Failed Transactions

3. **Charts**
   - Daily transactions chart
   - Weekly trend chart
   - Monthly overview chart

4. **Latest Transactions**
   - Recent transaction list
   - Transaction details
   - Status indicators

## 🔐 Security

### Token Management
- JWT tokens stored in session
- Token passed via data attributes
- Automatic token refresh on 401 errors
- Logout on token expiration

### API Protection
- All endpoints require JWT authentication
- Middleware: `JwtAuthMiddleware`
- Token validation on each request

## 📊 API Integration

### Using the API in Components

```javascript
import axios from 'axios';
import { SOFTPOS_API_BASE } from '../../utils/constants';

// Get token
const token = document.getElementById('merchant-dashboard-root')
    ?.getAttribute('data-api-token');

// Configure axios
const api = axios.create({
    baseURL: SOFTPOS_API_BASE,
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Fetch dashboard data
const response = await api.get('/api/v2/merchant/dashboard/');
```

## 🧪 Testing

### Manual Testing Checklist

- [ ] Dashboard loads without errors
- [ ] Statistics cards display correct data
- [ ] Charts render properly
- [ ] Filters work correctly
- [ ] Transactions list shows data
- [ ] Date range filter updates data
- [ ] Loading states show properly
- [ ] Error messages display when API fails

### Console Checks

```javascript
// Should see in console:
✅ Merchant Dashboard Initializing... { merchantId: ..., hasToken: true }
✅ Merchant Dashboard rendered successfully
✅ Dashboard data fetched successfully
```

## 🐛 Common Errors & Solutions

### Error: "Merchant Dashboard root element not found"
```
Solution: Verify the blade template has:
<div id="merchant-dashboard-root" ...>
```

### Error: "Token not found"
```
Solution: Ensure JWT token is in session:
session(['jwt_token' => $your_token]);
```

### Error: "Network Error"
```
Solution: 
1. Check Laravel server is running (port 8001)
2. Verify API endpoint exists
3. Check CORS configuration
```

### Error: "Cannot find module"
```
Solution:
npm install
npm run dev
```

## 📞 Support

If you encounter issues:

1. Check browser console (F12)
2. Check Laravel logs: `storage/logs/laravel.log`
3. Verify all services are running:
   - Laravel: `php artisan serve --port=8001`
   - Vite: `npm run dev`
   - AuthService: port 8000
   - POS: port 8002

## 🎉 Success Indicators

You'll know it's working when:
- ✅ Loading spinner disappears
- ✅ Statistics cards show numbers
- ✅ Charts are visible
- ✅ No errors in console
- ✅ Filters are interactive

---

**Last Updated:** October 27, 2025


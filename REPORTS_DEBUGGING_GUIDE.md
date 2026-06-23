# Reports Debugging Guide

## Issue: Data Not Showing in Reports

I've updated all report components with better debugging and fixed some issues. Here's how to find out what's happening:

## Changes Made

### 1. Fixed useEffect Dependencies
**Problem:** The `useEffect` was watching the entire `filters` object, causing infinite re-renders.

**Fixed:** Now watching individual filter properties:
```javascript
// Before (causes infinite loop)
useEffect(() => {
    fetchSales();
}, [filters, pagination.current_page]);

// After (fixed)
useEffect(() => {
    fetchSales();
}, [filters.from_date, filters.to_date, filters.customer_id, filters.warehouse_id, pagination.current_page]);
```

### 2. Added Comprehensive Debugging
All report components now have detailed console logging:
```javascript
console.log('Fetching sales with params:', {...});
console.log('Sales API Response:', response.data);
console.log('Sales Data:', salesData);
console.log('Pagination Data:', paginationData);
```

### 3. Better Error Handling
- Clear error messages
- Error details from API responses
- Reset error state before fetching

## How to Debug

### Step 1: Open Browser Console
1. Navigate to any report (e.g., `/merchant/sales/reports/sales`)
2. Open browser DevTools (F12 or Right-click → Inspect)
3. Go to the **Console** tab

### Step 2: Check the Console Output
You should see logs like this:

**If Data Loads Successfully:**
```
Fetching sales with params: {from_date: "", to_date: "", customer_id: "", warehouse_id: "", page: 1, per_page: 15}
Sales API Response: {status: true, message: "Sales report data", data: {...}}
Sales Data: [{id: 1, reference_no: "SR001", ...}, {...}]
Pagination Data: {total: 50, per_page: 15, current_page: 1, last_page: 4}
```

**If No Data in Database:**
```
Fetching sales with params: {...}
Sales API Response: {status: true, message: "Sales report data", data: {...}}
Sales Data: []  // Empty array means no data
Pagination Data: {total: 0, per_page: 15, current_page: 1, last_page: 1}
```

**If API Error:**
```
Error fetching sales: AxiosError {...}
Error details: {message: "Unauthenticated", ...}
```

### Step 3: Check Network Tab
1. Go to the **Network** tab in DevTools
2. Filter by "XHR" or "Fetch"
3. Look for requests to `/api/v1/reports/sales`
4. Click on the request to see:
   - **Request URL**: Should be like `http://localhost:8002/api/v1/reports/sales?page=1&per_page=15`
   - **Request Headers**: Should include `Authorization: Bearer [token]`
   - **Response**: Check the actual API response

### Step 4: Common Issues & Solutions

#### Issue 1: Empty Data Array
**Symptom:** `Sales Data: []`
**Reason:** No data in the database
**Solution:** Create some test data:
```sql
-- In Pos database
INSERT INTO sales (shop_id, reference_no, customer_id, warehouse_id, created_by, total_qty, grand_total, paid_amount, created_at)
VALUES (1, 'SR001', 1, 1, 1, 5, 100.00, 100.00, NOW());
```

#### Issue 2: 401 Unauthenticated
**Symptom:** Error details show "Unauthenticated"
**Reason:** API token is missing or invalid
**Solution:**
1. Check localStorage: `localStorage.getItem('sales_api_token')`
2. If null, logout and login again
3. Check if token is being sent in request headers

#### Issue 3: 404 Not Found
**Symptom:** Request fails with 404
**Reason:** API route not registered or wrong URL
**Solution:**
1. Check if Pos API is running (should be on port 8002)
2. Verify the route exists in `Pos/routes/api.php`
3. Check `SoftPos/resources/js/utils/constants.js` - POS_API_BASE should be `http://localhost:8002`

#### Issue 4: CORS Error
**Symptom:** Error mentions CORS policy
**Reason:** Cross-Origin Resource Sharing not configured
**Solution:**
1. Check `Pos/config/cors.php`
2. Make sure `'supports_credentials' => true`
3. Add SoftPos URL to allowed origins

#### Issue 5: Wrong shop_id
**Symptom:** Data exists but not showing
**Reason:** API filtering by different shop_id
**Solution:**
1. Check console logs for `shop_id` in query
2. Verify data exists for that shop_id in database
3. Check `creatorId()` function in `Pos/app/helpers.php`

## Test API Directly

Use curl to test the API directly:

```bash
# Replace YOUR_TOKEN with actual JWT token
curl -X GET "http://localhost:8002/api/v1/reports/sales" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

Expected response:
```json
{
  "status": true,
  "message": "Sales report data",
  "data": {
    "data": [
      {
        "id": 1,
        "reference_no": "SR001",
        "sale_date": "2024-01-15",
        "customer": "John Doe",
        "warehouse": "Main Warehouse",
        "biller": "Admin User",
        "grand_total": 100.00,
        "paid_amount": 100.00,
        "due": 0.00,
        "payment_status": "paid"
      }
    ],
    "pagination": {
      "total": 1,
      "per_page": 15,
      "current_page": 1,
      "last_page": 1,
      "from": 1,
      "to": 1
    }
  }
}
```

## Quick Fixes Applied

### All Report Components Updated:
- ✅ `SalesReport.jsx`
- ✅ `PurchaseReport.jsx`
- ✅ `ProductsReport.jsx`
- ✅ `ExpensesReport.jsx`

### What Changed:
1. Fixed infinite loop in useEffect
2. Added detailed console logging
3. Better error messages
4. Reset error state before fetching
5. More robust data extraction from API response

## Next Steps

1. **Open the reports page** in your browser
2. **Open console** (F12)
3. **Look for the logs** starting with "Fetching..."
4. **Share the console output** if you need help debugging further

The console logs will tell us exactly what's happening:
- ✅ Is the API being called?
- ✅ What parameters are being sent?
- ✅ What response is coming back?
- ✅ Is the data empty or is there an error?

Once you check the console, you'll know exactly what the issue is!


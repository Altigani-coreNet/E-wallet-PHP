# Customer Downloads - Authenticated AJAX Implementation ✅

Both Export and Download Template now use authenticated AJAX calls with Bearer token!

---

## ✅ What Changed

### Before (Insecure):
```javascript
// ❌ Old way - No token
const handleExport = () => {
    window.location.href = API_ENDPOINTS.CUSTOMERS.EXPORT;
};

const handleDownloadTemplate = () => {
    window.location.href = API_ENDPOINTS.CUSTOMERS.EXPORT_TEMPLATE;
};
```

### After (Secure with Token):
```javascript
// ✅ New way - AJAX with Bearer token
const handleExport = async () => {
    const token = getToken();
    const response = await fetch(url, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'text/csv',
        },
    });
    const blob = await response.blob();
    // Download file...
};

const handleDownloadTemplate = async () => {
    const token = getToken();
    const response = await fetch(API_ENDPOINTS.CUSTOMERS.EXPORT_TEMPLATE, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'text/csv',
        },
    });
    const blob = await response.blob();
    // Download file...
};
```

---

## 🔐 Security Improvements

### 1. **Token Authentication**
- ✅ Sends Bearer token with every request
- ✅ Validates user authentication before download
- ✅ Prevents unauthorized access

### 2. **Error Handling**
- ✅ Checks if token exists before making request
- ✅ Shows user-friendly error messages
- ✅ Handles network errors gracefully

### 3. **Proper Headers**
- ✅ `Authorization: Bearer {token}` - Auth token
- ✅ `Accept: text/csv` - Expected response type

---

## 📥 How It Works

### Export Customers:

1. **User clicks "Export" button**
   ```javascript
   onClick={handleExport}
   ```

2. **Get authentication token**
   ```javascript
   const token = getToken();
   if (!token) {
       alert('Authentication required');
       return;
   }
   ```

3. **Build URL with filters**
   ```javascript
   const params = new URLSearchParams();
   if (filters.country_id) params.append('country_id', filters.country_id);
   if (filters.date_from) params.append('date_from', filters.date_from);
   if (filters.date_to) params.append('date_to', filters.date_to);
   if (searchTerm) params.append('search', searchTerm);
   
   const url = `${API_ENDPOINTS.CUSTOMERS.EXPORT}?${params.toString()}`;
   ```

4. **Make authenticated AJAX request**
   ```javascript
   const response = await fetch(url, {
       method: 'GET',
       headers: {
           'Authorization': `Bearer ${token}`,
           'Accept': 'text/csv',
       },
   });
   ```

5. **Get blob from response**
   ```javascript
   if (!response.ok) {
       throw new Error('Failed to export customers');
   }
   const blob = await response.blob();
   ```

6. **Create download link and trigger download**
   ```javascript
   const downloadUrl = window.URL.createObjectURL(blob);
   const a = document.createElement('a');
   a.href = downloadUrl;
   a.download = `customers_export_${new Date().toISOString().slice(0, 10)}.csv`;
   document.body.appendChild(a);
   a.click();
   ```

7. **Cleanup**
   ```javascript
   window.URL.revokeObjectURL(downloadUrl);
   document.body.removeChild(a);
   ```

### Download Template:

1. **User clicks "Download Sample Template" button**
   ```javascript
   onClick={handleDownloadTemplate}
   ```

2. **Get authentication token**
   ```javascript
   const token = getToken();
   if (!token) {
       alert('Authentication required');
       return;
   }
   ```

3. **Make authenticated AJAX request**
   ```javascript
   const response = await fetch(API_ENDPOINTS.CUSTOMERS.EXPORT_TEMPLATE, {
       method: 'GET',
       headers: {
           'Authorization': `Bearer ${token}`,
           'Accept': 'text/csv',
       },
   });
   ```

4. **Get blob from response**
   ```javascript
   if (!response.ok) {
       throw new Error('Failed to download template');
   }
   const blob = await response.blob();
   ```

5. **Create download link and trigger download**
   ```javascript
   const url = window.URL.createObjectURL(blob);
   const a = document.createElement('a');
   a.href = url;
   a.download = 'customers_import_template.csv';
   document.body.appendChild(a);
   a.click();
   ```

6. **Cleanup**
   ```javascript
   window.URL.revokeObjectURL(url);
   document.body.removeChild(a);
   ```

---

## 🔄 Complete Flow Diagram

### Export Flow:
```
User clicks Export
    ↓
Get token from localStorage
    ↓
Check if token exists
    ↓
Build URL with filters
    ↓
fetch(url, { headers: { Authorization: Bearer token } })
    ↓
GET /api/v2/sales/customers/export?country_id=...
    ↓
Backend validates token
    ↓
Backend generates CSV
    ↓
Response: Blob (CSV file)
    ↓
Create download URL from blob
    ↓
Create <a> element with download attribute
    ↓
Trigger click() to download
    ↓
Cleanup (revoke URL, remove element)
    ↓
File saved: customers_export_2024-01-20.csv
```

### Download Template Flow:
```
User clicks Download Sample Template
    ↓
Get token from localStorage
    ↓
Check if token exists
    ↓
fetch(EXPORT_TEMPLATE, { headers: { Authorization: Bearer token } })
    ↓
GET /api/v2/sales/customers/export-template
    ↓
Backend validates token
    ↓
Backend generates template CSV
    ↓
Response: Blob (CSV template)
    ↓
Create download URL from blob
    ↓
Create <a> element with download attribute
    ↓
Trigger click() to download
    ↓
Cleanup (revoke URL, remove element)
    ↓
File saved: customers_import_template.csv
```

---

## 🎯 Key Features

### ✅ Security:
- Bearer token authentication
- Token validation before request
- User-specific data access

### ✅ User Experience:
- Automatic file download
- Custom filename with date
- Error messages on failure
- No page reload required

### ✅ Error Handling:
- Token missing alert
- Network error handling
- Server error handling
- User-friendly messages

### ✅ Clean Code:
- Async/await syntax
- Try-catch blocks
- Memory cleanup
- DOM element cleanup

---

## 📝 Backend Requirements

Your Laravel backend should:

### 1. **Accept Bearer Token**
```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/customers/export', [ApiCustomerController::class, 'export']);
    Route::get('/customers/export-template', [ApiCustomerController::class, 'exportTemplate']);
});
```

### 2. **Return CSV Response**
```php
public function export(Request $request)
{
    // ... generate CSV data
    
    return response()->stream($callback, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="customers_export.csv"',
    ]);
}

public function exportTemplate()
{
    // ... generate template CSV
    
    return response()->stream($callback, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="customers_import_template.csv"',
    ]);
}
```

### 3. **Handle CORS** (if needed)
```php
// config/cors.php
'paths' => ['api/*'],
'allowed_origins' => ['http://localhost:3000', 'http://localhost:5173'],
'allowed_headers' => ['*'],
'exposed_headers' => ['Content-Disposition'],
```

---

## 🧪 Testing

### Test Export:
```javascript
// In browser console
const token = localStorage.getItem('sales_api_token');
console.log('Token:', token);

// Manually trigger export
const exportBtn = document.querySelector('[onClick*="handleExport"]');
exportBtn.click();

// Check network tab for:
// - Request Headers: Authorization: Bearer {token}
// - Response: CSV file download
```

### Test Download Template:
```javascript
// In browser console
const token = localStorage.getItem('sales_api_token');
console.log('Token:', token);

// Manually trigger download
const templateBtn = document.querySelector('[onClick*="handleDownloadTemplate"]');
templateBtn.click();

// Check network tab for:
// - Request Headers: Authorization: Bearer {token}
// - Response: CSV template download
```

### Test Error Cases:
```javascript
// 1. Remove token
localStorage.removeItem('sales_api_token');
// Click export/template - should show "Authentication required"

// 2. Invalid token
localStorage.setItem('sales_api_token', 'invalid_token');
// Click export/template - should show "Failed to..." error
```

---

## 📊 Network Request Example

### Export Request:
```http
GET /api/v2/sales/customers/export?country_id=123&date_from=2024-01-01 HTTP/1.1
Host: localhost:8002
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Accept: text/csv
```

### Export Response:
```http
HTTP/1.1 200 OK
Content-Type: text/csv
Content-Disposition: attachment; filename="customers_export_2024-01-20.csv"

ID,Name,Company Name,Email,Phone,...
1,John Doe,Doe Enterprises,john@example.com,+1234567890,...
2,Jane Smith,Smith LLC,jane@example.com,+1987654321,...
```

### Template Request:
```http
GET /api/v2/sales/customers/export-template HTTP/1.1
Host: localhost:8002
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Accept: text/csv
```

### Template Response:
```http
HTTP/1.1 200 OK
Content-Type: text/csv
Content-Disposition: attachment; filename="customers_import_template.csv"

Name,Email,Phone,Company Name,Address,City,State,Postal Code,Country,Tax No
John Doe,john.doe@example.com,+1234567890,Doe Enterprises,123 Main Street,...
Jane Smith,jane.smith@example.com,+1234567891,Smith Inc,456 Oak Avenue,...
```

---

## ✅ Summary

### What's New:
✅ **Export** - Now uses AJAX with Bearer token
✅ **Download Template** - Now uses AJAX with Bearer token
✅ **Both** - Properly handle authentication errors
✅ **Both** - Download files programmatically
✅ **Both** - Clean up memory after download

### Security Benefits:
✅ Token-based authentication
✅ User validation on every request
✅ Prevents unauthorized access
✅ Proper error handling

### User Experience:
✅ Seamless downloads
✅ No page reload
✅ Clear error messages
✅ Custom filenames

---

**Both Export and Download Template are now secure and authenticated!** 🔐✅


# Merchant Transactions - Quick Start Guide

## 🚀 Quick Setup (3 Steps)

### Step 1: Install Dependencies
```bash
cd SoftPos
npm install
```

### Step 2: Build Assets
```bash
# For development (with hot reload)
npm run dev

# OR for production
npm run build
```

### Step 3: Done! 🎉
Navigate to `/merchant/transactions` in your browser - the React app will load automatically!

---

## 📝 What You Get

### ✅ Full-Featured Transaction Management
- **DataTable** with server-side pagination
- **Advanced Filters** (Search, Status, Payment Type, Terminal, Dates)
- **Statistics Cards** (Sale, Refund, Void transactions)
- **Export to CSV** with filters applied
- **Bulk Actions** (Select & delete multiple)
- **Transaction Actions** (View, Void, Refund, Send Receipt)

### 🎨 Modern UI
- **React Components** - Fast and responsive
- **Toast Notifications** - User-friendly feedback
- **SweetAlert2** - Beautiful confirmations
- **Smooth Animations** - Professional look

---

## 🔗 How It Works

The transactions page is now integrated into **`merchant-app.jsx`**:

```
Your Blade Template (index-react.blade.php)
         ↓
    merchant-app-root div
         ↓
    merchant-app.jsx (React Router)
         ↓
    /merchant/transactions route
         ↓
    MerchantTransactions component
```

---

## 📡 API Endpoints Available

All under `/api/v1/merchant/transactions/`:

| Action | Endpoint | What it does |
|--------|----------|--------------|
| 📊 List | `/data` | Get paginated transactions |
| 📈 Stats | `/statistics` | Get transaction statistics |
| 📥 Export | `/export` | Download CSV |
| 🗑️ Delete | `/bulk-delete` | Delete multiple |
| 👁️ View | `/{id}` | Get single transaction |
| ⛔ Void | `/{id}/void` | Void transaction |
| 💰 Refund | `/{id}/refund` | Refund transaction |
| 📧 Receipt | `/{id}/send-receipt` | Email receipt |

---

## 🎯 Usage Examples

### Basic Usage (Already Working!)
Just navigate to: `http://your-domain/merchant/transactions`

### With Type Filter
```
http://your-domain/merchant/transactions?type=sale
http://your-domain/merchant/transactions?type=refund
http://your-domain/merchant/transactions?type=void
```

### Access from Blade
```php
Route::get('/merchant/transactions', function() {
    return view('merchant.transactions.index-react');
})->middleware(['auth', 'merchant']);
```

### Access via React Router
The URL is automatically handled by React Router in `merchant-app.jsx`

---

## 🔐 Permissions Required

Users need these permissions to access features:

| Feature | Permission |
|---------|-----------|
| View transactions | `transactions` or `view_transactions` |
| Export | `export_transactions` |
| Delete | `delete_transactions` |
| Void | `void_transactions` |
| Refund | `refund_transactions` |
| Statistics | `statistics` |

---

## 🧪 Quick Test

1. ✅ Open `/merchant/transactions`
2. ✅ Try searching for a transaction
3. ✅ Apply some filters
4. ✅ Click export button
5. ✅ Select a transaction and try an action

---

## 💡 Pro Tips

### 1. Filter Management
- Filters are tracked in the filter summary
- "Clear Filters" button resets everything
- Filters are applied to exports too!

### 2. Bulk Operations
- Select multiple with checkboxes
- "Select All" at the top
- Confirm before bulk delete

### 3. Export with Filters
- Apply filters first
- Then click "Export"
- CSV includes only filtered results

### 4. Transaction Actions
- **View** - Opens detail page
- **Void** - For APPROVED/CAPTURED only
- **Refund** - Validates refundable amount
- **Send Receipt** - Email with PDF

---

## 🔧 Customization

### Change Merchant ID Source
Edit `MerchantTransactions.jsx`:
```javascript
const merchantId = propMerchantId || 
                   window.merchantTransactionsConfig?.merchantId || 
                   window.merchantAppConfig?.merchantId ||
                   JSON.parse(localStorage.getItem('user_data'))?.merchant_id;
```

### Add Custom Filters
Edit `TransactionFilters.jsx` to add new filter fields

### Modify Statistics
Edit `TransactionStatistics.jsx` to change metrics displayed

### Change Actions
Edit `TransactionActions.jsx` to add/remove action buttons

---

## 🐛 Troubleshooting

### Issue: Page doesn't load
**Solution:** 
```bash
npm run build
php artisan cache:clear
```

### Issue: 403 Unauthorized
**Solution:** Check user permissions in database

### Issue: Filters not working
**Solution:** Check browser console for errors, verify API endpoints

### Issue: Export fails
**Solution:** Check server logs, verify merchant_id is passed

---

## 📂 File Structure

```
SoftPos/
├── resources/
│   ├── js/
│   │   ├── merchant-app.jsx                    ← Main router (MODIFIED)
│   │   └── components/
│   │       └── merchant/
│   │           ├── MerchantTransactions.jsx    ← Main component (NEW)
│   │           ├── TransactionFilters.jsx      ← Filters (NEW)
│   │           ├── TransactionStatistics.jsx   ← Statistics (NEW)
│   │           └── TransactionActions.jsx      ← Actions (NEW)
│   └── views/
│       └── merchant/
│           └── transactions/
│               └── index-react.blade.php        ← Blade template (NEW)
├── app/
│   └── Http/
│       └── Controllers/
│           └── MerchantTransactionController.php ← Controller (MODIFIED)
└── routes/
    └── api.php                                   ← API routes (MODIFIED)
```

---

## 📞 Need Help?

1. Check `MERCHANT_TRANSACTIONS_IMPLEMENTATION_SUMMARY.md` for details
2. Read `MERCHANT_TRANSACTIONS_REACT_MIGRATION.md` for full documentation
3. Look at similar implementation: Payment Links (already React)
4. Check browser console for errors

---

## ✨ Next Steps

1. ✅ Test all features
2. ✅ Configure permissions
3. ✅ Customize as needed
4. ✅ Deploy to production

---

**That's it!** Your merchant transactions page is now powered by React! 🎉

---

**Quick Reference:**
- 📁 Components: `resources/js/components/merchant/`
- 🎯 Route: `/merchant/transactions`
- 🔧 Controller: `MerchantTransactionController.php`
- 📡 API: `/api/v1/merchant/transactions/`


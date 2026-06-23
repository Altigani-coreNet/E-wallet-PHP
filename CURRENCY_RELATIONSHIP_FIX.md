# Currency Relationship Fix

## Error Fixed
```
Call to a member function addEagerConstraints() on null
File: Illuminate\Database\Eloquent\Builder.php (line 926)
```

## Root Cause

After migrating to UUID-based currencies from an external service (AuthService), several models still had `currency()` relationship methods that returned `null` or were commented out. When controllers tried to eager load these relationships using `->with(['currency'])` or `->load(['currency'])`, Laravel would get `null` instead of a relationship object, causing this error.

## Models Affected

### ✗ Models with Broken Currency Relationships
1. **Transaction** - `currency()` returns null (commented out)
2. **PaymentByLink** - `currency()` is commented out

### ✓ Models with Working Currency Relationships
1. **Settlement** - Has proper `belongsTo(Currency::class)` relationship
2. **Batch** - Has proper `belongsTo(Currency::class)` relationship

## Why Currency Relationships Were Removed

As part of the UUID migration documented in `CURRENCY_UUID_MIGRATION_COMPLETE.md`, the system moved from:
- **Before**: Local currency table with auto-increment IDs
- **After**: External currency service (AuthService) with UUID identifiers

For Transaction and PaymentByLink models:
- `currency_id` now stores a UUID string from the external service
- No local `currencies` table exists to join with
- The relationship was removed/commented out intentionally

## The Fix

### 1. MerchantTransactionController.php
**Removed** `'currency'` from eager loading:

```php
// Before
$transaction->load([
    'merchant', 
    'user', 
    'paymentMethod', 
    'logs',
    'batch',
    'currency'  // ❌ This returns null
]);

// After
$transaction->load([
    'merchant', 
    'user', 
    'paymentMethod', 
    'logs',
    'batch'
    // 'currency' - Not a real relationship, it's a UUID reference
]);
```

### 2. MerchantPaymentLinkController.php
**Removed** `'currency'` from eager loading in 2 places:

```php
// Before
$query = PaymentByLink::where('merchant_id', $merchant->id)
    ->with(['merchant', 'customer', 'currency']);  // ❌ Currency is commented out

// After
$query = PaymentByLink::where('merchant_id', $merchant->id)
    ->with(['merchant', 'customer']); // currency is a UUID, not a relationship
```

Fixed in:
- `data()` method (line ~230)
- `export()` method (line ~399)

## Files Modified

1. ✅ `SoftPos/app/Http/Controllers/MerchantTransactionController.php`
2. ✅ `SoftPos/app/Http/Controllers/MerchantPaymentLinkController.php`

## How to Access Currency Information

Since `currency_id` is now a UUID, you can:

### Option 1: Direct Field Access
```php
$transaction = Transaction::find($id);
$currencyUuid = $transaction->currency_id;  // e.g., "a1b2c3d4-..."
```

### Option 2: Fetch from External Service
```php
use Illuminate\Support\Facades\Http;

$currencyUuid = $transaction->currency_id;
$response = Http::withToken($token)
    ->get("http://authservice/api/v2/currencies/{$currencyUuid}");
$currencyData = $response->json();
```

### Option 3: Cache Currency Data Locally
Consider caching currency information in the response:
```php
// In controller
$transaction = Transaction::find($id);
$transaction->currency_name = $this->getCurrencyName($transaction->currency_id);
return response()->json($transaction);
```

## Preventing Future Issues

### When Creating New Relationships

If you need to eager load relationships, always ensure the relationship method returns a valid Eloquent relationship:

✅ **Good:**
```php
public function currency()
{
    return $this->belongsTo(Currency::class);
}
```

❌ **Bad:**
```php
public function currency()
{
    // return $this->belongsTo(Currency::class);
}
```

❌ **Bad:**
```php
public function currency()
{
    return null;
}
```

### When Removing Relationships

If a relationship is no longer valid (like after UUID migration), you should:

1. **Remove or comment out the relationship method** in the model
2. **Remove all eager loading** of that relationship in controllers
3. **Document the change** and provide alternative data access methods
4. **Search codebase** for all uses: `grep -r "->with.*currency" .`

## Testing

After these fixes, the following should work without errors:

### Transaction Details
```bash
curl -X GET http://193.123.83.134:94/api/v1/merchant/transactions/6 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Payment Links List
```bash
curl -X GET http://193.123.83.134:94/api/v1/merchant/payment-links/data \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Payment Links Export
```bash
curl -X GET http://193.123.83.134:94/api/v1/merchant/payment-links/export \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Related Documentation

- `CURRENCY_UUID_MIGRATION_COMPLETE.md` - Original UUID migration details
- `EXTERNAL_USER_MORPHTO_FIX.md` - Related fix for ExternalUser relationships
- `TRANSACTION_DETAIL_FIX_QUICK_REFERENCE.md` - Quick reference for transaction fixes

## Summary

This fix removes eager loading of non-existent currency relationships that were returning `null` after the UUID migration. The `currency_id` field still contains valid UUID data; it just can't be joined as a local relationship anymore.


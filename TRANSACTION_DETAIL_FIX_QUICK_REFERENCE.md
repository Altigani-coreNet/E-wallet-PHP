# Transaction Detail Fix - Quick Reference

## Errors Fixed

### Error 1: ExternalUser Missing Methods
```
Call to undefined method App\Models\ExternalUser::getConnectionName()
```

### Error 2: Null Currency Relationship
```
Call to a member function addEagerConstraints() on null
```

## What Was Wrong

### Problem 1: ExternalUser Class
The `ExternalUser` class doesn't extend Laravel's `Model` class (it's just an auth wrapper), but Laravel's morphTo relationship expected it to have database model methods like `getConnectionName()`.

### Problem 2: Currency Relationship
After UUID migration, `currency()` relationships in Transaction and PaymentByLink models were commented out or returning null, but controllers were still trying to eager load them.

## What Was Fixed

### Fix 1: Added Missing Methods to ExternalUser
Added 4 methods that Laravel's relationship system needs:
- `getConnectionName()` - Returns null
- `getTable()` - Returns 'external_users'
- `getKeyName()` - Returns 'id'
- `getKey()` - Returns the actual ID

### Fix 2: Removed Broken Currency Eager Loading
Removed `'currency'` from `->load()` and `->with()` calls since currency_id is now a UUID reference to an external service, not a local relationship.

## Files Changed
1. `SoftPos/app/Models/ExternalUser.php` - Added 4 methods
2. `SoftPos/app/Http/Controllers/MerchantTransactionController.php` - Removed currency eager loading
3. `SoftPos/app/Http/Controllers/MerchantPaymentLinkController.php` - Removed currency eager loading

## Test It
```bash
# Test merchant transaction detail endpoint
curl -X GET http://193.123.83.134:94/api/v1/merchant/transactions/6 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Should now return transaction details without errors.

## Why This Happened

Your app uses two types of authentication:
1. **Local users** - Stored in SoftPos database (extend Model)
2. **External users** - Come from AuthService (don't extend Model)

When transaction logs try to record who did an action, they use a morphTo relationship that can point to either type. The problem occurred when an ExternalUser was recorded as the performer.

## Prevention

If you create similar wrapper classes in the future that need to work with morphTo relationships, make sure to add these methods:
- `getConnectionName()`
- `getTable()`
- `getKeyName()`
- `getKey()`

Or consider extending `Model` if the class will be used in Eloquent relationships.


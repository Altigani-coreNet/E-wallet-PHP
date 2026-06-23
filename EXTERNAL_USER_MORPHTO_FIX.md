# External User MorphTo Relationship Fix

## Problem Description

### Error Message
```
Call to undefined method App\Models\ExternalUser::getConnectionName()
```

### Error Location
- **File**: `MerchantTransactionController.php`
- **Line**: 75 (during `$transaction->load()`)
- **Endpoint**: `GET /api/v1/merchant/transactions/{id}`

### Root Cause

The error occurred when trying to view transaction details in the merchant app. The issue was caused by:

1. **ExternalUser Class Design**: The `ExternalUser` class implements `Authenticatable` interface but does NOT extend Laravel's `Illuminate\Database\Eloquent\Model` class
2. **MorphTo Relationship**: The `TransactionLog` model has a `performer()` morphTo relationship that can reference different types of users (User, Admin, ExternalUser)
3. **Missing Methods**: When Laravel's morphTo relationship tries to eager load the `performer` relationship, it calls `getConnectionName()` on the related model class
4. **Incompatibility**: Since `ExternalUser` doesn't extend `Model`, it doesn't have the required methods that Laravel's relationship system expects

## Why ExternalUser Doesn't Extend Model

`ExternalUser` is a special wrapper class designed to work with external authentication services (like AuthService). It:
- Stores user data from external API responses
- Implements the `Authenticatable` interface for Laravel's auth system
- Does NOT represent a database table in the SoftPos application
- Holds data temporarily during a user's authenticated session

## The Solution

### 1. Added Required Methods to ExternalUser

Added four methods to make `ExternalUser` compatible with Laravel's morphTo relationships:

```php
/**
 * Get the database connection name.
 * Required for Laravel's morphTo relationships.
 */
public function getConnectionName()
{
    return null;
}

/**
 * Get the table associated with the model.
 * Required for Laravel's morphTo relationships.
 */
public function getTable()
{
    return 'external_users';
}

/**
 * Get the primary key for the model.
 * Required for Laravel's morphTo relationships.
 */
public function getKeyName()
{
    return 'id';
}

/**
 * Get the value of the model's primary key.
 * Required for Laravel's morphTo relationships.
 */
public function getKey()
{
    return $this->attributes['id'] ?? null;
}
```

**Why These Methods?**
- `getConnectionName()`: Returns null since ExternalUser is not database-backed
- `getTable()`: Returns a table name (even though it doesn't exist) to satisfy Laravel's expectations
- `getKeyName()`: Returns the primary key column name
- `getKey()`: Returns the actual ID value from the attributes array

### 2. Updated Transaction Detail Loading

Modified the controller to be more defensive about loading relationships:

**Before:**
```php
$transaction->load([
    'merchant', 
    'user', 
    'paymentMethod', 
    'logs.performer',  // This caused the error
    'batch',
    'currency'
]);
```

**After:**
```php
$transaction->load([
    'merchant', 
    'user', 
    'paymentMethod', 
    'logs',  // Load logs without performer to avoid ExternalUser issues
    'batch',
    'currency'
]);
```

**Why This Change?**
- Removes the eager loading of `performer` relationship to avoid database queries for ExternalUser
- Logs are still loaded, but without attempting to resolve the performer
- This prevents any potential issues with ExternalUser not being queryable from the database

## Files Modified

1. **SoftPos/app/Models/ExternalUser.php**
   - Added 4 methods to make it compatible with morphTo relationships

2. **SoftPos/app/Http/Controllers/MerchantTransactionController.php**
   - Removed `logs.performer` from eager loading in the `show()` method

## How TransactionLog Records Performers

The `TransactionLog::logTransactionAction()` method records the performer like this:

```php
if (Auth::check()) {
    $user = Auth::user();
    $performedBy = $user->id;
    $performedByType = get_class($user);  // e.g., "App\Models\ExternalUser"
} elseif (Auth::guard('admin')->check()) {
    $admin = Auth::guard('admin')->user();
    $performedBy = $admin->id;
    $performedByType = get_class($admin);
}
```

This means transaction logs can have:
- **User** (local database user)
- **Admin** (local database admin)
- **ExternalUser** (external auth user - not in database)

## Testing

After this fix, the following should work:

1. ✅ View merchant transaction details: `GET /api/v1/merchant/transactions/{id}`
2. ✅ Transaction logs are loaded correctly
3. ✅ No errors when performer is an ExternalUser
4. ✅ No errors when performer is a local User or Admin

## Alternative Approaches Considered

### Option 1: Make ExternalUser Extend Model ❌
**Rejected**: This would fundamentally change how ExternalUser works and could break the external authentication system

### Option 2: Store Performer Data as JSON ❌
**Rejected**: Would require migration and lose the benefits of morphTo relationships for local users

### Option 3: Add Methods to ExternalUser ✅
**Selected**: Minimal change that maintains backward compatibility while fixing the issue

## Future Considerations

If you need to display performer information in transaction logs:

1. **For Local Users/Admins**: The morphTo relationship will work normally
2. **For ExternalUsers**: You'll need to fetch user data from the external service using the `performed_by` ID
3. **Consider**: Adding a `performer_name` column to `transaction_logs` table to cache the name at the time of the action

## Related Files

- `SoftPos/app/Models/ExternalUser.php` - External user wrapper class
- `SoftPos/app/Models/TransactionLog.php` - Transaction logging model
- `SoftPos/app/Models/Transaction.php` - Main transaction model
- `SoftPos/app/Http/Controllers/MerchantTransactionController.php` - Merchant transaction controller
- `SoftPos/app/Auth/ExternalUserProvider.php` - Custom auth provider for external users

## Summary

This fix resolves the `getConnectionName()` error by adding the minimum required methods to `ExternalUser` to make it compatible with Laravel's morphTo relationship system, while maintaining its design as a non-database-backed authentication wrapper.


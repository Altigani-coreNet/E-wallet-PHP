# Payment Links Currency UUID Migration - Complete

## Overview
Successfully migrated the Payment Links system to use UUIDs for currency management, integrating with AuthService API instead of local currency relations.

## Changes Made

### 1. Database Migration (`2025_08_19_064258_create_payment_by_links_table.php`)

**Changed:**
- `currency_id`: Changed from `unsignedBigInteger` to `uuid` (for AuthService currency UUID)
- **Added new fields:**
  - `currency_code`: `string`, nullable - Store currency code (USD, EUR, etc.) for quick access
  - `currency_object`: `json`, nullable - Store full currency entity from AuthService

**Removed:**
- Foreign key constraint on `currency_id` (no longer references local `currencies` table)

```php
$table->uuid('currency_id'); // Changed to UUID for AuthService currency
$table->string('currency_code')->nullable(); // Store currency code for quick access
$table->json('currency_object')->nullable(); // Store full currency entity from AuthService
```

### 2. Controller (`MerchantPaymentLinkApiController.php`)

**Added:**
- New import: `use Illuminate\Support\Facades\Http;`
- New method: `fetchCurrencyFromAuthService()` - Fetches currency details from AuthService by UUID

**Updated Methods:**

#### `store()` Method:
- Changed validation: `'currency_id' => 'required|uuid'` (was `exists:currencies,id`)
- Fetches currency from AuthService using UUID
- Stores `currency_code` and `currency_object` in the database
- Returns error if currency UUID is invalid or AuthService is unavailable

```php
// Fetch currency from AuthService
$currencyData = $this->fetchCurrencyFromAuthService($request->currency_id);

if (!$currencyData) {
    return response()->json([
        'success' => false,
        'message' => 'Invalid currency ID or unable to fetch currency details from AuthService'
    ], 400);
}

$request->merge([
    'currency_code' => $currencyData['currency_code'] ?? $currencyData['code'] ?? null,
    'currency_object' => json_encode($currencyData),
]);
```

#### `update()` Method:
- Changed validation: `'currency_id' => 'required|uuid'` (was `exists:currencies,id`)
- Same logic as `store()` - fetches from AuthService and updates `currency_code` and `currency_object`

#### `show()` Method:
- Removed `->load(['currency'])` relationship loading
- Decodes `currency_object` from JSON to array for response
- Returns currency data directly from the stored `currency_object` field

#### `index()` Method:
- Removed `'currency'` from `->with()` eager loading

#### `export()` Method:
- Removed `'currency'` from `->with()` eager loading
- Changed CSV export to use `$link->currency_code` instead of `optional($link->currency)->currency_code`

### 3. Model (`PaymentByLink.php`)

**Updated `$fillable` array:**
```php
protected $fillable = [
    // ... existing fields
    'currency_id',
    'currency_code',      // Added
    'currency_object',    // Added
    // ... rest of fields
];
```

**Updated `$casts` array:**
```php
protected $casts = [
    'scheduled_date' => 'datetime',
    'expired_date' => 'datetime',
    'payment_method_types' => 'array',
    'currency_object' => 'array',    // Added
    'metadata' => 'array',           // Added
];
```

**Commented out currency relationship:**
```php
// Commented out - storing currency info directly from AuthService
// public function currency()
// {
//     return $this->belongsTo(Currency::class);
// }
```

### 4. Repository (`PaymentByLinkRepository.php`)

**Updated `store()` Method:**
```php
// Before:
if (isset($data['currency_id'])) {
    $currency = \App\Models\Currency::find($data['currency_id']);
    $data['currency'] = $currency ? $currency->currency_code : 'USD';
}

// After:
$data['currency'] = $data['currency_code'] ?? 'USD';
```

**Updated `update()` Method:**
```php
// Before:
if (isset($data['currency_id'])) {
    $currency = \App\Models\Currency::find($data['currency_id']);
    $data['currency'] = $currency ? $currency->currency_code : ($data['currency'] ?? $link->currency);
}

// After:
if (isset($data['currency_code'])) {
    $data['currency'] = $data['currency_code'];
} else {
    $data['currency'] = $link->currency_code ?? 'USD';
}
```

## How It Works

### Data Flow:

1. **Frontend sends request** with `currency_id` (UUID from AuthService)

2. **Controller receives request:**
   - Validates `currency_id` as UUID
   - Calls `fetchCurrencyFromAuthService($currencyId)`

3. **AuthService API call:**
   ```
   GET {AUTH_SERVICE_URL}/api/currencies/{uuid}
   Authorization: Bearer {token}
   ```

4. **Response from AuthService** contains full currency entity:
   ```json
   {
     "data": {
       "id": "uuid-here",
       "currency_code": "USD",
       "currency_name": "US Dollar",
       "symbol": "$",
       // ... other currency fields
     }
   }
   ```

5. **Controller stores:**
   - `currency_id`: UUID from AuthService
   - `currency_code`: Extracted from response (for quick access)
   - `currency_object`: Full JSON object from AuthService

6. **On retrieval:**
   - `currency_object` is automatically cast to array by Laravel
   - No need to load currency relationship
   - All currency data available directly from the payment link record

## Benefits

1. **Microservices Architecture:** Payment Links now properly integrates with AuthService
2. **Data Consistency:** Currency data is fetched from central AuthService
3. **Performance:** No need for foreign key lookups or joins
4. **Flexibility:** Can store any currency data structure from AuthService
5. **Offline Access:** Currency data cached in `currency_object` field
6. **Quick Queries:** `currency_code` field allows fast filtering without JSON parsing

## API Endpoints Affected

All payment link endpoints now work with UUID-based currencies:

- `POST /api/payment-links` - Create payment link
- `PUT /api/payment-links/{id}` - Update payment link
- `GET /api/payment-links/{id}` - Get payment link details (returns currency_object)
- `GET /api/payment-links` - List payment links
- `GET /api/payment-links/export` - Export payment links (uses currency_code)

## Configuration Required

Ensure `config/services.php` contains:
```php
'auth_service_url' => env('AUTH_SERVICE_URL'),
```

And `.env` file has:
```
AUTH_SERVICE_URL=http://your-auth-service-url
```

## Migration Steps

To apply this migration to an existing database:

1. **Run the migration:**
   ```bash
   php artisan migrate:refresh --path=/database/migrations/2025_08_19_064258_create_payment_by_links_table.php
   ```

2. **Clear cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

3. **Test endpoints** with UUID-based currencies

## Testing Checklist

- [ ] Create payment link with currency UUID from AuthService
- [ ] Update payment link with different currency UUID
- [ ] Retrieve payment link and verify `currency_object` is returned
- [ ] List payment links and verify no errors
- [ ] Export payment links CSV and verify currency_code column
- [ ] Test with invalid currency UUID (should return 400 error)
- [ ] Test when AuthService is unavailable (should return 400 error)

## Error Handling

The system handles the following scenarios:

1. **Invalid Currency UUID:** Returns 400 error with message
2. **AuthService Unavailable:** Returns 400 error, logs warning
3. **Currency Not Found:** Returns 400 error
4. **Timeout:** 10-second timeout on AuthService calls

## Notes

- The old `Currency` model relationship is commented out, not deleted
- Existing data needs to be migrated if you have payment links already
- `currency_id` now stores UUIDs from AuthService, not local IDs
- Stripe integration continues to work with `currency_code` field
- The system falls back to 'USD' if no currency data is available

## Completed ✅

All tasks completed successfully:
- ✅ Migration updated with UUID fields
- ✅ Controller methods updated to fetch from AuthService
- ✅ Model updated with new fields and casts
- ✅ Repository updated to use direct currency data
- ✅ No linter errors

## Summary

The Payment Links system now fully integrates with AuthService for currency management, storing currency data as JSON objects and using UUIDs for currency references. This maintains consistency across the microservices architecture while providing fast access to currency information.



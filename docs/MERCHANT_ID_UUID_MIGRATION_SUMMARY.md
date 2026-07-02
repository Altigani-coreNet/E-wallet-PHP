# Merchant ID to UUID Migration - Summary ✅

## Overview
Successfully updated the original migration files to use UUID for `merchant_id` instead of foreign key relationships. This aligns with the AuthService architecture where merchants are identified by UUID.

## What Was Changed

### 1. **payment_by_links Table** ✅
📁 `database/migrations/2025_08_19_064258_create_payment_by_links_table.php`

**Changes:**
```php
// Changed merchant_id to UUID
$table->uuid('merchant_id'); // Was: unsignedBigInteger

// Made customer_id nullable
$table->unsignedBigInteger('customer_id')->nullable(); // Was: required

// Added customer information fields (storing directly)
$table->string('customer_name')->nullable();
$table->string('customer_phone')->nullable();
$table->string('customer_email')->nullable();

// Added country_id as UUID
$table->uuid('country_id')->nullable();

// Added metadata field
$table->json('metadata')->nullable();

// Removed foreign key constraint on merchant_id
// (no foreign key constraint)
```

### 2. **transactions Table** ✅
📁 `database/migrations/2025_08_12_create_transactions_table.php`

**Changes:**
```php
// Changed merchant_id to UUID
$table->uuid('merchant_id')->nullable(); // Was: unsignedBigInteger

// Removed foreign key constraint
// $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('set null');
```

### 3. **settlements Table** ✅
📁 `database/migrations/2025_09_27_000001_create_settlements_table.php`

**Changes:**
```php
// Changed merchant_id to UUID
$table->uuid('merchant_id'); // Was: unsignedBigInteger

// Foreign key was already commented out (no change needed)
```

### 4. **batches Table** ✅
📁 `database/migrations/2025_8_01_000000_create_batches_table.php`

**Changes:**
```php
// Changed merchant_id to UUID
$table->uuid('merchant_id'); // Was: unsignedBigInteger

// Commented out foreign key constraint
// $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
```

## Deleted Migrations

These migrations are no longer needed since changes were made to original migrations:

- ❌ `2025_01_27_000001_add_customer_fields_to_payment_by_links_table.php` (deleted)
- ❌ `2026_02_01_000700_change_merchant_id_to_uuid.php` (deleted)

## Database Schema After Migration

### payment_by_links Table:
```sql
CREATE TABLE payment_by_links (
    id BIGINT UNSIGNED PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    merchant_id CHAR(36) NOT NULL,              -- ✅ UUID
    status VARCHAR(255) DEFAULT 'pending',
    payment_status ENUM('paid', 'unpaid') DEFAULT 'unpaid',
    link TEXT,
    payment_sdk VARCHAR(255),
    amount DECIMAL(15,2),
    currency_id BIGINT UNSIGNED,
    payment_method_types JSON,
    scheduled_date DATETIME,
    expired_date DATETIME,
    customer_id BIGINT UNSIGNED NULL,            -- ✅ Nullable
    customer_name VARCHAR(255),                  -- ✅ New
    customer_phone VARCHAR(255),                 -- ✅ New
    customer_email VARCHAR(255),                 -- ✅ New
    country_id CHAR(36),                         -- ✅ UUID
    metadata JSON,                               -- ✅ New
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (currency_id) REFERENCES currencies(id)
);
```

### transactions Table:
```sql
CREATE TABLE transactions (
    id BIGINT UNSIGNED PRIMARY KEY,
    merchant_id CHAR(36),                        -- ✅ UUID (nullable)
    terminal_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED,
    -- ... other fields ...
    FOREIGN KEY (terminal_id) REFERENCES terminals(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
    -- No foreign key on merchant_id
);
```

### settlements Table:
```sql
CREATE TABLE settlements (
    id BIGINT UNSIGNED PRIMARY KEY,
    settlement_number VARCHAR(255) UNIQUE,
    batch_id BIGINT UNSIGNED,
    merchant_id CHAR(36) NOT NULL,               -- ✅ UUID
    user_id BIGINT UNSIGNED,
    -- ... other fields ...
    -- No foreign key on merchant_id
);
```

### batches Table:
```sql
CREATE TABLE batches (
    id BIGINT UNSIGNED PRIMARY KEY,
    batch_number VARCHAR(255) UNIQUE,
    merchant_id CHAR(36) NOT NULL,               -- ✅ UUID
    user_id BIGINT UNSIGNED,
    -- ... other fields ...
    FOREIGN KEY (user_id) REFERENCES users(id)
    -- No foreign key on merchant_id
);
```

## How to Apply

### If Database Exists (with data):

⚠️ **WARNING:** This will require dropping and recreating tables!

**Option 1: Fresh Migration (Development Only)**
```bash
# Backup your data first!
php artisan migrate:fresh

# Or if you want to seed data
php artisan migrate:fresh --seed
```

**Option 2: Export/Import Data**
```bash
# 1. Export existing data
php artisan db:backup  # or use mysqldump

# 2. Drop all tables
php artisan migrate:fresh

# 3. Import data back (you'll need to convert merchant_id values to UUID)
```

### If Fresh Database (No data):
```bash
php artisan migrate
```

## Data Conversion Script

If you have existing data, you'll need to convert merchant_id from integer to UUID. Here's a helper:

```php
// Run in tinker: php artisan tinker

use App\Models\PaymentByLink;
use App\Models\Transaction;
use App\Models\Settlement;
use App\Models\Batch;
use App\Models\Merchant;

// Create a mapping of old merchant IDs to UUIDs
$merchantMapping = [];
Merchant::all()->each(function($merchant) use (&$merchantMapping) {
    $merchantMapping[$merchant->id] = $merchant->uuid; // Assuming merchants have UUID
});

// Update payment_by_links
DB::table('payment_by_links')->get()->each(function($link) use ($merchantMapping) {
    if (isset($merchantMapping[$link->merchant_id])) {
        DB::table('payment_by_links')
            ->where('id', $link->id)
            ->update(['merchant_id' => $merchantMapping[$link->merchant_id]]);
    }
});

// Repeat for transactions, settlements, batches...
```

## Benefits

### ✅ **Consistent Architecture**
- All tables now use UUID for merchant_id
- Matches AuthService design
- No foreign key constraints to manage

### ✅ **Flexibility**
- Can reference merchants from different services
- No database-level coupling
- Easier to scale horizontally

### ✅ **Data Integrity**
- Customer information preserved in payment_by_links
- No cascade delete issues
- Better for audit trails

### ✅ **Performance**
- No foreign key overhead
- Faster inserts/updates
- Better for high-throughput systems

## Testing After Migration

### Test Create Payment Link:
```bash
curl -X POST http://localhost:8001/v1/merchant/payment-links \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "amount": 100.00,
    "currency_id": 1,
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "customer_phone": "+1234567890"
  }'
```

### Verify merchant_id is UUID:
```sql
SELECT id, uuid, merchant_id, customer_name FROM payment_by_links LIMIT 5;

-- merchant_id should look like: 9d2e3f4a-5b6c-7d8e-9f0a-1b2c3d4e5f6a
```

## Tables Updated

| Table | merchant_id Changed | Foreign Key Removed |
|-------|-------------------|-------------------|
| payment_by_links | ✅ UUID | ✅ Yes |
| transactions | ✅ UUID | ✅ Yes |
| settlements | ✅ UUID | Already commented |
| batches | ✅ UUID | ✅ Yes |

## Next Steps

1. ✅ **Backup database** (if has data)
2. ✅ **Run migration** `php artisan migrate:fresh`
3. ✅ **Test API endpoints** with UUID merchant_id
4. ✅ **Verify data integrity**
5. ✅ **Update any seeds** to use UUID for merchant_id

## Important Notes

- 🔴 **merchant_id** is now **CHAR(36)** - must pass UUID strings
- 🔴 **No foreign key validation** - ensure merchant UUID exists in AuthService
- 🔴 **customer_id** is now **nullable** - customer info stored directly
- 🔴 **Requires fresh migration** if you have existing data

## Done! 🎉

All tables now use UUID for `merchant_id` and customer information is stored directly in `payment_by_links` table!


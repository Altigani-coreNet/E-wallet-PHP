<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Transaction;
use App\Traits\AppliesCountryScope;
use App\Scopes\CountryScope;

class Batch extends Model
{
    use HasFactory, AppliesCountryScope;

    protected $fillable = [
        'batch_number',
        'merchant_id',
        'user_id',
        'status',
        'total_amount',
        'transaction_count',
        'settled_at',
        'total_amount_refunded', 
        'total_amount_voided',
        'country_id' ,
        'currency_id',
        'currency_symbol',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    // Batch status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_SETTLED = 'settled';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function settlement()
    {
        return $this->hasOne(Settlement::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSettled($query)
    {
        return $query->where('status', self::STATUS_SETTLED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    // Helper methods
    public function markAsSettled()
    {
        $this->update([
            'status' => self::STATUS_SETTLED,
            'settled_at' => now(),
        ]);
    }

    public function markAsFailed()
    {
        $this->update([
            'status' => self::STATUS_FAILED,
        ]);
    }

    public function updateTotals()
    {
        $this->update([
            'total_amount' => $this->transactions()->sum('amount'),
            'transaction_count' => $this->transactions()->count(),
        ]);
    }

    public function generateBatchNumber()
    {
        $prefix = 'BATCH';
        $date = now()->format('Ymd');
        $merchantCode = str_pad($this->merchant_id, 4, '0', STR_PAD_LEFT);
        $sequence = str_pad($this->id, 6, '0', STR_PAD_LEFT);
        
        return "{$prefix}{$date}{$merchantCode}{$sequence}";
    }

    /**
     * Generate unique batch number for a merchant on a specific date
     * Each merchant gets a unique batch number per day
     */
    public static function generateUniqueBatchNumberForMerchant(string $merchantId, string $date = null): string
    {
        $date = $date ?: now()->format('Ymd');
        $prefix = 'BATCH';
        // $merchantCode = str_pad($merchantId, 4, '0', STR_PAD_LEFT);
        
        // Get the count of batches for this merchant on this date
        $batchCount = self::where('merchant_id', $merchantId)
            ->whereDate('created_at', $date)
            ->count();
        
        // Generate sequence number (1-based, padded to 3 digits)
        $sequence = str_pad($batchCount + 1, 3, '0', STR_PAD_LEFT);
        
        // Format: BATCH + YYYYMMDD + MERCHANT(4) + SEQUENCE(3)
        // Example: BATCH2024011512340001 (BATCH + 20240115 + 1234 + 001)
        $batchNumber = "{$prefix}{$date}{$sequence}";
        
        // Ensure uniqueness by checking if this batch number already exists
        while (self::where('batch_number', $batchNumber)->exists()) {
            $batchCount++;
            $sequence = str_pad($batchCount + 1, 3, '0', STR_PAD_LEFT);
            $batchNumber = "{$prefix}{$date}{$sequence}";
        }
        
        return $batchNumber;
    }

    /**
     * Get or create daily batch for a merchant
     * If no batch exists for today, create one
     */
    public static function getOrCreateDailyBatch(string $merchantId, string $userId, string $currency, ?string $currencySymbol = null): self
    {
        // dd($currency);
        $today = now()->format('Y-m-d');
        $countryId = auth('external')->user()->merchant?->country_id;
// dd($countryId);
        // Try to find existing pending batch for today
        $batch = self::where('merchant_id', $merchantId)
            ->where('user_id', $userId)
            ->where('country_id', $countryId)
            ->where('currency_id', $currency)
            ->whereDate('created_at', $today)
            ->where('status', self::STATUS_PENDING)
            ->first();
        
            // dd($currency);
        if (!$batch) {
            // Create new batch for today
            $batch = self::create([
                'batch_number' => self::generateUniqueBatchNumberForMerchant($merchantId),
                'merchant_id' => $merchantId,
                'user_id' => $userId,
                'country_id' => $countryId,
                'status' => self::STATUS_PENDING,
                'total_amount' => 0,
                'transaction_count' => 0,
                'currency_id' => $currency,
                'currency_symbol' => $currencySymbol,
            ]);
        }
        
        // dd($batch);
        return $batch;
    }

    // Note: currency_id is now a UUID from external service - no relationship
    // Currency data is stored in currency_symbol field
    public function getCurrencyAttribute()
    {
        // Return currency symbol if available
        return $this->currency_symbol ? (object) ['symbol' => $this->currency_symbol, 'currency_id' => $this->currency_id] : null;
    }
    /**
     * Get batch number format explanation
     */
    public static function getBatchNumberFormat(): string
    {
        return "Format: BATCH + YYYYMMDD + MERCHANT(4) + SEQUENCE(3)\n" .
               "Example: BATCH2024011512340001\n" .
               "Where: BATCH + 20240115 + 1234 + 001\n" .
               "       Prefix + Date + DailySequence";
    }

    // Additional batch management methods
    public function addTransaction(Transaction $transaction)
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \Exception('Cannot add transaction to non-pending batch');
        }

        $transaction->assignToBatch($this->id);
        $this->refresh();
        return $this;
    }

    public function removeTransaction(Transaction $transaction)
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \Exception('Cannot remove transaction from non-pending batch');
        }

        $transaction->removeFromBatch();
        $this->refresh();
        return $this;
    }

    public function canBeSettled()
    {
        // Check if all transactions are in final states
        $pendingTransactions = $this->transactions()
            ->whereNotIn('status', ['processed', 'approved', 'declined', 'failed'])
            ->count();

        return $pendingTransactions === 0;
    }

    public function getTransactionSummary()
    {
        $transactions = $this->transactions;
        
        return [
            'total_transactions' => $transactions->count(),
            'pending_transactions' => $transactions->where('status', 'pending')->count(),
            'approved_transactions' => $transactions->where('status', 'approved')->count(),
            'declined_transactions' => $transactions->where('status', 'declined')->count(),
            'failed_transactions' => $transactions->where('status', 'failed')->count(),
            'processed_transactions' => $transactions->where('status', 'processed')->count(),
            'refunded_transactions' => $transactions->where('status', 'refunded')->count(),
        ];
    }

    public function getTotalAmountByStatus($status)
    {
        return $this->transactions()
            ->where('status', $status)
            ->sum('amount');
    }

    public function isEmpty()
    {
        return $this->transactions()->count() === 0;
    }

    public function hasTransactions()
    {
        return $this->transactions()->count() > 0;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Add global scope to automatically filter by regions from X-Regions header
        static::addGlobalScope(new CountryScope());
    }
}

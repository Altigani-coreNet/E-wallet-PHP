<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use App\Models\Partner;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Batch;
use App\Traits\AppliesCountryScope;
use App\Scopes\CountryScope;
use App\Models\Currency;

class Transaction extends Model
{
    use HasFactory, AppliesCountryScope;

    public const TRANSACTION_STATUS = [
        'pending',
        'approved',
        'declined',
        'failed',
        'processed',
        'refunded',
        'captured',
        'voided',
        'cancelled',
        'expired',
        'reversed',
    ];

    public const PAYMENT_TYPES = [
        'card',
        'web', 
        'bank',
        'mobile',
        'qr',
        'other',
    ];

    protected $fillable = [
        // Core Transaction Details
        'amount',
        'currency_id',
        'currency_symbol',
        'currency_object',
        'transaction_id',
        'timestamp',
        'transaction_type',

        // Payment Processing Details
        'batch_no',
        'trace_no',
        'rrn',
        'auth_code',
        'mid',
        'tid',
        'state',
        'description',
        'transaction_datetime',
        'invoice_no',
        'card_number',
        'expiry',
        'method',
        'ref_no',
        'atc',
        'tvr',
        'app_name',
        'tsi',
        'sdk',

        // Payment Method & Security
        'payment_method_id',
        'emv_data',
        'pin_block',
        'ksn',

        // Status & Additional Fields
        'status',
        'response_data',
        'processed_at',
        'notes',
        'metadata',

        // Refund and Void Fields
        'refunded_amount',
        'voided_amount',
        'cancelled_amount',
        'original_amount',

        // Foreign Keys
        'terminal_id',
        'merchant_id',
        'partner_id',
        'service_category_id',
        'service_id',
        'batch_id',
        'user_id',
        // Refund/Type
        'type',
        'reference_transaction_id',
        'country_id', 
        'payment_type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'voided_amount' => 'decimal:2',
        'cancelled_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'timestamp' => 'datetime',
        'transaction_datetime' => 'datetime',
        'processed_at' => 'datetime',
        'metadata' => 'array',
        'currency_object' => 'array',
        'reference_transaction_id' => 'integer',
        'type' => 'string',
    ];

    protected $appends = [
        'partner_name',
        'service_category_name',
        'service_name',
    ];

    /**
     * Business / payment datetime fields: app timezone (e.g. Asia/Dubai) for API parity with DB UI.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return Carbon::instance($date)
            ->timezone(config('app.timezone'))
            ->toIso8601String();
    }

    /**
     * Laravel audit timestamps: always UTC (ISO-8601 with Z) for consistency across regions.
     */
    protected function serializeAuditTimestamp(DateTimeInterface $date): string
    {
        return Carbon::instance($date)->utc()->toIso8601String();
    }

    /**
     * {@inheritdoc}
     *
     * Only Laravel timestamp columns ({@see getDates()}) are serialized here — use UTC.
     * Other datetime fields (timestamp, transaction_datetime, …) use {@see serializeDate()} via casts.
     */
    protected function addDateAttributesToArray(array $attributes)
    {
        foreach ($this->getDates() as $key) {
            if (! isset($attributes[$key])) {
                continue;
            }

            $attributes[$key] = $this->serializeAuditTimestamp(
                $this->asDateTime($attributes[$key])
            );
        }

        return $attributes;
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Note: terminal_id is now a UUID from AuthService - no relationship
    
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function serviceTransaction()
    {
        return $this->hasOne(ServiceTransaction::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function logs()
    {
        return $this->hasMany(TransactionLog::class);
    }

    public function referenceTransaction()
    {
        return $this->belongsTo(Transaction::class, 'reference_transaction_id');
    }

    public function refunds()
    {
        return $this->hasMany(Transaction::class, 'reference_transaction_id');
    }

    public function paymentLink()
    {
        return $this->belongsTo(\App\Models\PaymentByLink::class, 'metadata->payment_link_id', 'id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    // Backward-compatible computed currency payload from legacy fields.
    public function getCurrencyAttribute()
    {
        // Return currency data from currency_object if available, otherwise return currency_symbol
        if ($this->currency_object) {
            return (object) $this->currency_object;
        }
        
        // Fallback to currency_symbol if currency_object is not available
        return $this->currency_symbol ? (object) ['symbol' => $this->currency_symbol] : null;
    }

    public function getPartnerNameAttribute(): ?string
    {
        if ($this->relationLoaded('partner') && $this->partner) {
            return $this->partner->name;
        }

        return $this->partner_id ? Partner::query()->where('id', $this->partner_id)->value('name') : null;
    }

    public function getServiceCategoryNameAttribute(): ?string
    {
        if ($this->relationLoaded('serviceCategory') && $this->serviceCategory) {
            return $this->serviceCategory->name_en ?? null;
        }

        return $this->service_category_id
            ? ServiceCategory::query()->where('id', $this->service_category_id)->value('name_en')
            : null;
    }

    public function getServiceNameAttribute(): ?string
    {
        if ($this->relationLoaded('service') && $this->service) {
            return $this->service->service_name['en'] ?? null;
        }

        if (! $this->service_id) {
            return null;
        }

        $serviceName = Service::query()->where('id', $this->service_id)->value('service_name');
        if (is_array($serviceName)) {
            return $serviceName['en'] ?? null;
        }

        if (is_string($serviceName)) {
            $decoded = json_decode($serviceName, true);
            return is_array($decoded) ? ($decoded['en'] ?? null) : null;
        }

        return null;
    }

    // Batch-related scopes
    public function scopeInBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeNotInBatch($query)
    {
        return $query->whereNull('batch_id');
    }

    public function scopeByBatchStatus($query, $status)
    {
        return $query->whereHas('batch', function ($q) use ($status) {
            $q->where('status', $status);
        });
    }

    public function scopeByMerchantBatch($query, $merchantId)
    {
        return $query->whereHas('batch', function ($q) use ($merchantId) {
            $q->where('merchant_id', $merchantId);
        });
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInPendingBatch($query)
    {
        return $query->whereHas('batch', function ($q) {
            $q->where('status', 'pending');
        });
    }

    public function scopeInSettledBatch($query)
    {
        return $query->whereHas('batch', function ($q) {
            $q->where('status', 'settled');
        });
    }

    public function scopeInFailedBatch($query)
    {
        return $query->whereHas('batch', function ($q) {
            $q->where('status', 'failed');
        });
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'DECLINED');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'FAILED');
    }

    // Helper methods - These are now overridden below with logging functionality

    // Batch-related helper methods - These are now overridden below with logging functionality

    public function isInBatch()
    {
        return !is_null($this->batch_id);
    }

    public function getBatchStatus()
    {
        return $this->batch ? $this->batch->status : null;
    }

    public function canBeSettled()
    {
        return $this->isInBatch() &&
            in_array($this->status, ['processed', 'approved', 'declined']) &&
            $this->batch->status === 'pending';
    }

    public function getBatchNumber()
    {
        return $this->batch ? $this->batch->batch_number : null;
    }

    public function getBatchTotalAmount()
    {
        return $this->batch ? $this->batch->total_amount : 0;
    }

    public function getBatchTransactionCount()
    {
        return $this->batch ? $this->batch->transaction_count : 0;
    }

    public function isBatchSettled()
    {
        return $this->batch && $this->batch->status === 'settled';
    }

    public function isBatchFailed()
    {
        return $this->batch && $this->batch->status === 'failed';
    }

    public function canBeAddedToBatch()
    {
        // Transaction can be added to batch if:
        // 1. It's not already in a batch, or
        // 2. It's in a pending batch
        return !$this->isInBatch() ||
            ($this->isInBatch() && $this->batch->status === 'pending');
    }

    public function getBatchInfo()
    {
        if (!$this->isInBatch()) {
            return null;
        }

        return [
            'batch_id' => $this->batch_id,
            'batch_number' => $this->batch->batch_number,
            'batch_status' => $this->batch->status,
            'batch_total_amount' => $this->batch->total_amount,
            'batch_transaction_count' => $this->batch->transaction_count,
            'batch_created_at' => $this->batch->created_at,
            'batch_settled_at' => $this->batch->settled_at,
        ];
    }

    // Refund and Void Methods
    public static function generateTransactionId()
    {
        return 'TXN-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    public function refund($amount, $description = null, $metadata = [])
    {
        // Validate refund amount
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Refund amount must be greater than 0');
        }

        $availableAmount = $this->getAvailableAmountForRefund();
        if ($amount > $availableAmount) {
            throw new \InvalidArgumentException("Cannot refund {$amount}. Available amount: {$availableAmount}");
        }

        // Store original amount if not set
        if (!$this->original_amount) {
            $this->original_amount = $this->amount;
        }

        $oldRefundedAmount = $this->refunded_amount ?? 0;
        $newRefundedAmount = $oldRefundedAmount + $amount;

        // Update transaction
        $this->update([
            'refunded_amount' => $newRefundedAmount,
            'amount' => $this->original_amount - $newRefundedAmount,
        ]);

        if ($this->batch) {
            $this->batch->increment('total_amount_refunded', $amount);
        }

        // Create refund transaction
        $refundData = $this->toArray();
        unset($refundData['id'], $refundData['created_at'], $refundData['updated_at'], $refundData['refunded_amount'], $refundData['voided_amount'], $refundData['cancelled_amount'], $refundData['original_amount']);
        $refundData['amount'] = $amount;
        $refundData['type'] = 'refund';
        $refundData['reference_transaction_id'] = $this->id;
        $refundData['description'] = $description;
        $refundData['metadata'] = array_merge($this->metadata ?? [], $metadata);
        $refundData['status'] = 'refunded';
        $refundData['transaction_id'] = self::generateTransactionId();
        $refundTransaction = self::create($refundData);

        // Log the refund
        TransactionLog::logRefund(
            $this->id,
            $this->original_amount,
            $amount,
            $description,
            array_merge($metadata, [
                'refund_amount' => $amount,
                'total_refunded' => $newRefundedAmount,
                'remaining_amount' => $this->amount,
                'refund_transaction_id' => $refundTransaction->id,
            ])
        );

        return $this;
    }

    public function void($description = null, $metadata = [])
    {
        if ($this->voided_amount > 0) {
            throw new \InvalidArgumentException('Transaction has already been voided');
        }

        if ($this->refunded_amount > 0) {
            throw new \InvalidArgumentException('Cannot void transaction that has refunds');
        }

        // Store original amount if not set
        if (!$this->original_amount) {
            $this->original_amount = $this->amount;
        }

        $voidAmount = $this->amount;

        // Update transaction
        $this->update([
            'voided_amount' => $voidAmount,
            'amount' => 0,
            'status' => 'voided',
        ]);

        // Log the void
        TransactionLog::logVoid(
            $this->id,
            $voidAmount,
            $description,
            array_merge($metadata, [
                'void_amount' => $voidAmount,
                'original_amount' => $this->original_amount,
            ])
        );

        return $this;
    }

    public function cancel($description = null, $metadata = [])
    {
        if ($this->cancelled_amount > 0) {
            throw new \InvalidArgumentException('Transaction has already been cancelled');
        }

        if ($this->refunded_amount > 0 || $this->voided_amount > 0) {
            throw new \InvalidArgumentException('Cannot cancel transaction that has refunds or voids');
        }

        // Store original amount if not set
        if (!$this->original_amount) {
            $this->original_amount = $this->amount;
        }

        $cancelAmount = $this->amount;

        // Update transaction
        $this->update([
            'cancelled_amount' => $cancelAmount,
            'amount' => 0,
            'status' => 'cancelled',
        ]);

        if ($this->batch) {
            $this->batch->increment('total_amount_cancelled', $cancelAmount);
        }

        // Log the cancellation
        TransactionLog::logCancellation(
            $this->id,
            $cancelAmount,
            $description,
            array_merge($metadata, [
                'cancel_amount' => $cancelAmount,
                'original_amount' => $this->original_amount,
            ])
        );

        return $this;
    }

    public function partialRefund($amount, $description = null, $metadata = [])
    {
        return $this->refund($amount, $description, $metadata);
    }

    public function getAvailableAmountForRefund()
    {
        $refunded = $this->refunded_amount ?? 0;
        $voided = $this->voided_amount ?? 0;
        $cancelled = $this->cancelled_amount ?? 0;

        // dd($this->amount, $refunded, $voided, $cancelled);
        return $this->original_amount - $refunded - $voided - $cancelled;
    }

    public function getTotalRefundedAmount()
    {
        return $this->refunded_amount ?? 0;
    }

    public function getTotalVoidedAmount()
    {
        return $this->voided_amount ?? 0;
    }

    public function getTotalCancelledAmount()
    {
        return $this->cancelled_amount ?? 0;
    }

    public function isRefundable()
    {
        return $this->getAvailableAmountForRefund() > 0 &&
            $this->status !== 'cancelled' &&
            $this->status !== 'voided';
    }

    public function isVoidable()
    {
        return $this->voided_amount == 0 &&
            $this->refunded_amount == 0 &&
            $this->status !== 'cancelled';
    }

    public function isCancellable()
    {
        return $this->cancelled_amount == 0 &&
            $this->refunded_amount == 0 &&
            $this->voided_amount == 0;
    }

    // Override existing methods to add logging
    public function markAsProcessed($responseData = null)
    {
        $oldStatus = $this->status;

        $this->update([
            'status' => 'processed',
            'response_data' => $responseData,
            'processed_at' => now(),
        ]);

        // Log status change
        TransactionLog::logStatusChange(
            $this->id,
            $oldStatus,
            'processed',
            'Transaction processed',
            ['response_data' => $responseData]
        );
    }

    public function markAsApproved($responseData = null)
    {
        $oldStatus = $this->status;

        $this->update([
            'status' => 'approved',
            'response_data' => $responseData,
            'processed_at' => now(),
        ]);

        // Log status change
        TransactionLog::logStatusChange(
            $this->id,
            $oldStatus,
            'approved',
            'Transaction approved',
            ['response_data' => $responseData]
        );
    }

    public function markAsCaptured($responseData = null)
    {
        $oldStatus = $this->status;

        $this->update([
            'status' => 'captured',
            'response_data' => $responseData,
            'processed_at' => now(),
        ]);

        // Log status change
        TransactionLog::logStatusChange(
            $this->id,
            $oldStatus,
            'declined',
            'Transaction declined',
            ['response_data' => $responseData]
        );
    }

    public function markAsDeclined($responseData = null)
    {
        $oldStatus = $this->status;

        $this->update([
            'status' => 'declined',
            'response_data' => $responseData,
            'processed_at' => now(),
        ]);

        // Log status change
        TransactionLog::logStatusChange(
            $this->id,
            $oldStatus,
            'declined',
            'Transaction declined',
            ['response_data' => $responseData]
        );
    }

    public function markAsFailed($responseData = null)
    {
        $oldStatus = $this->status;

        $this->update([
            'status' => 'failed',
            'response_data' => $responseData,
            'processed_at' => now(),
        ]);

        // Log status change
        TransactionLog::logStatusChange(
            $this->id,
            $oldStatus,
            'failed',
            'Transaction failed',
            ['response_data' => $responseData]
        );
    }

    public function assignToBatch($batchId)
    {
        $oldBatchId = $this->batch_id;

        $this->update(['batch_id' => $batchId]);

        // Log batch assignment
        TransactionLog::logBatchAssignment(
            $this->id,
            $batchId,
            "Transaction assigned to batch {$batchId}"
        );

        // Update batch totals if batch exists
        if ($batch = $this->batch) {
            $batch->updateTotals();
        }

        return $this;
    }

    public function removeFromBatch()
    {
        $oldBatchId = $this->batch_id;

        $this->update(['batch_id' => null]);

        // Log batch removal
        if ($oldBatchId) {
            TransactionLog::logBatchRemoval(
                $this->id,
                $oldBatchId,
                "Transaction removed from batch {$oldBatchId}"
            );
        }

        // Update old batch totals if it exists
        if ($oldBatchId && $oldBatch = Batch::find($oldBatchId)) {
            $oldBatch->updateTotals();
        }

        return $this;
    }

    // Get transaction audit trail
    public function getAuditTrail()
    {
        return $this->logs()->with('performer')->orderBy('created_at', 'asc')->get();
    }

    public function getTransactionHistory($limit = null)
    {
        $query = $this->logs()->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'currency',
        'transaction_id',
        'timestamp',
        'transaction_type',
        'payment_method_id',
        'merchant_id',
        'terminal_id',
        'emv_data',
        'pin_block',
        'ksn',
        'status',
        'response_data',
        'processed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'timestamp' => 'datetime',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
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

    // Helper methods
    public function markAsProcessed($responseData = null)
    {
        $this->update([
            'status' => 'PROCESSED',
            'response_data' => $responseData,
            'processed_at' => now(),
        ]);
    }

    public function markAsApproved($responseData = null)
    {
        $this->update([
            'status' => 'APPROVED',
            'response_data' => $responseData,
            'processed_at' => now(),
        ]);
    }

    public function markAsDeclined($responseData = null)
    {
        $this->update([
            'status' => 'DECLINED',
            'response_data' => $responseData,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed($responseData = null)
    {
        $this->update([
            'status' => 'FAILED',
            'response_data' => $responseData,
            'processed_at' => now(),
        ]);
    }
} 
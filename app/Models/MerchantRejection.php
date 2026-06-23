<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantRejection extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'rejection_reason',
        'invalid_fields',
        'missing_attachments',
        'rejected_by',
    ];

    protected $casts = [
        'invalid_fields' => 'array',
        'missing_attachments' => 'array',
    ];

    /**
     * Get the merchant that was rejected.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get the admin who rejected the merchant.
     */
    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
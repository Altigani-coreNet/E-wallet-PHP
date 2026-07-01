<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRejection extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'customer_id',
        'rejection_reason',
        'invalid_fields',
        'missing_attachments',
        'rejected_by',
    ];

    protected $casts = [
        'invalid_fields' => 'array',
        'missing_attachments' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'rejected_by');
    }
}

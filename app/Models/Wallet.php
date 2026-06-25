<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory, HasUuids;

    public const STATUSES = [
        'active',
        'frozen',
        'closed',
    ];

    protected $fillable = [
        'user_number',
        'wallet_id',
        'customer_id',
        'merchant_id',
        'currency_id',
        'currency_code',
        'balance',
        'available_balance',
        'account_id',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}

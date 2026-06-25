<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'sub_type',
        'is_enabled',
        'description',
        'created_by',
    ];

    protected $casts = [
        'is_enabled' => 'integer',
    ];

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccountType::class, 'type');
    }

    public function accountSubType(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccountSubType::class, 'sub_type');
    }

    public function transactionLines(): HasMany
    {
        return $this->hasMany(TransactionLine::class, 'account_id');
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'account_id');
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', 1);
    }

    public function scopeByCode(Builder $query, int $code): Builder
    {
        return $query->where('code', $code);
    }
}

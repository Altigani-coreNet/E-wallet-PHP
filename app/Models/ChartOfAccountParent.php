<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChartOfAccountParent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sub_type',
        'type',
        'account',
        'created_by',
    ];

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccountType::class, 'type');
    }

    public function accountSubType(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccountSubType::class, 'sub_type');
    }

    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account');
    }
}

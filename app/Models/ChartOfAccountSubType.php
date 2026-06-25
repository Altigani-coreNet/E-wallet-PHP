<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccountSubType extends Model
{
    use HasFactory;

    public static array $chartOfAccountSubType = [
        'assets' => [
            '1' => 'Current Asset',
            '2' => 'Inventory Asset',
            '3' => 'Non-current Asset',
        ],
        'liabilities' => [
            '1' => 'Current Liabilities',
            '2' => 'Long Term Liabilities',
        ],
        'equity' => [
            '1' => 'Owners Equity',
            '2' => 'Share Capital',
            '3' => 'Retained Earnings',
        ],
        'income' => [
            '1' => 'Sales Revenue',
            '2' => 'Other Revenue',
        ],
        'costs of goods sold' => [
            '1' => 'Costs of Goods Sold',
        ],
        'expenses' => [
            '1' => 'Payroll Expenses',
            '2' => 'General and Administrative expenses',
        ],
    ];

    protected $fillable = [
        'name',
        'type',
    ];

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccountType::class, 'type');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'sub_type');
    }
}

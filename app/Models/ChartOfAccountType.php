<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccountType extends Model
{
    use HasFactory;

    public static array $chartOfAccountType = [
        'assets' => 'Assets',
        'liabilities' => 'Liabilities',
        'equity' => 'Equity',
        'income' => 'Income',
        'costs of goods sold' => 'Costs of Goods Sold',
        'expenses' => 'Expenses',
    ];

    protected $fillable = [
        'name',
        'created_by',
    ];

    public function subTypes(): HasMany
    {
        return $this->hasMany(ChartOfAccountSubType::class, 'type');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'type');
    }
}

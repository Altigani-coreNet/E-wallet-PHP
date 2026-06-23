<?php

namespace App\Models;

use App\Traits\hasOld;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Unit extends Model
{
    use HasFactory, HasTranslations, HasStatus, hasOld, SoftDeletes;

    public array $translatable = ['name'];

    public $fillable = [
        "name",
        "code",
        "shop_id",
        "created_by",
        "base_unit_id",
        "operator",
        "operation_value",
        "status"
    ];

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
} 
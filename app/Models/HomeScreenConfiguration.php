<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeScreenConfiguration extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'configurable_type',
        'configurable_id',
        'sort_order',
        'is_quick_action',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_quick_action' => 'boolean',
    ];

    public function configurable()
    {
        return $this->morphTo();
    }
}

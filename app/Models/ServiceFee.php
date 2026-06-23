<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceFee extends Model
{
    protected $fillable = [
        'name',
        'type',
        'fees'
    ];

    protected $casts = [
        'fees' => 'decimal:2'
    ];
}

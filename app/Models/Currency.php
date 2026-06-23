<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Currency extends Model
{
    use HasFactory, HasUuids, HasTranslations;
    
    protected $fillable = [
        'country',
        'name',
        'symbol',
        'currency_code'
    ];

    public array $translatable = ['currency_code', 'symbol'];
}


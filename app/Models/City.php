<?php

namespace App\Models;

use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class City extends Model
{
    use HasFactory, HasStatus, SoftDeletes, HasUuids , HasTranslations;

    public array $translatable = ['name'];

    public $guarded = [];

    public $hidden = [
        "deleted_at", "updated_at", "created_at"
    ];

    protected $casts = [
        'name' => 'array', // Cast JSON to array
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}


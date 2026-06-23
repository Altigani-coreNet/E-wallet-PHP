<?php

namespace App\Models;

use App\Traits\hasOld;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Brand extends Model
{
    /** @use HasFactory<\Database\Factories\BrandFactory> */
    use HasFactory, HasTranslations, HasStatus, hasOld, SoftDeletes;

    public array $translatable = ['name'];

    public $fillable = [
        "name",
        "status",
        "image",
        "shop_id"
    ];

    public function getImagePath()
    {
        return asset($this->image);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Feature extends Model
{
    /** @use HasFactory<\Database\Factories\FeatureFactory> */
    use HasTranslations, HasFactory;

    protected $fillable = ["name", "plan_id", "is_enabled"];

    public $translatable = ["name"];
    public $guarded = ["id"];
}

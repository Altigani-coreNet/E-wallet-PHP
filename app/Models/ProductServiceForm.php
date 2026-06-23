<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ProductServiceFormField;
use Spatie\Translatable\HasTranslations;

class ProductServiceForm extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['form_name'];

    protected $fillable = [
        'product_id',
        'form_name',
        'form_url',
        'country_id',
    ];

    protected $casts = [
        'form_name' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function fields()
    {
        return $this->hasMany(ProductServiceFormField::class, 'product_service_form_id')->orderBy('sort_order');
    }
}


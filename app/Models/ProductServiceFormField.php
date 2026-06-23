<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductServiceFormField extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_service_form_id',
        'label_en',
        'label_ar',
        'key',
        'type',
        'options_json',
        'customization_json',
        'sort_order',
        'is_required',
        'status',
        'country_id',
    ];

    protected $casts = [
        'options_json' => 'array',
        'customization_json' => 'array',
        'is_required' => 'boolean',
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function form()
    {
        return $this->belongsTo(ProductServiceForm::class, 'product_service_form_id');
    }
}


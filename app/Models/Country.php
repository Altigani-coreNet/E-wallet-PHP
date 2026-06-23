<?php

namespace App\Models;

use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Country extends Model
{
    use HasFactory, HasStatus, SoftDeletes, HasUuids, HasTranslations;

    public array $translatable = ['name'];
    
    public $guarded = [];

    public $hidden = [
        "deleted_at", "updated_at", "created_at"
    ];

    protected $casts = [
        'name' => 'array', // Cast JSON to array
    ];

    public function cities()
    {
        return $this->hasMany(City::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the flag URL for the country
     */
    public function getFlagUrl(): ?string
    {
        if (!$this->short_name) {
            return null;
        }

        $short_name = strtolower($this->short_name);
        $flagPath = "flags/{$short_name}.jpg";
        $fullPath = public_path($flagPath);
        
        // Check if flag file exists
        if (file_exists($fullPath)) {
            return asset($flagPath);
        }

        // Return null if flag doesn't exist
        return asset($flagPath);
    }

    /**
     * Get the flag path (without URL)
     */
    public function getFlagPath(): ?string
    {
        if (!$this->short_name) {
            return null;
        }

        $short_name = strtolower($this->short_name);
        $flagPath = "flags/{$short_name}.jpg";
        $fullPath = public_path($flagPath);
        
        // Check if flag file exists
        if (file_exists($fullPath)) {
            return $flagPath;
        }

        return $flagPath;
    }
}


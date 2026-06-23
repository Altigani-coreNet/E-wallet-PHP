<?php

namespace App\Models;

use App\Traits\hasOld;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Psy\Util\Str;
use Spatie\Translatable\HasTranslations;

/**
 * @method setTranslation(string $string, string $string1, mixed $en)
 * @method static withCount(string $string)
 * @property mixed $type
 * @property bool|mixed $status
 * @property mixed|string $image
 * @property mixed $url
 */
class Category extends Model
{
    use HasFactory, HasTranslations, HasStatus, hasOld, softDeletes;


    public array $translatable = ['name'];

    public $fillable = [
        "name",
        "status",
        "type",
        "image",
    ];

    public function SubCategories(): hasMany
    {
        return $this->hasMany(Category::class, "parent_id");
    }

    public function getImagePath()
    {
       return asset($this->image);
    }

}

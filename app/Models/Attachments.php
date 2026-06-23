<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static insert(array $attachmentsData)
 */
class Attachments extends Model
{
    use HasFactory, HasUuids;

    const TYPES = ["image", "document"];
    protected $guarded = [];

    public $fillable = [
        'url',
        'url_type',
        'type',
        'created_at',
        'updated_at',
        'attachable_type',
        'attachable_id',
        'title',
        'details',
    ];

    public $appends = ["attachment_url"];

    public function getAttachmentUrlAttribute()
    {

        $file = public_path('mec\\' . $this->url);

        if (file_exists($file)) {
            // If the file exists in the "msc" directory, return the path with "msc"
            return asset("mec\\" . $this->url);
        } else {
            // If the file does not exist, return the path without "msc"
            return asset($this->url);
        }
    }

    public function attachable()
    {
        return $this->morphTo();
    }

//    public function getUrlAttribute($key)
//    {
//        switch ($this->attachable_type) {
//            case 'App\Models\Profile':
//                return  asset('profiles/attachments/' . $key);
//
//            default:
//                return asset('projects/attachments/' . $key);
//        }
//    }

    public static function AttachMUltiFIleFiles($files, Model $model, string $disc)
    {
        if (count($files) > 0) {
            foreach ($files as $key => $value) {
                $name = $value->hashName();
                $value->store($disc, 'public');
                // I Do This For First Step
                $attachment = new Attachments;
                // $attachment->attacheable = $agent->id;
                $attachment->url = $name;
                $model->attachments()->save($attachment);
            }
            return true;
        }
    }

}

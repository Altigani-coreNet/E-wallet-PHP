<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChangeHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'changeable_type',
        'changeable_id',
        'actor_type',
        'actor_id',
        'before',
        'after',
        'payload',
        'change_request_id',
        'user_id',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'payload' => 'array',
        'user_id' => 'integer',
    ];

    public function changeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }
}



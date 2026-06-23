<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'changeable_type',
        'changeable_id',
        'requester_type',
        'requester_id',
        'approver_type',
        'approver_id',
        'payload',
        'reason',
        'status',
        'moderation_note',
        'approved_id',
        'approved_at',
        'rejected_at',
        'has_file',
    ];

    protected $casts = [
        'payload' => 'array',
        'approved_id' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'has_file' => 'boolean',
    ];

    public function changeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requester(): MorphTo
    {
        return $this->morphTo();
    }

    public function approver(): MorphTo
    {
        return $this->morphTo();
    }
}



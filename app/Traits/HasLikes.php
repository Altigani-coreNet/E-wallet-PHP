<?php

namespace App\Traits;


use App\Models\Like;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasLikes
{
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function UserLikes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable')->where("user_id", auth()->user()->id);
    }

    public function isLiked(?string $type = null): bool
    {
        $query = $this->likes()->where('user_id', auth()->user()->id);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->exists();
    }

    public function addLike(?string $type = null): void
    {
        $existingLike = $this->likes()
            ->where('user_id', auth()->user()->id)
            ->where('type', $type)
            ->first();

        if ($existingLike) {
            // If the like exists, delete it and decrement the likes count
            $existingLike->delete();
            $this->decrement('likes');
        } else {
            // If the like does not exist, create it and increment the likes count
            $this->likes()->create([
                'user_id' => auth()->user()->id,
                'type' => $type,
            ]);

            $this->increment('likes');
        }
    }

}

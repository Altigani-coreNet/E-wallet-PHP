<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * @method static where(string $string, $old_id)
 */
trait hasOld
{
    public static function findOld(Builder $query, $old_id)
    {
        return $query->where('old_id', $old_id)->first();
    }

    public static function findOldAndGetId($old_id): ?int
    {
        return self::select('id')->where('old_id', $old_id)->first()->id;
    }
}

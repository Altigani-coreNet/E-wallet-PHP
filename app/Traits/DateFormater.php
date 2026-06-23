<?php

namespace App\Traits;

use Carbon\Carbon;

trait DateFormater
{
    public function getDate($date): string
    {
        try {
            // Try to parse the date as 'd-m-Y'
            return Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            // If it fails, it means it's in 'Y-m-d' format
            return Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d');
        }
    }
}

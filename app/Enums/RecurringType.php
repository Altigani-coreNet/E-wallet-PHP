<?php

namespace App\Enums;

enum RecurringType: string
{
    case Onetime = 'Onetime';
    case Weekly = 'Weekly';
    case Monthly = 'Monthly';
    case Yearly = 'Yearly';
}

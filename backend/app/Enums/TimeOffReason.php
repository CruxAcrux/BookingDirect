<?php

namespace App\Enums;

enum TimeOffReason: string
{
    case Vacation = 'vacation';
    case Sick = 'sick';
    case Other = 'other';
}

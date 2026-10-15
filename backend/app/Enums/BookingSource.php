<?php

namespace App\Enums;

enum BookingSource: string
{
    case Online = 'online';
    case Staff = 'staff';
    case WalkIn = 'walk_in';
}

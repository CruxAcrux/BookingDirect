<?php

return [
    'slot_taken' => 'This time was just taken. Please choose another time.',
    'too_late' => 'It is too late to change this booking online. Please contact the shop.',
    'not_active' => 'This booking has already been cancelled or completed.',
    'in_past' => 'The time has already passed.',
    'not_started' => 'The visit has not started yet.',
    'invalid_outcome' => 'A visit can only be marked as completed or no-show.',
    'shop_closed' => 'This shop is not taking bookings at the moment.',
    'name_required' => 'Please tell us your name.',
    'otp' => [
        'wrong' => 'The code is wrong. Check the SMS and try again.',
        'expired' => 'The code has expired or been used. Request a new code.',
    ],
    'sms' => [
        'otp' => ':code is your code for :shop. It is valid for :minutes minutes.',
    ],
];

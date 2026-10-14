<?php

use App\Support\PhoneNumber;

it('normalizes phone numbers to E.164', function (string $input, ?string $expected) {
    expect(PhoneNumber::normalize($input))->toBe($expected);
})->with([
    'swedish mobile' => ['070-123 45 67', '+46701234567'],
    'swedish landline' => ['031-12 34 56', '+4631123456'],
    'already E.164' => ['+46 70 123 45 67', '+46701234567'],
    'international prefix' => ['0047 912 34 567', '+4791234567'],
    'too short' => ['12', null],
    'letters' => ['070-CALL-NOW', null],
]);

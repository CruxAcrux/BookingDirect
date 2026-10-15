<?php

namespace App\Sms;

use Illuminate\Database\Eloquent\Model;

/**
 * Sends text messages. Callers depend on this interface only, so a real provider can be
 * added as another implementation without touching them.
 */
interface SmsSender
{
    /**
     * @param  string  $to  E.164 phone number
     * @param  string  $type  what the message is for, e.g. "otp" or "reminder"
     */
    public function send(string $to, string $body, string $type, ?Model $related = null): void;
}

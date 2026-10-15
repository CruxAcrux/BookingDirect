<?php

namespace App\Sms;

use App\Enums\SmsType;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Model;

/**
 * Sends text messages. Callers depend on this interface only, so a real provider can be
 * added as another implementation without touching them.
 */
interface SmsSender
{
    /**
     * @param  Shop  $shop  the shop the message is sent on behalf of
     * @param  string  $to  E.164 phone number
     */
    public function send(Shop $shop, string $to, string $body, SmsType $type, ?Model $related = null): void;
}

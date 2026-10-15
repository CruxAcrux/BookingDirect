<?php

namespace App\Sms;

use App\Enums\SmsType;
use App\Models\Shop;
use App\Models\SmsMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Records messages instead of sending them: in the `sms_messages` table (for the
 * dashboard's sent-SMS page) and in the application log.
 */
class LogSmsSender implements SmsSender
{
    public function send(Shop $shop, string $to, string $body, SmsType $type, ?Model $related = null): void
    {
        $message = new SmsMessage(['to' => $to, 'body' => $body, 'type' => $type, 'sent_at' => now()]);
        $message->shop_id = $shop->id;
        $message->related()->associate($related);
        $message->save();

        Log::info('SMS sent', ['shop' => $shop->id, 'to' => $to, 'type' => $type->value, 'body' => $body]);
    }
}

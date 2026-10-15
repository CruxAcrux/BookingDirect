<?php

namespace App\Sms;

use App\Models\SmsMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Records messages instead of sending them: in the `sms_messages` table (for the
 * dashboard's sent-SMS page) and in the application log.
 */
class LogSmsSender implements SmsSender
{
    public function send(string $to, string $body, string $type, ?Model $related = null): void
    {
        $message = new SmsMessage(['to' => $to, 'body' => $body, 'type' => $type, 'sent_at' => now()]);
        $message->related()->associate($related);
        $message->save();

        Log::info('SMS sent', ['to' => $to, 'type' => $type, 'body' => $body]);
    }
}

<?php

namespace App\Notifications\Channels;

use App\Sms\SmsSender;
use Illuminate\Notifications\Notification;

/**
 * Delivers notifications that implement {@see SendsSms} through the configured SMS driver.
 * Use it in `via()` as `SmsChannel::class`; the recipient's number comes from
 * `routeNotificationForSms()`.
 */
class SmsChannel
{
    public function __construct(private readonly SmsSender $sender) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof SendsSms || ! method_exists($notifiable, 'routeNotificationFor')) {
            return;
        }

        $to = $notifiable->routeNotificationFor('sms', $notification);
        if (! is_string($to) || $to === '') {
            return;
        }

        $sms = $notification->toSms($notifiable);
        $this->sender->send($sms->shop, $to, $sms->body, $sms->type, $sms->related);
    }
}

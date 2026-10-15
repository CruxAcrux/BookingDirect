<?php

namespace App\Notifications\Booking;

use App\Enums\SmsType;
use App\Models\Booking;
use App\Models\Customer;
use App\Notifications\Channels\OutgoingSms;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Notifications\Messages\MailMessage;

/** Sent a day before (SMS and email) and two hours before (SMS only). */
class BookingReminder extends BookingNotification
{
    public function __construct(Booking $booking, public readonly SmsType $kind)
    {
        parent::__construct($booking);
    }

    public function via(Customer $notifiable): array
    {
        return $this->kind === SmsType::Reminder24h ? parent::via($notifiable) : [SmsChannel::class];
    }

    public function shouldSend(Customer $notifiable, string $channel): bool
    {
        return $this->stillScheduled();
    }

    public function toSms(object $notifiable): OutgoingSms
    {
        $key = $this->kind === SmsType::Reminder24h ? 'notifications.sms.reminder_24h' : 'notifications.sms.reminder_2h';
        $body = __($key, $this->details());

        if ($this->kind === SmsType::Reminder2h && $this->shop()->address !== null) {
            $body .= "\n".$this->shop()->address;
        }

        return new OutgoingSms($this->shop(), $body, $this->kind, $this->booking);
    }

    public function toMail(Customer $notifiable): MailMessage
    {
        return $this->visitLines(
            $this->mail('notifications.mail.reminder.subject', $notifiable)
                ->line(__('notifications.mail.reminder.intro', $this->details()))
        )
            ->action(__('notifications.mail.manage'), $this->details()['link'])
            ->salutation(__('notifications.mail.salutation', ['shop' => $this->shop()->name]));
    }
}

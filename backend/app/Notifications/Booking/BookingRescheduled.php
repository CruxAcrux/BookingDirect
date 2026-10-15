<?php

namespace App\Notifications\Booking;

use App\Enums\SmsType;
use App\Models\Customer;
use App\Notifications\Channels\OutgoingSms;
use Illuminate\Notifications\Messages\MailMessage;

class BookingRescheduled extends BookingConfirmed
{
    public function toSms(object $notifiable): OutgoingSms
    {
        return new OutgoingSms($this->shop(), __('notifications.sms.rescheduled', $this->details()), SmsType::BookingRescheduled, $this->booking);
    }

    public function toMail(Customer $notifiable): MailMessage
    {
        return $this->visitLines(
            $this->mail('notifications.mail.rescheduled.subject', $notifiable)
                ->line(__('notifications.mail.rescheduled.intro', $this->details()))
        )
            ->action(__('notifications.mail.manage'), $this->details()['link'])
            ->line(__('notifications.mail.calendar_hint'))
            ->salutation(__('notifications.mail.salutation', ['shop' => $this->shop()->name]))
            ->attachData($this->ics(), 'booking.ics', ['mime' => 'text/calendar; charset=utf-8; method=PUBLISH']);
    }
}

<?php

namespace App\Notifications\Booking;

use App\Booking\Calendar\IcsFile;
use App\Enums\SmsType;
use App\Models\Customer;
use App\Notifications\Channels\OutgoingSms;
use Illuminate\Notifications\Messages\MailMessage;

class BookingConfirmed extends BookingNotification
{
    public function shouldSend(Customer $notifiable, string $channel): bool
    {
        return $this->stillScheduled();
    }

    public function toSms(object $notifiable): OutgoingSms
    {
        return new OutgoingSms($this->shop(), __('notifications.sms.confirmed', $this->details()), SmsType::BookingConfirmed, $this->booking);
    }

    public function toMail(Customer $notifiable): MailMessage
    {
        return $this->visitLines(
            $this->mail('notifications.mail.confirmed.subject', $notifiable)
                ->line(__('notifications.mail.confirmed.intro', $this->details()))
        )
            ->action(__('notifications.mail.manage'), $this->details()['link'])
            ->line(__('notifications.mail.calendar_hint'))
            ->salutation(__('notifications.mail.salutation', ['shop' => $this->shop()->name]))
            ->attachData($this->ics(), 'booking.ics', ['mime' => 'text/calendar; charset=utf-8; method=PUBLISH']);
    }

    protected function ics(): string
    {
        $details = $this->details();

        return IcsFile::forBooking(
            $this->booking,
            __('notifications.ics.summary', $details),
            __('notifications.ics.description', $details),
            $this->shop()->address,
        );
    }
}

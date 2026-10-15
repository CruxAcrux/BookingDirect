<?php

namespace App\Notifications\Booking;

use App\Enums\CancelledBy;
use App\Enums\SmsType;
use App\Models\Customer;
use App\Notifications\Channels\OutgoingSms;
use Illuminate\Notifications\Messages\MailMessage;

class BookingCancelled extends BookingNotification
{
    public function toSms(object $notifiable): OutgoingSms
    {
        $body = __($this->byShop() ? 'notifications.sms.cancelled_by_shop' : 'notifications.sms.cancelled', [
            ...$this->details(),
            'link' => $this->shopUrl(),
        ]);

        if ($this->booking->cancellation_message !== null) {
            $body .= "\n\n".$this->booking->cancellation_message;
        }

        return new OutgoingSms($this->shop(), $body, SmsType::BookingCancelled, $this->booking);
    }

    public function toMail(Customer $notifiable): MailMessage
    {
        $mail = $this->mail('notifications.mail.cancelled.subject', $notifiable)
            ->line(__($this->byShop() ? 'notifications.mail.cancelled.intro_by_shop' : 'notifications.mail.cancelled.intro', $this->details()));

        if ($this->booking->cancellation_message !== null) {
            $mail->line('**'.__('notifications.mail.cancelled.message').':**')
                ->line($this->booking->cancellation_message);
        }

        return $mail->action(__('notifications.mail.book_again'), $this->shopUrl())
            ->salutation(__('notifications.mail.salutation', ['shop' => $this->shop()->name]));
    }

    private function byShop(): bool
    {
        return $this->booking->cancelled_by === CancelledBy::Staff;
    }
}

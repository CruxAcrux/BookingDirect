<?php

namespace App\Notifications\Booking;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Shop;
use App\Notifications\Channels\SendsSms;
use App\Notifications\Channels\SmsChannel;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Number;
use LogicException;

/**
 * Shared parts of the messages a customer gets about a booking: queued until the booking
 * is committed, sent by SMS and (when the customer has one) email, in the customer's
 * language with times in the shop's timezone.
 */
abstract class BookingNotification extends Notification implements SendsSms, ShouldQueueAfterCommit
{
    use Queueable;

    /** The start time when the message was queued, to spot bookings moved since. */
    protected readonly string $startsAt;

    public function __construct(public readonly Booking $booking)
    {
        $this->startsAt = $booking->starts_at->toIso8601String();
    }

    /**
     * @return list<string>
     */
    public function via(Customer $notifiable): array
    {
        return $notifiable->email === null ? [SmsChannel::class] : [SmsChannel::class, 'mail'];
    }

    /**
     * Messages about a time are pointless once that time is cancelled or has changed,
     * which can happen while they wait in the queue.
     */
    protected function stillScheduled(): bool
    {
        return $this->booking->status->blocksTime()
            && $this->booking->starts_at->toIso8601String() === $this->startsAt;
    }

    protected function shop(): Shop
    {
        return $this->booking->shop ?? throw new LogicException('A booking always belongs to a shop.');
    }

    /**
     * @return array<string, string>
     */
    protected function details(): array
    {
        return [
            'shop' => $this->shop()->name,
            'service' => $this->booking->service->name,
            'barber' => $this->booking->barber->display_name,
            'time' => $this->localTime()->isoFormat(__('notifications.datetime_format')),
            'clock' => $this->localTime()->format('H:i'),
            'link' => $this->booking->manageUrl() ?? $this->shopUrl(),
        ];
    }

    protected function localTime(): CarbonInterface
    {
        $time = $this->booking->starts_at->copy()->setTimezone($this->shop()->timezone);
        $time->locale(app()->getLocale());

        return $time;
    }

    protected function shopUrl(): string
    {
        return rtrim(config()->string('app.frontend_url'), '/').'/s/'.$this->shop()->slug;
    }

    /** A mail sent in the shop's name, with replies going to the shop. */
    protected function mail(string $subjectKey, Customer $notifiable): MailMessage
    {
        $shop = $this->shop();
        $mail = (new MailMessage)
            ->from(config()->string('mail.from.address'), $shop->name)
            ->subject(__($subjectKey, $this->details()))
            ->greeting($notifiable->name === null
                ? __('notifications.mail.greeting_anonymous')
                : __('notifications.mail.greeting', ['name' => $notifiable->name]));

        if ($shop->email !== null) {
            $mail->replyTo($shop->email, $shop->name);
        }

        return $mail;
    }

    /** Visit details as Markdown lines. */
    protected function visitLines(MailMessage $mail): MailMessage
    {
        $details = $this->details();
        $shop = $this->shop();

        $mail->line('**'.__('notifications.mail.when').':** '.$details['time'])
            ->line('**'.__('notifications.mail.what').':** '.$details['service'].' · '.Number::currency($this->booking->price_ore / 100, 'SEK', app()->getLocale(), 0))
            ->line('**'.__('notifications.mail.barber').':** '.$details['barber']);

        if ($shop->address !== null) {
            $mail->line('**'.__('notifications.mail.where').':** '.$shop->address);
        }

        return $mail;
    }
}

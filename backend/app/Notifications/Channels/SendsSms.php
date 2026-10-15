<?php

namespace App\Notifications\Channels;

interface SendsSms
{
    public function toSms(object $notifiable): OutgoingSms;
}

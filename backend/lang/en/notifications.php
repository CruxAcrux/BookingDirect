<?php

return [
    'datetime_format' => 'dddd D MMMM [at] HH:mm',

    'sms' => [
        'confirmed' => "Booked at :shop: :service with :barber, :time.\nChange or cancel: :link",
        'rescheduled' => "Your booking at :shop has moved to :time (:service with :barber).\nChange or cancel: :link",
        'cancelled' => "Your booking at :shop on :time is cancelled.\nBook a new time: :link",
        'cancelled_by_shop' => ":shop has cancelled your booking on :time. Sorry about that!\nBook a new time: :link",
        'reminder_24h' => "Reminder: :service with :barber at :shop, :time.\nChange or cancel: :link",
        'reminder_2h' => 'See you soon! :service with :barber at :shop today at :clock.',
    ],

    'mail' => [
        'greeting' => 'Hi :name,',
        'greeting_anonymous' => 'Hi,',
        'salutation' => "See you soon,  \n:shop",
        'when' => 'When',
        'what' => 'Service',
        'barber' => 'Barber',
        'where' => 'Where',
        'manage' => 'Change or cancel',
        'book_again' => 'Book a new time',
        'calendar_hint' => 'The attached file adds the visit to your calendar.',
        'confirmed' => [
            'subject' => 'Booking confirmed: :shop, :time',
            'intro' => 'Thanks for booking with :shop. Here are the details:',
        ],
        'rescheduled' => [
            'subject' => 'New time: :shop, :time',
            'intro' => 'Your booking at :shop has moved. This is the new time:',
        ],
        'cancelled' => [
            'subject' => 'Booking cancelled: :shop, :time',
            'intro' => 'Your booking at :shop on :time is cancelled.',
            'intro_by_shop' => ':shop has cancelled your booking on :time. Sorry for the trouble.',
            'message' => 'Message from the shop',
        ],
        'reminder' => [
            'subject' => 'Reminder: :shop, :time',
            'intro' => 'A reminder of your visit to :shop:',
        ],
    ],

    'ics' => [
        'summary' => ':service at :shop',
        'description' => ':service with :barber. Change or cancel: :link',
    ],
];

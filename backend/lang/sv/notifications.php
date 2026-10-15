<?php

return [
    'datetime_format' => 'dddd D MMMM [kl.] HH:mm',

    'sms' => [
        'confirmed' => "Bokat hos :shop: :service med :barber, :time.\nÄndra eller avboka: :link",
        'rescheduled' => "Din tid hos :shop är flyttad till :time (:service med :barber).\nÄndra eller avboka: :link",
        'cancelled' => "Din tid hos :shop :time är avbokad.\nBoka en ny tid: :link",
        'cancelled_by_shop' => ":shop har tyvärr avbokat din tid :time.\nBoka en ny tid: :link",
        'reminder_24h' => "Påminnelse: :service med :barber hos :shop, :time.\nÄndra eller avboka: :link",
        'reminder_2h' => 'Snart dags! :service med :barber hos :shop i dag kl. :clock.',
    ],

    'mail' => [
        'greeting' => 'Hej :name!',
        'greeting_anonymous' => 'Hej!',
        'salutation' => "Vi ses,  \n:shop",
        'when' => 'När',
        'what' => 'Tjänst',
        'barber' => 'Barberare',
        'where' => 'Var',
        'manage' => 'Ändra eller avboka',
        'book_again' => 'Boka en ny tid',
        'calendar_hint' => 'Med den bifogade filen lägger du in besöket i din kalender.',
        'confirmed' => [
            'subject' => 'Bokning bekräftad: :shop, :time',
            'intro' => 'Tack för din bokning hos :shop. Här är detaljerna:',
        ],
        'rescheduled' => [
            'subject' => 'Ny tid: :shop, :time',
            'intro' => 'Din bokning hos :shop är flyttad. Det här är den nya tiden:',
        ],
        'cancelled' => [
            'subject' => 'Bokning avbokad: :shop, :time',
            'intro' => 'Din tid hos :shop :time är avbokad.',
            'intro_by_shop' => ':shop har tyvärr avbokat din tid :time. Vi ber om ursäkt för besväret.',
            'message' => 'Meddelande från salongen',
        ],
        'reminder' => [
            'subject' => 'Påminnelse: :shop, :time',
            'intro' => 'En påminnelse om ditt besök hos :shop:',
        ],
    ],

    'ics' => [
        'summary' => ':service hos :shop',
        'description' => ':service med :barber. Ändra eller avboka: :link',
    ],
];

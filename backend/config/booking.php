<?php

return [

    /*
    | While no real SMS provider is configured, demo mode returns the one-time sign-in code
    | in the API response so the booking flow can be tried end to end. Never enable this
    | in production with a real SMS provider.
    */
    'otp_demo_mode' => (bool) env('OTP_DEMO_MODE', false),

];

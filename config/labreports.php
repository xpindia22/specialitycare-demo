<?php

return [

    /*
     | WhatsApp numbers allowed to send in lab-report PDFs (inbound via Gupshup).
     | Digits only, no '+' or spaces — country code included, e.g. 919812345678.
     | Set LAB_REPORT_SENDERS in .env as a comma-separated list, or edit here.
     | Anything from a number not on this list is ignored.
    */
    'allowed_senders' => array_values(array_filter(array_map(
        fn ($n) => preg_replace('/\D/', '', $n),
        explode(',', (string) env('LAB_REPORT_SENDERS', ''))
    ))),

    /*
     | Shared secret the Gupshup webhook must present (as ?token= or X-Hub-Token
     | header) so random callers can't post to the endpoint. Set LAB_REPORT_WEBHOOK_SECRET.
    */
    'webhook_secret' => env('LAB_REPORT_WEBHOOK_SECRET', ''),

];

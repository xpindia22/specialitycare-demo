<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hidden owner / admin emails
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of email addresses that are treated as hidden
    | owner accounts: they get full access but are filtered OUT of the
    | Users, Doctors and Staff listing screens so they never appear to
    | buyers or demo viewers. Set OWNER_HIDDEN_EMAILS in .env per install
    | (NOT committed) so no real owner identity ships in the codebase.
    |
    */
    'hidden_emails' => array_values(array_filter(array_map(
        fn ($e) => strtolower(trim($e)),
        explode(',', (string) env('OWNER_HIDDEN_EMAILS', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Login activity logging
    |--------------------------------------------------------------------------
    | Capture IP + country for every login/failed attempt. The geolocation
    | lookup is best-effort and only runs for non-private IPs.
    */
    'log_logins'    => filter_var(env('LOGIN_ACTIVITY_LOG', true), FILTER_VALIDATE_BOOLEAN),
    'geo_lookup'    => filter_var(env('LOGIN_ACTIVITY_GEO', true), FILTER_VALIDATE_BOOLEAN),
];

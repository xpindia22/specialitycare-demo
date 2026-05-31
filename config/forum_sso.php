<?php

/*
|--------------------------------------------------------------------------
| phpBB SSO bridge
|--------------------------------------------------------------------------
|
| The phpBB forum installed at public/forum shares this app's database
| (phpbb_ prefix) and is logged into automatically when a specialitycare
| user is authenticated. Mechanism: this app sets a short-lived,
| HMAC-signed cookie that the phpBB SSO auth extension verifies with the
| SAME secret. The cookie is excluded from Laravel's cookie encryption
| (see bootstrap/app.php) so phpBB can read it raw.
|
| The secret MUST match the value stored in the phpBB extension's config
| (config key: sc_sso_secret).
|
*/

return [
    'secret'  => env('FORUM_SSO_SECRET', ''),
    'cookie'  => env('FORUM_SSO_COOKIE', 'sc_forum_sso'),
    // https on nvme; http on home dev.
    'secure'  => filter_var(env('FORUM_SSO_SECURE', false), FILTER_VALIDATE_BOOLEAN),
    // Seconds the signed payload stays valid; refreshed on every request.
    'ttl'     => 7200,
];

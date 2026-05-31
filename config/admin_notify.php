<?php

return [
    'email'   => env('ADMIN_NOTIFY_EMAIL'),
    'enabled' => filter_var(env('ADMIN_NOTIFY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
];

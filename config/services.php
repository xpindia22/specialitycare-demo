<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid'             => env('TWILIO_SID'),
        'token'           => env('TWILIO_TOKEN'),
        'whatsapp_from'   => env('TWILIO_WHATSAPP_FROM'),       // e.g. whatsapp:+14155238886 (sandbox)
        'default_country' => env('TWILIO_DEFAULT_COUNTRY', '91'), // for normalising local 10-digit numbers
        'sandbox'         => env('TWILIO_SANDBOX', true),         // shows sandbox join-code hint in admin UI
        // Hard daily ceiling on outbound WhatsApp messages (across all kinds).
        // Once hit, send() returns ok=false skipped='daily_limit_reached'.
        // Belt-and-suspenders against runaway scripts on big patient bases.
        'daily_limit'     => (int) env('TWILIO_DAILY_LIMIT', 500),
        // Pre-fills the recipient field on the admin test-send page so
        // operators don't have to retype their own number every time.
        'test_recipient'  => env('TWILIO_TEST_RECIPIENT'),
        // Clinic owner's WhatsApp number — receives a short alert copy when
        // a patient gets a notification (registration, appointment, etc.).
        // Falls back to TWILIO_TEST_RECIPIENT if unset, since on the live
        // server that field already holds the owner's phone.
        'owner_phone'     => env('OWNER_WHATSAPP_PHONE') ?: env('TWILIO_TEST_RECIPIENT'),
    ],

    // Provider-agnostic WhatsApp settings. The single WhatsAppService reads
    // these; the per-provider connection details live in 'twilio' (above) and
    // 'whatsapp.meta' (below). Flip 'provider' to switch backends — no code
    // change needed. Default stays 'twilio' so the existing sandbox keeps
    // working until Meta is fully set up.
    'whatsapp' => [
        'provider'        => env('WHATSAPP_PROVIDER', 'twilio'),    // 'twilio' | 'meta' | 'gupshup'
        'default_country' => env('WHATSAPP_DEFAULT_COUNTRY', env('TWILIO_DEFAULT_COUNTRY', '91')),
        'daily_limit'     => (int) env('WHATSAPP_DAILY_LIMIT', env('TWILIO_DAILY_LIMIT', 500)),
        'owner_phone'     => env('OWNER_WHATSAPP_PHONE') ?: env('TWILIO_TEST_RECIPIENT'),
        'test_recipient'  => env('TWILIO_TEST_RECIPIENT'),

        // Meta WhatsApp Cloud API (graph.facebook.com). Used when provider=meta.
        // Get these from Meta Business Manager → WhatsApp → API Setup. The token
        // must be a *permanent* System-User token, not the 24h temporary one.
        'meta' => [
            'token'           => env('META_WA_TOKEN'),
            'phone_number_id' => env('META_WA_PHONE_NUMBER_ID'),    // numeric sender ID
            'waba_id'         => env('META_WA_WABA_ID'),            // WhatsApp Business Account ID
            'api_version'     => env('META_WA_API_VERSION', 'v21.0'),
            'lang'            => env('META_WA_TEMPLATE_LANG', 'en'), // default template language code
        ],

        // Internal kind => the template *name* you registered & got approved in
        // Meta. Business-initiated messages (reminders etc.) MUST use a template.
        // Edit the env vars to match the exact names Meta approves.
        'templates' => [
            'appointment_reminder' => env('META_WA_TPL_APPT_REMINDER', 'appointment_reminder'),
            'appointment_confirm'  => env('META_WA_TPL_APPT_CONFIRM',  'appointment_confirm'),
            'followup'             => env('META_WA_TPL_FOLLOWUP',      'followup_reminder'),
            'vaccination'          => env('META_WA_TPL_VACCINATION',   'vaccination_due'),
            'billing'              => env('META_WA_TPL_BILLING',       'bill_overdue'),
            'patient_welcome'      => env('META_WA_TPL_WELCOME',       'patient_welcome'),
            // Bill lifecycle templates added 2026-05-25 (PENDING Meta review).
            'bill_created'         => env('META_WA_TPL_BILL_CREATED'),
            'bill_paid'            => env('META_WA_TPL_BILL_PAID'),
            'owner_bill_created'   => env('META_WA_TPL_OWNER_BILL_CREATED'),
            'owner_bill_paid'      => env('META_WA_TPL_OWNER_BILL_PAID'),
            // Owner appointment alert added 2026-05-29 (PENDING Meta review):
            'owner_appointment_created' => env('META_WA_TPL_OWNER_APPT_CREATED'),
            // Teleconsult link delivery (PENDING Meta review):
            'teleconsult_link'     => env('META_WA_TPL_TELECONSULT_LINK'),
        ],

        // Gupshup BSP (https://api.gupshup.io). Used when provider=gupshup.
        // Get these from the Gupshup dashboard once your WhatsApp app is live.
        // 'source' is your registered WhatsApp number (digits only, with country
        // code, no '+'); 'app_name' is the Gupshup app name sent as src.name.
        'gupshup' => [
            'api_key'  => env('GUPSHUP_API_KEY'),
            'source'   => env('GUPSHUP_SOURCE'),
            'app_name' => env('GUPSHUP_APP_NAME'),
            // Internal kind => Gupshup *template ID* (a UUID from the dashboard,
            // NOT the template name). Required for business-initiated sends.
            'templates' => [
                'appointment_reminder' => env('GUPSHUP_TPL_APPT_REMINDER'),
                'appointment_confirm'  => env('GUPSHUP_TPL_APPT_CONFIRM'),
                'followup'             => env('GUPSHUP_TPL_FOLLOWUP'),
                'vaccination'          => env('GUPSHUP_TPL_VACCINATION'),
                'billing'              => env('GUPSHUP_TPL_BILLING'),
                'patient_welcome'      => env('GUPSHUP_TPL_WELCOME'),
                // Growth/clinical templates added 2026-05-23 (PENDING Meta review):
                'review_request'       => env('GUPSHUP_TPL_REVIEW'),
                'birthday_greeting'    => env('GUPSHUP_TPL_BIRTHDAY'),
                'medication_reminder'  => env('GUPSHUP_TPL_MEDREMINDER'),
                'refill_reminder'      => env('GUPSHUP_TPL_REFILL'),
                // Bill lifecycle templates added 2026-05-25 (PENDING Gupshup/Meta approval):
                'bill_created'         => env('GUPSHUP_TPL_BILL_CREATED'),
                'bill_paid'            => env('GUPSHUP_TPL_BILL_PAID'),
                'owner_bill_created'   => env('GUPSHUP_TPL_OWNER_BILL_CREATED'),
                'owner_bill_paid'      => env('GUPSHUP_TPL_OWNER_BILL_PAID'),
                // Owner appointment alert added 2026-05-29 (PENDING Gupshup/Meta approval):
                'owner_appointment_created' => env('GUPSHUP_TPL_OWNER_APPT_CREATED'),
                // Teleconsult link (PENDING approval 2026-05-26):
                'teleconsult_link'     => env('GUPSHUP_TPL_TELECONSULT_LINK'),
            ],
        ],
    ],

];

<?php

/*
|--------------------------------------------------------------------------
| SpecialityCare Modules
|--------------------------------------------------------------------------
|
| Each big feature in the product is a module. Modules are gated by a
| signed license file at storage/license/license.{json,sig}. The license
| is signed with an ed25519 private key that ONLY the software owner
| holds; the deployed app verifies signatures with the public key below.
|
| A buyer with shell / DB access cannot enable a disabled module without
| a valid signature, because they don't have the private signing key.
|
| To rotate the keypair, run `php artisan modules:keygen` LOCALLY, copy
| the printed public key into `public_key` below, commit, and re-issue
| signed licenses for every deployment.
|
*/

return [

    // Base64-encoded ed25519 public key. Generate with `php artisan modules:keygen`.
    // Keep the *private* key OFF this server and out of the repo.
    'public_key' => env('MODULES_PUBLIC_KEY', ''),

    // Where the signed license lives on the server. Buyers can read these
    // files; they cannot forge a new signed pair without the private key.
    'license_path' => storage_path('license/license.json'),
    'signature_path' => storage_path('license/license.sig'),

    // Catalog of modules the product offers. Add new entries here when you
    // build a new module. The `enabled_by_default` flag is ONLY honored when
    // no license file exists at all (fresh install / dev).
    'catalog' => [
        'hr' => [
            'label'       => 'HR & Payroll',
            'description' => 'Staff, attendance, duty roster, pay adjustments, monthly payroll, salary slips.',
            'icon'        => 'fa-user-tie',
            'enabled_by_default' => false,
        ],
        'opd' => [
            'label'       => 'OPD',
            'description' => 'Outpatient consultations, queue, encounters, prescriptions.',
            'icon'        => 'fa-stethoscope',
            'enabled_by_default' => false,
        ],
        'ipd' => [
            'label'       => 'IPD',
            'description' => 'Inpatient admissions, beds, ward rounds, discharge summaries.',
            'icon'        => 'fa-bed-pulse',
            'enabled_by_default' => false,
        ],
        'pharmacy' => [
            'label'       => 'Pharmacy',
            'description' => 'Inventory, dispensing, stock alerts, vendor management.',
            'icon'        => 'fa-pills',
            'enabled_by_default' => false,
        ],
        'lab' => [
            'label'       => 'Lab',
            'description' => 'Test orders, panels, results capture, report PDFs.',
            'icon'        => 'fa-flask-vial',
            'enabled_by_default' => false,
        ],
        'billing' => [
            'label'       => 'Billing & Finance',
            'description' => 'Bills, invoices, payments, refunds, day-end totals.',
            'icon'        => 'fa-file-invoice-dollar',
            'enabled_by_default' => false,
        ],
        'notifications' => [
            'label'       => 'Notifications (WhatsApp/SMS)',
            'description' => 'Patient WhatsApp notifications via Twilio: appointment confirmations, day-before reminders, follow-ups, vaccinations due, billing reminders, and ad-hoc messages.',
            'icon'        => 'fa-bell',
            'enabled_by_default' => false,
        ],
        'accounts' => [
            'label'       => 'Accounts & GST',
            'description' => 'Tally-style double-entry bookkeeping: chart of accounts, vouchers (Payment/Receipt/Journal/Sales/Purchase/Contra), GST (CGST/SGST/IGST) on purchases and sales, expense tracking (rent/electricity/salary/petrol/cleaning), recurring expenses, Trial Balance, P&L, Balance Sheet, GSTR-1/3B summary, HSN-wise summary.',
            'icon'        => 'fa-book',
            'enabled_by_default' => false,
        ],
        'forum' => [
            'label'       => 'Forum & Messaging',
            'description' => 'Internal phpBB-style discussion forum (categories → forums → topics → posts) with per-forum role permissions for view/post/reply/moderate, 1-to-1 and group private messaging between staff, and patient-facing public announcements (topics flagged public). Email + WhatsApp fan-out on new DMs and subscribed-topic replies.',
            'icon'        => 'fa-comments',
            'enabled_by_default' => false,
        ],
    ],

    // Owner panel access. Set OWNER_KEY in .env to a long random string.
    // The panel exists only to view license state and (if a private key
    // file is present locally) sign a new license. Without OWNER_KEY set,
    // the panel returns 404.
    'owner_key' => env('OWNER_KEY'),

    // Optional path to the private signing key on the OWNER's machine.
    // If this file exists, the owner panel can sign new licenses directly.
    // On buyer deployments this file should NOT exist.
    'private_key_path' => env('MODULES_PRIVATE_KEY_PATH', storage_path('license/private.key')),

    // Heartbeat — installs phone home so the owner can detect cloning AND
    // so revocations propagate back. Unset on the owner's central server
    // (it would be heartbeating itself); set on every buyer install to
    // point at the owner's receiver endpoint. When unset, the revocation
    // and dead-man enforcement below also disable themselves — the owner
    // server runs unchecked.
    'heartbeat_url'      => env('MODULES_HEARTBEAT_URL'),
    // Throttle for HeartbeatService::sendIfDue. Set just under the cron
    // schedule (hourly) so each scheduled run actually sends.
    'heartbeat_interval' => env('MODULES_HEARTBEAT_INTERVAL', 3300), // seconds (55 min)
    'install_id_path'    => storage_path('license/install_id'),

    // Receiver-reported status cache: written by HeartbeatService after
    // every successful POST, read by LicenseService to honor remote
    // revocation/expiry. JSON shape: { license_id, status, checked_at }.
    'revocation_status_path' => storage_path('license/revocation_status'),
    // Last heartbeat timestamp file (UNIX seconds as text). Also used as
    // the dead-man clock — see heartbeat_grace_seconds below.
    'last_heartbeat_path' => storage_path('license/last_heartbeat'),
    // Dead-man switch: if MODULES_HEARTBEAT_URL is set and the install
    // hasn't successfully heartbeated for this many seconds, modules
    // disable themselves. Closes the "buyer blocks the receiver to evade
    // revocation" hole. Default 1 hour. Disabled when no URL is set.
    //
    // This is only the FALLBACK — the install prefers the per-license
    // `heartbeat_grace_seconds` value baked into the signed payload by
    // the owner panel. The owner sets it per-buyer (or per-license) so
    // a single config default rarely needs touching.
    'heartbeat_grace_seconds' => env('MODULES_HEARTBEAT_GRACE', 3600), // 1 hour

    // Buyer-managed config file. Lets non-technical buyers on shared
    // hosting set MODULES_PUBLIC_KEY and MODULES_HEARTBEAT_URL through
    // the admin UI without editing .env. AppServiceProvider::boot reads
    // this and overlays its values onto runtime config ONLY when the
    // matching .env value is empty — so .env always wins on installs
    // (nvme, home dev) that already set the env vars directly.
    'buyer_config_path' => storage_path('license/buyer_config.json'),
];

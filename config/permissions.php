<?php

/*
|--------------------------------------------------------------------------
| Permissions catalog
|--------------------------------------------------------------------------
|
| Every grantable thing in the app, grouped by section. Each role gets a
| subset of these via the `permissions` JSON column on `roles`. A user
| inherits the union of permissions across every role they're assigned.
|
| Convention: `<section>.<verb_or_resource>`. Use lowercase, snake_case.
|
| To add a new permission:
|   1. Add it here under the right section
|   2. Reference it in routes via `permission:<key>` middleware
|   3. Reference it in views via `@if($u->can('<key>'))`
|   4. (Optional) Tick it on the relevant roles via the admin UI
|
*/

return [

    'patients' => [
        'label' => 'Patients',
        'icon'  => 'fa-user-injured',
        'perms' => [
            'patients.view'   => 'View patient list',
            'patients.create' => 'Register new patient',
            'patients.edit'   => 'Edit patient details',
        ],
    ],

    'opd' => [
        'label' => 'OPD',
        'icon'  => 'fa-stethoscope',
        'perms' => [
            'opd.queue'        => 'Manage OPD queue',
            'opd.appointments' => 'View / manage appointments',
            'opd.encounter'    => 'Create encounter / consultation',
            'opd.prescription' => 'Write prescriptions',
            'opd.vaccinations' => 'Record vaccinations',
        ],
    ],

    'ipd' => [
        'label' => 'IPD',
        'icon'  => 'fa-bed-pulse',
        'perms' => [
            'ipd.admit'     => 'Admit patient',
            'ipd.beds'      => 'Manage beds / room charges',
            'ipd.discharge' => 'Discharge patient',
        ],
    ],

    'pharmacy' => [
        'label' => 'Pharmacy',
        'icon'  => 'fa-pills',
        'perms' => [
            'pharmacy.dispense' => 'Dispense medicines',
            'pharmacy.stock'    => 'Manage stock / inventory',
        ],
    ],

    'lab' => [
        'label' => 'Lab',
        'icon'  => 'fa-flask-vial',
        'perms' => [
            'lab.orders'  => 'Take lab orders',
            'lab.results' => 'Enter / verify results',
        ],
    ],

    'billing' => [
        'label' => 'Billing',
        'icon'  => 'fa-file-invoice',
        'perms' => [
            'billing.create' => 'Create bills / invoices',
            'billing.refund' => 'Process refunds',
        ],
    ],

    'reports' => [
        'label' => 'Reports',
        'icon'  => 'fa-chart-line',
        'perms' => [
            'reports.view' => 'View reports',
        ],
    ],

    'hr' => [
        'label' => 'HR & Payroll',
        'icon'  => 'fa-user-tie',
        'perms' => [
            'hr.attendance'  => 'Mark / view attendance',
            'hr.adjustments' => 'Pay adjustments',
            'hr.payroll'     => 'View / process payroll',
        ],
    ],

    'misc' => [
        'label' => 'Misc',
        'icon'  => 'fa-folder',
        'perms' => [
            'certificates.manage' => 'Manage certificates',
        ],
    ],

    'forum' => [
        'label' => 'Forum & Messaging',
        'icon'  => 'fa-comments',
        'perms' => [
            'forum.view'      => 'View forums (subject to per-forum role list)',
            'forum.post'      => 'Start new topics (subject to per-forum role list)',
            'forum.reply'     => 'Reply to topics (subject to per-forum role list)',
            'forum.moderate'  => 'Edit/delete others\' posts, lock/pin/move topics',
            'forum.admin'     => 'Create / edit categories and forums; assign role lists',
            'messages.send'   => 'Send 1-to-1 private messages',
            'messages.group'  => 'Create group message threads',
        ],
    ],

    'admin' => [
        'label' => 'Administration',
        'icon'  => 'fa-shield-halved',
        'perms' => [
            'admin.section'     => 'See Administration menu (gate)',
            'admin.users'       => 'Manage users',
            'admin.staff'       => 'Manage staff records',
            'admin.doctors'     => 'Manage doctors',
            'admin.duty_roster' => 'Manage duty roster',
            'admin.settings'    => 'Edit settings',
            'admin.logs'        => 'View activity logs',
            'admin.backup'      => 'Backup & export',
            'admin.import'      => 'Import data',
            'admin.referrals'   => 'Manage referrals',
            'admin.modules'     => 'View modules status',
            'admin.licensing'   => 'Owner panel — license management',
            'admin.roles'       => 'Manage roles & permissions',
            'admin.clinical_chips' => 'Manage clinical quick-pick chips',
            'admin.notifications'  => 'Manage WhatsApp / SMS notifications',
        ],
    ],
];

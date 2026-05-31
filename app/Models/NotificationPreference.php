<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per domain event. Owner-controlled matrix of who (patient/admin)
 * gets notified on which channel (email/WhatsApp). Read by
 * NotificationPreferenceService::shouldSend() before any observer or
 * notifier fires; edited from /admin/notifications/preferences.
 */
class NotificationPreference extends Model
{
    protected $fillable = [
        'event_key','event_label','group_label','is_enabled',
        'patient_email','patient_whatsapp','admin_email','admin_whatsapp','note',
    ];

    protected $casts = [
        'is_enabled'       => 'bool',
        'patient_email'    => 'bool',
        'patient_whatsapp' => 'bool',
        'admin_email'      => 'bool',
        'admin_whatsapp'   => 'bool',
    ];
}

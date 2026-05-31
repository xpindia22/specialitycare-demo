<?php

namespace App\Services;

use App\Models\NotificationPreference;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for "should we notify on this event?". Every
 * observer / sender consults shouldSend($eventKey, $recipient, $channel)
 * before calling AdminNotifier or WhatsAppService.
 *
 * Preferences are cached for 60s — flipping a checkbox in the admin UI
 * takes effect within a minute without us having to plumb cache
 * invalidation through the controller.
 */
class NotificationPreferenceService
{
    private const CACHE_KEY = 'notif_prefs:v1';
    private const TTL       = 60;

    /**
     * @param string $eventKey  e.g. 'appointment_created'
     * @param string $recipient 'patient' | 'admin'
     * @param string $channel   'email' | 'whatsapp'
     */
    public function shouldSend(string $eventKey, string $recipient, string $channel): bool
    {
        $prefs = $this->all();
        $pref  = $prefs[$eventKey] ?? null;
        if (!$pref || !$pref['is_enabled']) return false;

        $col = $recipient . '_' . $channel;       // patient_email | patient_whatsapp | admin_email | admin_whatsapp
        return (bool) ($pref[$col] ?? false);
    }

    /** Return all preferences keyed by event_key (cached). */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, function () {
            return NotificationPreference::all()->keyBy('event_key')->map(function ($p) {
                return [
                    'is_enabled'       => (bool) $p->is_enabled,
                    'patient_email'    => (bool) $p->patient_email,
                    'patient_whatsapp' => (bool) $p->patient_whatsapp,
                    'admin_email'      => (bool) $p->admin_email,
                    'admin_whatsapp'   => (bool) $p->admin_whatsapp,
                ];
            })->toArray();
        });
    }

    /** Drop the cache — called by the admin controller after a save. */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

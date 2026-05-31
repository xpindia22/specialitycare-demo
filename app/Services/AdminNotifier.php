<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a short admin-facing email when significant domain events happen
 * (visits, prescriptions, bills, etc.). Configured via env:
 *   ADMIN_NOTIFY_EMAIL=admin@demo.local
 *   ADMIN_NOTIFY_ENABLED=true
 *
 * - Skips when running under artisan / seeders
 * - Wraps Mail::raw in try/catch so a mail failure never breaks the save
 * - 60s per-key debounce in cache to avoid storms (e.g. 3 saves in a row)
 * - Mail::raw is dispatched via dispatch()->afterResponse() so the HTTP
 *   request returns to the user BEFORE we wait on SMTP. Same trick that
 *   fixed BTLP-style slowness — every CRUD save used to block ~1s on the
 *   Gmail SMTP handshake; now it doesn't.
 */
class AdminNotifier
{
    public static function notify(string $subject, string $body, ?string $dedupeKey = null): void
    {
        // Read from config (config files are loaded once at boot — works in both
        // CLI and web workers, and survives php artisan config:cache).
        $to            = config('admin_notify.email');
        $enabled       = config('admin_notify.enabled', true);
        $forceConsole  = (bool) env('ADMIN_NOTIFY_FORCE_CONSOLE', false);
        $debug         = (bool) env('ADMIN_NOTIFY_DEBUG', false);

        $skipReason = null;
        if (!$to)                                              $skipReason = 'no recipient configured (admin_notify.email empty)';
        elseif (!$enabled)                                     $skipReason = 'disabled by admin_notify.enabled=false';
        elseif (app()->runningInConsole() && !$forceConsole)   $skipReason = 'running in console';

        if ($skipReason) {
            if ($debug) Log::info("AdminNotifier skipped: {$skipReason} | subject={$subject}");
            return;
        }

        // Per-key 60s debounce — avoids 3 emails when a doctor hits Save 3 times.
        if ($dedupeKey) {
            $ck = 'adm_notify:' . md5($dedupeKey);
            if (Cache::has($ck)) {
                if ($debug) Log::info("AdminNotifier debounced: key={$dedupeKey} | subject={$subject}");
                return;
            }
            Cache::put($ck, 1, 60);
        }

        // Queue the SMTP send via the configured QUEUE_CONNECTION. On nvme this
        // is `database` with a `queue:work` daemon, so dispatch() inserts a row
        // into `jobs` (sub-ms) and the request returns immediately; the worker
        // delivers the email out-of-band. On home (QUEUE_CONNECTION=sync) it
        // still runs inline — acceptable for dev. The previous `->afterResponse()`
        // approach kept the FPM worker busy for several seconds after the
        // response, which is why saves felt slow once Gupshup + SMTP were both live.
        //
        // The closure captures only scalars, so it serialises cleanly to the
        // database queue (no $this, no service objects).
        try {
            dispatch(function () use ($to, $subject, $body, $debug) {
                try {
                    Mail::raw($body, function ($m) use ($to, $subject) {
                        $m->to($to)->subject('[SC] ' . $subject);
                    });
                    if ($debug) Log::info("AdminNotifier sent: to={$to} subject={$subject}");
                } catch (\Throwable $e) {
                    Log::warning("AdminNotifier mail failed: " . $e->getMessage() . " | subject={$subject}");
                }
            });
        } catch (\Throwable $e) {
            // dispatch() itself shouldn't fail, but if it does, we still
            // don't want to break the save that triggered us.
            Log::warning("AdminNotifier dispatch failed: " . $e->getMessage());
        }
    }

    /**
     * Short single-line actor descriptor: "Sample Admin (admin, admin@demo.local)".
     * Falls back to "System" for console/unauth contexts (cron, seeders, etc.).
     * Use actorContext() when you also want the IP / user-agent block.
     */
    public static function actor(): string
    {
        $u = auth()->user();
        if (!$u) return 'System';
        $bits = array_filter([$u->role ?? null, $u->email ?? null]);
        return $bits ? "{$u->name} (" . implode(', ', $bits) . ")" : (string) $u->name;
    }

    /**
     * Multi-line actor block — name + role + email + IP + user-agent + timestamp.
     * Returns "" lines for missing pieces so callers can drop it straight into fmt().
     * IP/UA only populate in web requests; console-fired observers get just "By: System".
     */
    public static function actorContext(): array
    {
        $lines = ['By: ' . self::actor()];
        try {
            $req = request();
            if ($req && !app()->runningInConsole()) {
                if ($ip = $req->ip())          $lines[] = "IP: {$ip}";
                if ($ua = $req->userAgent())   $lines[] = "Agent: " . substr($ua, 0, 160);
            }
        } catch (\Throwable $e) {
            // request() is unavailable in some boot contexts — skip silently.
        }
        $lines[] = "When: " . now()->format('d M Y H:i');
        return $lines;
    }

    /**
     * Build a "field: old → new" diff block from a freshly-saved Eloquent model.
     * Designed for use inside an observer's updated() handler, where
     * getChanges() returns post-save attributes and getOriginal() still
     * holds pre-save values.
     *
     * - $skip drops noisy / non-meaningful columns (timestamps by default).
     * - Long values are truncated so emails stay readable.
     * - Returns "" when nothing meaningful changed, so callers can include
     *   the result in fmt() and the line just disappears.
     */
    public static function diff($model, array $skip = ['updated_at', 'created_at']): string
    {
        if (!is_object($model) || !method_exists($model, 'getChanges')) return '';
        $changes = $model->getChanges();
        foreach ($skip as $k) unset($changes[$k]);
        if (!$changes) return '';

        $rows = [];
        foreach ($changes as $field => $new) {
            $old = $model->getOriginal($field);
            $rows[] = "  • {$field}: " . self::fmtValue($old) . " → " . self::fmtValue($new);
            if (count($rows) >= 25) {                        // hard cap on email size
                $rows[] = '  • … (' . (count($changes) - 25) . ' more)';
                break;
            }
        }
        return "Edits:\n" . implode("\n", $rows);
    }

    private static function fmtValue($v): string
    {
        if ($v === null || $v === '') return '∅';
        if (is_bool($v))   return $v ? 'true' : 'false';
        if ($v instanceof \DateTimeInterface) return $v->format('Y-m-d H:i');
        if (is_array($v) || is_object($v))    $v = json_encode($v);
        $s = (string) $v;
        // Strip HTML so rich-text fields (chief_complaint, plan…) don't dump tags.
        $s = trim(preg_replace('/\s+/', ' ', strip_tags($s)));
        return strlen($s) > 80 ? substr($s, 0, 77) . '...' : $s;
    }

    public static function fmt(array $lines): string
    {
        return implode("\n", array_filter($lines, fn ($l) => $l !== null && $l !== ''));
    }
}

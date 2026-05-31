<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Loads, verifies, and exposes the signed module license.
 *
 * The license is two files in storage/license/ :
 *   - license.json : plaintext payload { licensee, issued_at, expires_at, modules: [...] }
 *   - license.sig  : base64-encoded ed25519 signature of the json bytes
 *
 * Verification uses the public key from config('modules.public_key'). If
 * verification fails for ANY reason (missing files, bad signature, expired,
 * malformed json, missing public key), enabledModules() returns [] — every
 * gated module then 404s. Default-on flags in the catalog only apply when
 * NO license file exists at all (fresh install / dev convenience).
 */
class LicenseService
{
    /** Per-request cache of the resolved enabled list. */
    private ?array $enabledCache = null;
    /** Per-request cache of the parsed payload (or null). */
    private array|false|null $payloadCache = null;

    public function enabledModules(): array
    {
        if ($this->enabledCache !== null) return $this->enabledCache;

        // Dev convenience: when MODULES_DEV_OVERRIDE=true in .env, every
        // module in the catalog is enabled. Used on the home-dev install so
        // new modules can be exercised without re-issuing licenses for every
        // test. NEVER set this on buyer installs — it defeats licensing.
        // Buyer installs leave it unset and this branch is a no-op.
        if (filter_var(env('MODULES_DEV_OVERRIDE', false), FILTER_VALIDATE_BOOLEAN)) {
            return $this->enabledCache = array_keys((array) config('modules.catalog', []));
        }

        $payload = $this->payload();

        // No license at all → fall back to catalog defaults (dev mode).
        if ($payload === null) {
            $defaults = [];
            foreach ((array) config('modules.catalog', []) as $key => $meta) {
                if (!empty($meta['enabled_by_default'])) $defaults[] = $key;
            }
            return $this->enabledCache = $defaults;
        }

        // License present but invalid → all modules off.
        if ($payload === false) return $this->enabledCache = [];

        // Remote enforcement: if the receiver has marked this license
        // revoked/expired since issuance, or the install hasn't phoned
        // home in too long, lock everything regardless of signature.
        // Gated by MODULES_HEARTBEAT_URL — owner's central server is
        // unset and skips these checks.
        if ($this->isRevokedRemotely($payload)) return $this->enabledCache = [];
        if ($this->isHeartbeatStale())          return $this->enabledCache = [];

        $modules = array_values(array_filter(
            (array) ($payload['modules'] ?? []),
            fn ($k) => is_string($k) && array_key_exists($k, (array) config('modules.catalog', []))
        ));
        return $this->enabledCache = $modules;
    }

    public function isEnabled(string $key): bool
    {
        return in_array($key, $this->enabledModules(), true);
    }

    /**
     * Drop the per-request memo of payload + enabled list. Call this
     * after on-disk license files change within the same request so a
     * follow-up read (e.g. the forced heartbeat fired immediately
     * after installing a new license) sees the new files instead of
     * the previously-cached payload.
     */
    public function clearCache(): void
    {
        $this->payloadCache = null;
        $this->enabledCache = null;
    }

    /**
     * Returns the verified license payload for display purposes.
     * Returns null when no license file exists, false when present-but-invalid.
     */
    public function payload(): array|false|null
    {
        if ($this->payloadCache !== null) return $this->payloadCache;

        $jsonPath = config('modules.license_path');
        $sigPath  = config('modules.signature_path');
        $pubB64   = (string) config('modules.public_key');

        if (!is_file($jsonPath) && !is_file($sigPath)) {
            return $this->payloadCache = null;
        }

        if (!is_file($jsonPath) || !is_file($sigPath) || $pubB64 === '') {
            Log::warning('License files present but incomplete', compact('jsonPath','sigPath'));
            return $this->payloadCache = false;
        }

        $json = @file_get_contents($jsonPath);
        $sigB64 = trim((string) @file_get_contents($sigPath));
        if ($json === false || $sigB64 === '') return $this->payloadCache = false;

        $sig = base64_decode($sigB64, true);
        $pub = base64_decode($pubB64, true);
        if ($sig === false || $pub === false || strlen($pub) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return $this->payloadCache = false;
        }

        try {
            $ok = sodium_crypto_sign_verify_detached($sig, $json, $pub);
        } catch (\Throwable $e) {
            Log::warning('License signature verify threw', ['err' => $e->getMessage()]);
            return $this->payloadCache = false;
        }
        if (!$ok) return $this->payloadCache = false;

        $data = json_decode($json, true);
        if (!is_array($data)) return $this->payloadCache = false;

        // Expiry check.
        if (!empty($data['expires_at'])) {
            try {
                if (Carbon::parse($data['expires_at'])->isPast()) return $this->payloadCache = false;
            } catch (\Throwable) {
                return $this->payloadCache = false;
            }
        }

        return $this->payloadCache = $data;
    }

    /** Status string for the owner panel UI. */
    public function status(): string
    {
        if (filter_var(env('MODULES_DEV_OVERRIDE', false), FILTER_VALIDATE_BOOLEAN)) {
            return 'dev_override';
        }
        $p = $this->payload();
        if ($p === null)  return 'no_license';
        if ($p === false) return 'invalid';
        if ($this->isRevokedRemotely($p)) return 'revoked';
        if ($this->isHeartbeatStale())    return 'stale';
        return 'active';
    }

    /**
     * True when the receiver-cached status file marks the current
     * license as revoked or expired. Only consulted on installs that
     * actually heartbeat (MODULES_HEARTBEAT_URL set); the owner's own
     * server, which never heartbeats, is unaffected.
     */
    private function isRevokedRemotely(array $payload): bool
    {
        if ((string) config('modules.heartbeat_url', '') === '') return false;

        $path = (string) config('modules.revocation_status_path');
        if ($path === '' || !is_file($path)) return false;

        $raw = @file_get_contents($path);
        if ($raw === false) return false;
        $data = json_decode($raw, true);
        if (!is_array($data)) return false;

        $licenseId = $payload['license_id'] ?? null;
        if (!$licenseId || (int) ($data['license_id'] ?? 0) !== (int) $licenseId) {
            return false;
        }

        $status = (string) ($data['status'] ?? '');
        return in_array($status, ['revoked', 'expired'], true);
    }

    /**
     * Dead-man switch — true when the install hasn't heartbeated for
     * longer than the grace window. Reset-resistant: the baseline is
     * the MAX across three independently-stored sources (file mtime,
     * buyer_config.json field, settings table row). All three are
     * updated on every successful heartbeat. A semi-technical buyer
     * deleting one source does not reset the timer; resetting requires
     * finding and editing all three. Future-dated sources are capped
     * at now() to defeat trivial timestamp forgery.
     *
     * Gated on MODULES_HEARTBEAT_URL — owner's central server skips
     * the check entirely.
     */
    private function isHeartbeatStale(): bool
    {
        if ((string) config('modules.heartbeat_url', '') === '') return false;

        // Grace comes from the signed license payload first (owner-set
        // per buyer/license), then falls back to the install's config.
        // Reading from the payload means a buyer can't change their own
        // grace — any edit breaks the signature.
        $payload = $this->payload();
        $payloadGrace = is_array($payload) && array_key_exists('heartbeat_grace_seconds', $payload)
            ? $payload['heartbeat_grace_seconds']
            : null;
        $grace = $payloadGrace !== null
            ? (int) $payloadGrace
            : (int) config('modules.heartbeat_grace_seconds', 3600);
        if ($grace <= 0) return false;

        $now = time();

        $heartbeatSources = array_filter([
            $this->fileMtime((string) config('modules.last_heartbeat_path')),
            $this->buyerConfigInt('last_heartbeat_unix'),
            $this->settingsInt('_license_heartbeat_baseline_unix'),
        ], fn ($t) => $t !== null && $t > 0 && $t <= $now);

        if (!empty($heartbeatSources)) {
            return ($now - max($heartbeatSources)) > $grace;
        }

        // No heartbeat baseline anywhere — fall back to install time so
        // a fresh install gets its grace window but a deliberately
        // wiped install (all three baseline sources zeroed by a tamperer)
        // doesn't get free 7-day windows when the install is old.
        $installSources = array_filter([
            $this->buyerConfigInt('installed_at'),
            $this->settingsInt('_license_installed_at_unix'),
        ], fn ($t) => $t !== null && $t > 0 && $t <= $now);

        if (!empty($installSources)) {
            return ($now - max($installSources)) > $grace;
        }

        // Pre-dead-man install with no install timestamp recorded.
        // Allow but log so we notice unseeded installs.
        Log::info('License dead-man check: no baseline source found, allowing');
        return false;
    }

    /**
     * Called by HeartbeatService after a successful POST to the
     * receiver. Persists "now" to all three baseline sources so a
     * deletion of any one source doesn't reset the dead-man clock.
     */
    public function recordHeartbeatSuccess(): void
    {
        $now = time();

        $stamp = (string) config('modules.last_heartbeat_path');
        if ($stamp !== '') {
            $dir = dirname($stamp);
            if (!is_dir($dir)) @mkdir($dir, 0770, true);
            @file_put_contents($stamp, (string) $now);
            @chmod($stamp, 0660);
        }

        $this->updateBuyerConfig(['last_heartbeat_unix' => $now]);
        $this->setSettingsValue('_license_heartbeat_baseline_unix', (string) $now);
    }

    /**
     * Called by the install controller right after writing license
     * files. Persists the install timestamp into buyer_config.json AND
     * the settings table so the dead-man fallback (used when no
     * heartbeat baseline exists yet) can't be reset by deleting either
     * source alone.
     */
    public function recordLicenseInstall(int $licenseId, ?string $licensee = null, array $extra = []): void
    {
        $now = time();
        $cfg = array_merge([
            'installed_at' => $now,
            'license_id'   => $licenseId,
        ], $extra);
        if ($licensee !== null) $cfg['licensee'] = $licensee;
        $this->updateBuyerConfig($cfg);
        $this->setSettingsValue('_license_installed_at_unix', (string) $now);
    }

    /**
     * Read-modify-write merge into buyer_config.json. Used by both
     * heartbeat-success and install paths so the file is the single
     * point of truth for the public_key + heartbeat URL even when
     * separate writers append fields over time.
     */
    public function updateBuyerConfig(array $merge): void
    {
        $path = (string) config('modules.buyer_config_path');
        if ($path === '') return;
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0770, true);

        $existing = is_file($path) ? json_decode((string) @file_get_contents($path), true) : [];
        if (!is_array($existing)) $existing = [];
        $next = array_merge($existing, $merge);
        @file_put_contents($path, json_encode($next, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        @chmod($path, 0640);
    }

    private function fileMtime(string $path): ?int
    {
        if ($path === '' || !is_file($path)) return null;
        $m = @filemtime($path);
        return $m === false ? null : (int) $m;
    }

    private function buyerConfigInt(string $key): ?int
    {
        $path = (string) config('modules.buyer_config_path');
        if ($path === '' || !is_file($path)) return null;
        $j = json_decode((string) @file_get_contents($path), true);
        if (!is_array($j) || empty($j[$key])) return null;
        return (int) $j[$key];
    }

    private function settingsInt(string $key): ?int
    {
        try {
            $v = DB::table('settings')->where('key', $key)->value('value');
            if ($v === null || $v === '') return null;
            return (int) $v;
        } catch (\Throwable) {
            return null;
        }
    }

    private function setSettingsValue(string $key, string $value): void
    {
        try {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'group' => '_system', 'updated_at' => now(), 'created_at' => now()]
            );
        } catch (\Throwable $e) {
            Log::warning('License settings write failed', ['key' => $key, 'err' => $e->getMessage()]);
        }
    }
}

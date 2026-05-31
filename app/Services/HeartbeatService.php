<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Install-side heartbeat sender.
 *
 * Reads the local license file, looks up the install_id (generating one
 * the first time), and POSTs to the owner's heartbeat receiver. Used by
 * the scheduled `license:heartbeat` artisan command and by an opportunistic
 * trigger from middleware so the heartbeat fires even on hosts without
 * cron.
 *
 * Failures are logged but never thrown — clones must not be able to
 * disable the app by blocking the receiver.
 */
class HeartbeatService
{
    public function __construct(private readonly LicenseService $license) {}

    /**
     * Best-effort send. Returns one of:
     *   'sent'            successful POST
     *   'no_url'          no heartbeat URL configured (we are the owner server)
     *   'no_license'      no installed license to report on
     *   'no_license_id'   license payload missing license_id (legacy license)
     *   'too_soon'        sent within heartbeat_interval already
     *   'http_error'      receiver returned non-2xx
     *   'network_error'   could not reach the receiver
     */
    public function sendIfDue(bool $force = false): string
    {
        $url = (string) config('modules.heartbeat_url', '');
        if ($url === '') return 'no_url';

        $payload = $this->license->payload();
        if (!is_array($payload)) return 'no_license';

        $licenseId = $payload['license_id'] ?? null;
        if (!$licenseId) return 'no_license_id';

        $stampPath = storage_path('license/last_heartbeat');
        if (!$force && is_file($stampPath)) {
            $age = time() - filemtime($stampPath);
            if ($age < (int) config('modules.heartbeat_interval', 86400)) return 'too_soon';
        }

        $body = [
            'license_id'  => (int) $licenseId,
            'install_id'  => $this->installId(),
            'host'        => request()?->getHost() ?: gethostname(),
            'ip'          => null, // server uses request IP from the receiver side
            'app_version' => config('app.version', '1.0.0'),
        ];

        try {
            // 10s is generous for cross-region first-handshake; we'd
            // rather succeed late than miss a heartbeat.
            $resp = Http::timeout(10)->connectTimeout(5)->acceptJson()->post($url, $body);
        } catch (\Throwable $e) {
            Log::warning('Heartbeat network error', ['err' => $e->getMessage()]);
            return 'network_error';
        }

        if (!$resp->successful()) {
            Log::warning('Heartbeat receiver returned non-2xx', ['status' => $resp->status(), 'body' => $resp->body()]);
            return 'http_error';
        }

        // Persist "now" across all three baseline sources (file, buyer_config,
        // settings table) so deleting any one source doesn't reset the
        // dead-man clock.
        $this->license->recordHeartbeatSuccess();

        $this->recordReceiverStatus((int) $licenseId, $resp->json());

        return 'sent';
    }

    /**
     * Persist the receiver's view of this license so LicenseService can
     * honor remote revocation/expiry on the next request. Best-effort;
     * a missing or malformed body is ignored (license stays active).
     */
    private function recordReceiverStatus(int $licenseId, mixed $body): void
    {
        if (!is_array($body)) return;
        $status = $body['status'] ?? null;
        if (!is_string($status) || $status === '') return;

        $path = (string) config('modules.revocation_status_path');
        if ($path === '') return;

        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0770, true);
        @file_put_contents($path, json_encode([
            'license_id' => $licenseId,
            'status'     => $status,
            'checked_at' => time(),
        ], JSON_UNESCAPED_SLASHES));
        @chmod($path, 0660);
    }

    /**
     * UUID assigned to this install at first run, persisted on disk so
     * that subsequent heartbeats use the same id. Cloning the entire
     * install copies this file, which is fine — the heartbeat receiver
     * detects "same install_id from different hosts/IPs" as a clone.
     * Wiping this file before cloning produces a "different install_ids
     * for the same license_id" pattern, which is also detected.
     */
    public function installId(): string
    {
        $path = (string) config('modules.install_id_path');
        if (is_file($path)) {
            $id = trim((string) @file_get_contents($path));
            if (Str::isUuid($id)) return $id;
        }
        $id = (string) Str::uuid();
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0770, true);
        @file_put_contents($path, $id);
        @chmod($path, 0660);
        return $id;
    }
}

<?php
namespace App\Services;

use App\Models\Buyer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Wraps ed25519 signing of module licenses. Used by:
 *   - php artisan modules:sign  (CLI)
 *   - the owner panel sign actions (web)
 *   - the buyer-ledger "issue license" flow
 *
 * Throws RuntimeException with a user-safe message on any failure.
 */
class LicenseSigner
{
    /**
     * Sign a payload and return ['payload' => array, 'json' => string,
     * 'signature_b64' => string, 'fingerprint' => string].
     *
     * @param string $licensee
     * @param string[] $modules  Module keys; invalid keys are dropped silently
     * @param Carbon|null $expiresAt
     * @param string|null $privateKeyPath  Defaults to config('modules.private_key_path')
     */
    public function sign(string $licensee, array $modules, ?Carbon $expiresAt = null, ?string $privateKeyPath = null): array
    {
        if ($licensee === '') {
            throw new RuntimeException('Licensee is required.');
        }

        $catalog = (array) config('modules.catalog', []);
        $modules = array_values(array_unique(array_filter(
            $modules,
            fn ($k) => is_string($k) && array_key_exists($k, $catalog)
        )));
        if (empty($modules)) {
            throw new RuntimeException('At least one valid module key is required.');
        }

        $keyPath = $privateKeyPath ?: config('modules.private_key_path');
        if (!is_file($keyPath)) {
            throw new RuntimeException("Private key not found at: $keyPath. Generate with `php artisan modules:keygen`.");
        }
        if (!is_readable($keyPath)) {
            // Almost always means the web user (www-data) can't read a key
            // that was generated as root or another user. On the owner's
            // own machine this is safe to fix; never on a buyer install.
            $webUser = function_exists('posix_geteuid') && function_exists('posix_getpwuid')
                ? (posix_getpwuid(posix_geteuid())['name'] ?? 'web user')
                : 'web user';
            throw new RuntimeException(
                "Private key at $keyPath is not readable by $webUser. " .
                "On the owner workstation, fix with: " .
                "sudo chgrp www-data $keyPath && sudo chmod 640 $keyPath"
            );
        }
        $contents = @file_get_contents($keyPath);
        if ($contents === false) {
            throw new RuntimeException("Could not read private key at $keyPath.");
        }
        $secret = base64_decode(trim($contents), true);
        if ($secret === false || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new RuntimeException('Private key file is malformed.');
        }

        $payload = [
            'licensee'   => $licensee,
            'issued_at'  => now()->toIso8601String(),
            'expires_at' => $expiresAt?->endOfDay()?->toIso8601String(),
            'modules'    => $modules,
        ];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $sig  = sodium_crypto_sign_detached($json, $secret);

        // Public-key fingerprint (first 8 bytes of sha256 in hex) so we can
        // tell which keypair signed an old license, useful after a key rotation.
        $public = sodium_crypto_sign_publickey_from_secretkey($secret);
        $fp     = substr(hash('sha256', $public), 0, 16);

        return [
            'payload'       => $payload,
            'json'          => $json,
            'signature_b64' => base64_encode($sig),
            'fingerprint'   => $fp,
        ];
    }

    /**
     * Write signed json+sig to storage/license/ with perms that survive
     * being rewritten by the web user.
     *
     * Used by the "install on this server" action and by the CLI. This has
     * to work whether the existing files were created by root (CLI) or by
     * www-data (web), so we self-heal: if a file exists and we can't
     * overwrite it, unlink it first (only needs dir write) and then create
     * fresh. Final perms are 0660 group=web-server-group so the panel can
     * rewrite the same file later.
     *
     * On a buyer install where there's no panel and no rewrites happen,
     * 0660 vs 0640 makes no security difference — what matters is that the
     * web user can READ to verify the signature.
     */
    /**
     * Sign a license for a specific buyer using THEIR private key.
     *
     * Each buyer has a unique ed25519 keypair stored encrypted in the
     * `buyers` table. A license signed for one buyer cannot validate on
     * another buyer's install because their installs each pin their own
     * MODULES_PUBLIC_KEY env. This is the cross-buyer piracy defence.
     *
     * Pass licenseId when re-signing an already-saved license so the
     * signed payload includes the row id (used by heartbeats).
     */
    public function signForBuyer(Buyer $buyer, array $modules, ?Carbon $expiresAt = null, ?int $licenseId = null, ?int $graceSeconds = null): array
    {
        if (empty($buyer->private_key)) {
            throw new RuntimeException("Buyer #{$buyer->id} has no keypair. Generate one first.");
        }
        $secret = $buyer->privateKeyRaw();
        if (strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new RuntimeException("Buyer #{$buyer->id} private key is malformed.");
        }

        $catalog = (array) config('modules.catalog', []);
        $modules = array_values(array_unique(array_filter(
            $modules, fn ($k) => is_string($k) && array_key_exists($k, $catalog)
        )));
        if (empty($modules)) {
            throw new RuntimeException('At least one valid module key is required.');
        }

        $payload = [
            'license_id' => $licenseId,
            'buyer_id'   => $buyer->id,
            'licensee'   => $buyer->name,
            'issued_at'  => now()->toIso8601String(),
            'expires_at' => $expiresAt?->endOfDay()?->toIso8601String(),
            'modules'    => $modules,
        ];
        // Per-license dead-man grace. Embedded in the signed payload so
        // the buyer can't tamper with it on their install (any edit
        // breaks the signature). Null = install falls back to its own
        // config('modules.heartbeat_grace_seconds') default.
        if ($graceSeconds !== null) {
            $payload['heartbeat_grace_seconds'] = (int) $graceSeconds;
        }
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $sig  = sodium_crypto_sign_detached($json, $secret);

        return [
            'payload'       => $payload,
            'json'          => $json,
            'signature_b64' => base64_encode($sig),
            'fingerprint'   => $buyer->publicKeyFingerprint(),
        ];
    }

    public function installLocally(string $json, string $signatureB64): void
    {
        $this->writePair(
            (string) config('modules.license_path'),
            (string) config('modules.signature_path'),
            $json,
            $signatureB64,
        );
        Log::info('License installed locally');
    }

    /**
     * Write a license json + sig pair to a chosen directory. Used by the
     * CLI when --out points somewhere other than the live install path.
     */
    public function writePair(string $jsonPath, string $sigPath, string $json, string $signatureB64): void
    {
        $dir = dirname($jsonPath);
        if (!is_dir($dir) && !@mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new RuntimeException("Cannot create directory: $dir");
        }
        // setgid (2xxx) + group rwx so files created here inherit the
        // directory group and the web user can overwrite them.
        @chmod($dir, 02770);
        $webGroup = $this->detectWebGroup();
        if ($webGroup) @chgrp($dir, $webGroup);

        $this->writeFile($jsonPath, $json);
        $this->writeFile($sigPath, $signatureB64);
    }

    /**
     * Write atomically with self-healing perms: unlink-then-create when a
     * stale file owned by another user is in the way. Final mode 0664 so
     * any user on the box can read (license content isn't secret — the
     * signature is the security) and the web user can overwrite on the
     * next call.
     */
    private function writeFile(string $path, string $contents): void
    {
        // If a file is in the way and we can't write to it, drop it first.
        // unlink only needs write on the parent directory.
        if (is_file($path) && !is_writable($path)) {
            if (!@unlink($path)) {
                throw new RuntimeException("Cannot replace $path (permission denied and unlink failed). Fix: sudo chmod 660 $path");
            }
        }
        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException("Cannot write $path. Fix: sudo chmod 770 ".dirname($path));
        }
        @chmod($path, 0664);
        $webGroup = $this->detectWebGroup();
        if ($webGroup) @chgrp($path, $webGroup);
    }

    private function detectWebGroup(): ?string
    {
        foreach (['www-data', 'apache', 'nginx', 'http'] as $g) {
            if (function_exists('posix_getgrnam') && @posix_getgrnam($g)) return $g;
        }
        return null;
    }
}
